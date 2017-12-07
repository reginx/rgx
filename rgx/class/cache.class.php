<?php
/**
 * 缓存操作接口抽象类
 * @copyright reginx.com
 * $Id: cache.class.php 5 2017-07-19 03:44:30Z reginx $
 */
namespace re\rgx;

abstract class cache extends rgx {

    /**
     * 统计结果
     *
     * @var unknown_type
     */
    protected $_stat = array(
        'write' => 0,
        'read'  => 0
    );
    
    /**
     * 默认配置
     * @var array
     */
    private static $_conf = [
        'type'  => 'file',
        'pre'   => 'RGX_',
        'file'  => []
    ];

    private static $_obj = [];

    /**
     * 统计
     *
     * @param unknown_type $type
     */
    public final function count ($type = 'w') {
        $this->_stat[$type == 'w' ? 'write' : 'read'] ++;
    }
    
    /**
     * 架构函数
     *
     * @param unknown_type $config
     */
    public static final function get_instance ($config = array(), $extra = []) {
        $extra = $extra ?: ['name' => APP_NAME, 'id' => APP_ID];
        $key   = join('_', $extra);
        if (!isset(self::$_obj[$key])) {
            $config = array_merge(self::$_conf, $config);
            // 缓存读取统计
            $GLOBALS['_STAT']['cache_reads']  = 0;
            // 缓存写入统计
            $GLOBALS['_STAT']['cache_writes'] = 0;

            $class = $config['type'] . '_cache';
            $class = __NAMESPACE__ . "\\" . $class;
            
            if (!class_exists($class, false)) {
                if (RUN_MODE == 'debug') {
                    $file = RGX_PATH . 'extra/cache/' . $config['type'] . '.cache.php';
                    if (!is_file($file)) {
                        throw new exception(LANG('driver file not found', $class), exception::NOT_EXISTS);
                    }
                    include($file);
                }
            }
            self::$_obj[$key] = new $class($config[$config['type']], $extra);
        }

        return self::$_obj[$key];
    }
    
    public final static function chk () {}

    /**
     * 缓存数据统计
     *
     * @param unknown_type $type
     */
    abstract public function stat ();

    /**
     * 获取缓存
     *
     * @param String $key
     */
    abstract public function get ($key);

    /**
     * 设置缓存
     *
     * @param String $key
     * @param Mixed $value
     * @param Integer $ttl
     */
    abstract public function set ($key, $value, $ttl = 0);

    /**
     * 删除指定缓存
     *
     * @param String $key
     */
    abstract public function del ($key);

    /**
     * 清除缓存
     */
    abstract public function flush ($group = null);
}