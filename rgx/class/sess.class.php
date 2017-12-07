<?php
namespace re\rgx;
/**
 * session 抽象类
 * @author reginx
 */
abstract class sess {

    /**
     * 获取项目值
     * @param mixed $key
     */
    abstract protected function get ($key);

    /**
     * 获取当前配置信息 (sess_name, sess_id, expires)
     */
    abstract protected function get_config ();

    /**
     * 设置项目值
     * @param string $key
     * @param mixed  $value
     */
    abstract protected function set ($key, $value);

    /**
     * 是否存在某项
     * @param string $key
     */
    abstract protected function exists ($key);

    /**
     * 获取回话ID
     */
    abstract protected function sess_id ();

    /**
     * 删除项目值
     * @param string $key
     */
    abstract protected function del ($key);

    /**
     * 会话销毁
     */
    abstract protected function remove ();

    /**
     * GC
     */
    abstract protected function gc ();

    /**
     * 获取域名
     * @return string
     */
    public static final function get_domain () {
        static $_domain = null;
        if (empty($_domain)) {
            $domain = $_SERVER['HTTP_HOST'];
            if (!filter_var($domain, FILTER_VALIDATE_IP)) {
                if (substr_count($_SERVER['HTTP_HOST'], '.') >= 2 ){
                    $_domain = substr($_SERVER['HTTP_HOST'], 
                                strpos($_SERVER['HTTP_HOST'], '.'));
                }
                else {
                    $_domain = '.' . $_SERVER['HTTP_HOST'];
                }
            }
            else {
                $_domain = $_SERVER['HTTP_HOST'];
            }
            if (preg_match('/^.+\:\d+$/i', $_domain)) {
                $_domain = preg_replace('/\:\d+/i', '', $_domain);
            }
        }
        return $_domain;
    }

    /**
     * 获取 sess 操作实例
     * @param mixed $conf
     * @param string $sess_name
     * @param string $sess_id
     * @param array $extra
     * @throws exception
     * @return \re\rgx\sess
     */
    public static final function get_instance($conf, $sess_name = null, $sess_id = null, $extra = []) {
        static $sobj = null;
        if (empty($sobj)) {
            $type = $conf['type'] ? $conf['type'] : 'php';
            if (RUN_MODE == 'debug') {
                $file = RGX_PATH . 'extra/sess/' . $type . '.sess.php';
                if (!is_file($file)) {
                    throw new exception(LANG('driver file not found', "{$conf['type']}_sess"), exception::NOT_EXISTS);
                }
                include ($file);
            }
            $class = __NAMESPACE__ . "\\" . $type . '_sess';
            $sobj = new $class($conf[$conf['type']], $sess_name, $sess_id, $extra);
        }
        return $sobj;
    }

    /**
     * 设置 Cookie
     * @param string $k
     * @param string $v
     * @param number $ttl
     * @param string $path
     */
    public static function set_cookie ($k, $v, $ttl = 0, $path = '/') {
        setcookie($k, $v, $ttl , $path , self::get_domain());
    }
}