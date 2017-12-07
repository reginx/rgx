<?php
/**
 * 日志
 * @copyright reginx.com
 * $Id: log.class.php 5 2017-07-19 03:44:30Z reginx $
 */
namespace re\rgx;

abstract class log {

    /**
     * 配置信息
     * @var null
     */
    protected $config = null;
    
    /**
     * 写日志
     * @param unknown $mod
     * @param unknown $content
     * @param string $trace
     */
    abstract protected function write ($content, $trace = false, $trace_stack = null);
    
    /**
     * 持久化
     * @param unknown $mod
     */
    abstract protected function flush ($mod = null);
    
    /**
     * 初始化
     * @param unknown $mod
     */
    abstract protected function init ($mod);
    
    /**
     * 获取日志操作对象
     *
     * @return unknown
     */
    public static function get_instance ($config, $callback = null) {
        static $logobj = null;
        if (empty($logobj)) {
            $type = CFG('log.type') ?: 'file';
            if (RUN_MODE == 'debug') {
                $file = RGX_PATH . 'extra/log/' . $type . '.log.php';
                if (!is_file($file)) {
                    throw new exception(LANG('driver file not found', $type . '_log'), exception::NOT_EXISTS);
                }
                include ($file);
            }
            $class = __NAMESPACE__ . "\\" . $type . '_log';
            $logobj = new $class($config[$config['type']]);
            if (is_callable($callback)) {
                $callback($logobj);
            }
        }
        return $logobj;
    }
}// end class