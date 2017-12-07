<?php
/**
 * 默认 sess 实现类
 * @copyright reginx.com
 * $Id: php.sess.php 411 2017-09-28 08:27:45Z reginx $
 */
namespace re\rgx;

class php_sess extends sess {


    /**
     * 配置信息
     * @var array
     */
    protected $config = [];


    /**
     * 架构函数
     *
     * @param unknown_type $conf
     */
    public function __construct ($conf, $sess_name = null, $sess_id = null, $extra = []) {
        $this->config = $confg;
        session_set_cookie_params($this->config['ttl'] ?: 1800 , '/', parent::get_domain());
        $sess_name = $sess_name ? session_name($sess_name) : session_name();
        if (!empty($sess_id)) {
            session_id($sess_id);
        }
        else if (isset($_COOKIE[$sess_name]) && preg_match('/^RS\d{1,3}\-[\w\-]+$/i', $_COOKIE[$sess_name])) {
            $sess_id = $_COOKIE[$sess_name];
            session_id($_COOKIE[$sess_name]);
        }
        else {
            $sess_id = 'RS' . sprintf('%03d', explode('.', $_SERVER['SERVER_ADDR'])[0]) . 
                            md5(app::get_ip() . $_SERVER['HTTP_USER_AGENT']);
            session_id($sess_id);
        }
        session_start($extra);
        if ($conf['cache']) {
            header("Cache-control: private"); // 使用http头控制缓存
        }
    }
    
    /**
     * 获取当前配置信息
     *
     * @return unknown
     */
    public function get_config () {
        return array(
            'id'        => session_id(),
            'name'      => session_name(),
            'expires'   => $this->config['ttl'] ?: 1800
        );
    }
    
    /**
     * 获取 session ID
     *
     * @return unknown
     */
    public function sess_id () {
        return session_id();
    }
    
    /**
     * GC
     *
     */
    public function gc () {}
    
    /**
     * 获取项目值
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function get ($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }
    
    /**
     * 获取项目值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     * @return unknown
     */
    public function set ($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * 删除项目值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     * @return unknown
     */
    public function del ($key) {
        $_SESSION[$key] = null;
    }
    
    /**
     * 验证是否存在某项目
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function exists ($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * 销毁会话
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function remove () {
        return session_destroy();
    }
}