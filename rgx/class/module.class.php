<?php
namespace re\rgx;
/**
 * 核心模块类
 * @copyright reginx.com
 * $Id: module.class.php 5 2017-07-19 03:44:30Z reginx $
 */
class module extends rgx {
    
    /**
     * 会话操作对象
     *
     * @var unknown_type
     */
    public $_sess = null;
    
    /**
     * 模板操作对象
     *
     * @var unknown_type
     */
    protected $_tpl = null;

    /**
     * 是否输出性能报告
     * @var unknown
     */
    public $show_profile = false;
    
    /**
     * 架构函数
     *
     * @param unknown_type $param
     */
    public function __construct($param = array()) {
        $this->_tpl = app::tpl();
        plugin::notify('MOD_INIT');
        // 设置默认 模板风格目录
        $this->_tpl->style($this->_config['template']);
        $this->assign('_MODULE', router::get_mod(false));
        $this->assign('_ACTION', router::get_act(false));
    }

    /**
     * 默认调用
     * @param  [type] $method [description]
     * @param  array  $agrv   [description]
     * @return [type]         [description]
     */
    public function __call ($method, $args = []) {
        if (!method_exists($this, 'defaults')) {
            $this->show404(LANG('not exists', "{$this}::{$method}"));
        }
        $this->defaults($method, $args);
    }
    
    /**
     * 会话开启
     *
     */
    public function sess ($sess_name = null, $sess_id = null, $opts = []) {
       $this->_sess = app::sess($sess_name, $sess_id);
    }

    /**
     * 获取session内容
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function sess_get ($key) {
        return $this->_sess->get($key);
    }

    /**
     * 设置 session 变量
     * @param  [type] $key [description]
     * @param  [type] $val [description]
     * @return [type]      [description]
     */
    public function sess_set ($key, $val) {
        return $this->_sess->set($key, $val);
    }

    /**
     * 移除 session 项目
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function sess_del ($key) {
        return $this->_sess->del($key);
    }

    /**
     * 销毁 session
     * @return [type] [description]
     */
    public function sess_remove () {
        return $this->_sess->remove();
    }

    /**
     * 获取会话ID
     * @return [type] [description]
     */
    public function sess_id () {
        return $this->_sess->sess_id();
    }

    /**
     * 获取参数
     *
     * @param unknown_type $k
     * @return unknown
     */
    public final function get ($key, $type = 'R') {
        return router::get($key, $type);
    }

    /**
     * 是否存在某类型的参数
     *
     * @param unknown_type $k
     * @return unknown
     */
    public final function has ($key, $type = 'R') {
        return router::exists($key, $type);
    }

    /**
     * 模板赋值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     * @return unknown
     */
    public function assign ($key, $value) {
        return $this->_tpl->assign($key, $value);
    }

    /**
     * 获取模板变量
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function get_tpl_var ($key) {
        return $this->_tpl->get($key);
    }
    
    /**
     * 加载模块语言包
     *
     * @param unknown_type $name
     */
    public final function lang ($name = 'zh-cn') {
        core::loadlang(APP_NAME . '@' . $name);
    }
    
    /**
     * 禁止客户端缓存当前页
     */
    public final function nocache () {
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", 0) . "GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
    }

    /**
     * 重定向
     *
     * @param unknown_type $url
     * @param unknown_type $parse
     */
    public final function redirect ($url, $parse = true, $code = '303') {
        header("HTTP/1.1 {$code} See Other");
        header("Location: " . ($parse ? router::url($url) : $url));
        header('X-Powered-By: RGX v' . RGX_VER);
        exit(0);
    }
    
    /**
     * 转发
     *
     * @param unknown_type $class
     * @param unknown_type $method
     */
    public function forward ($class, $method, &$extra = array()) {
        $class = $class . '_module';
        if (empty($extra)) {
            $extra = &$this->conf;
        }
        call_user_func(array( new $class($extra), $method . '_action'));
    }
    
