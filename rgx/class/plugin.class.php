<?php
namespace re\rgx;
/**
 * 插件类
 * @author reginx
 */
class plugin extends rgx {

    /**
     * 默认切入点配置
     * @var array
     */
    private static $defhooks = [
        'RGX_LOAD'      => [],
        'TPL_INIT'      => [],
        'MOD_INIT'      => [],
        'MOD_DISPLAY'   => [],
        'MOD_404'       => [],
    ];

    /**
     * 列表
     * @var array
     */
    private static $hooks = null;

    /**
     * 执行通知
     * @param string $ctype
     * @param number $limit
     * @param array $param
     */
    public static final function notify ($ctype = 'default', $limit = 0, &$param = []) {
        if (self::$hooks === null) {
            self::load();
        }
        if (isset(self::$hooks[$ctype]) && ! empty(self::$hooks[$ctype])) {
            foreach ((array) self::$hooks[$ctype] as $k => $v) {
               OBJ($v[0])->{$v[1]}($param ? $param : null);
                if ($limit > 0) {
                    break;
                }
            }
        }
    }

    /**
     * 加载插件配置信息
     */
    public static final function load () {
        if (is_file(APP_PATH . 'config/plugins.php')) {
            self::$hooks = include (APP_PATH . 'config/plugins.php');
        }
        else {
            self::$hooks = [];
        }
    }
}