    /**
     * Ajax 输出
     *
     * @abstract 默认不缓存
     * @param unknown_type $data
     * @param unknown_type $type
     */
    public function ajaxout ($data, $type = 'json', $output_header = true) {
        plugin::notify('MOD_AJAXOUT');
        $this->nocache();
        header('X-Powered-By: RGX V' . RGX_VER);
        if ($type == 'json') {
            if ($output_header) {
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
                    header('Content-Type: text/html; charset=utf-8');
                }
                else {
                    header('Content-Type: application/json; charset=utf-8');
                }
            }
            exit(json_encode($data, 256));
        }
        else {
            header('Content-Type: text/html; charset=utf-8');
            exit($data);
        }
    }

    /**
     * ajax 错误输出
     * @param  [type]  $msg   [description]
     * @param  array   $error [description]
     * @param  integer $code  [description]
     * @return [type]         [description]
     */
    public function ajax_failure ($msg, $code = 1) {
        $this->ajaxout([
            'code'  => $code,
            is_array($msg) ? 'error' : 'msg' => $msg
        ]);
    }

    /**
     * ajax 错误输出
     * @param  [type]  $msg   [description]
     * @param  array   $error [description]
     * @param  integer $code  [description]
     * @return [type]         [description]
     */
    public function ajax_success ($msg, $data = [], $url = null, $code = 0) {
        $this->ajaxout([
            'code'  => $code,
            'msg'   => $msg,
            'data'  => $data,
            'url'   => $url
        ]);
    }
    
    /**
     * 渲染模板
     *
     * @param unknown_type $tplfile
     * @return unknown
     */
    public function display ($tplfile, $type = 'text/html', $headers = []) {
        if ($this->show_profile) {
            $this->assign('_profile',misc::get_profile());
        }
        plugin::notify('MOD_DISPLAY');
        return app::tpl()->display($tplfile, $type, $headers);
    }
    
    /**
     * 获取解析后的模板内容
     *
     * @param unknown_type $tplfile
     * @return unknown
     */
    public function fetch ($tplfile) {
        return app::tpl()->fetch($tplfile, true);
    }
    
    /**
     * form token
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function token ($key) {
        if (!isset($GLOBALS['_SESS']) || $GLOBALS['_SESS'] === null) {
            $this->initsess();
        }
        $token =  md5(REQUEST_TIME . misc::randstr(4));
        $this->_sess->set($key . '@' . APP_NAME, $token);
        return "<input type=\"hidden\" name=\"{$key}\" value=\"{$token}\" />";
    }
    
    /**
     * 验证 token
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function verifytoken ($key) {
        $ret = false;
        $val = $this->get($key, '*');
        if (!isset($GLOBALS['_SESS']) || $GLOBALS['_SESS'] === null) {
            $this->initsess();
        }
        if ($val == $this->_sess->get($key . '@' . APP_NAME)) {
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * 删除token
     *
     * @param unknown_type $key
     */
    public function rmtoken ($key) {
        if (!isset($GLOBALS['_SESS']) || $GLOBALS['_SESS'] === null) {
            $this->initsess();
        }
        $this->_sess->del($key . '@' . APP_NAME);
    }
    
    /**
     * 显示404页面
     *
     * @param unknown_type $msg
     */
    public function show404 ($msg) {
        //header("HTTP/1.1 404 Not Found");
        header('X-Powered-By: RGX v' . RGX_VER);
        plugin::notify('MOD_404');
        if (IS_CLI) {
            app::cli_msg($msg ?: 'not found');
        }
        $this->assign('msg', $msg ?: LANG('404 not found'));
        $this->display(CFG('tpl.404_tpl'), 'text/html', ['HTTP/1.1 404 Not Found']);
    }
    
    /**
     * 显示提示消息
     *
     * @param unknown_type $msg
     */
    public function show_msg ($msg, $url = null, $auto_jump = false, $ttl = 2) {
        if (IS_CLI) {
            app::cli_msg($msg);
        }
        $this->assign('msg', $msg);
        $this->assign('url', $url);
        $this->assign('ttl', $ttl);
        $this->assign('auto_jump', $auto_jump);
        $this->display(CFG('tpl.msg_tpl'));
    }
}