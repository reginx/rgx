<?php
namespace re\rgx;
// 开始时间 (ms)
define('START_TIME', floatval($_SERVER["REQUEST_TIME_FLOAT"] ?: microtime(true)));

// 当前时间 (s)
define('REQUEST_TIME', intval($_SERVER['REQUEST_TIME']) ?: time());

// 禁用直接访问当前文件
if (!defined('IN_RGX')) {
    header('HTTP/1.1 403 Access Forbidden');
    exit('Access Forbidden !');
}

// 是否为命令行方式运行
define('IS_CLI', php_sapi_name() == 'cli');

// 是否是映射模式运行
if (!defined('IS_ALIAS')) {
    define('IS_ALIAS', false);
}

// 是否开启映射模板目录
if (!defined('IS_ALIAS_TPL')) {
    define('IS_ALIAS_TPL', false);
}

// 是否定义了APP_ID
if (!defined('APP_ID')) {
    define('APP_ID', APP_NAME);
}

// 运行模式
if (!defined('RUN_MODE')) {
    define('RUN_MODE', 'normal');
}

if (RUN_MODE == 'debug') {
    // 开启输出缓冲
    ob_start();
    error_reporting(E_ALL ^ E_NOTICE);
}
else {
    error_reporting(0);
}

// defines
define('RGX_VER', '1.0.0');
define('RGX_BUILD', 1);
define('RGX_PATH', __DIR__ . DS);
define('MOD_PATH', APP_PATH . 'module' . DS);
define('PLU_PATH', APP_PATH . 'plugin' . DS);
define('TPL_PATH', APP_PATH . 'template' . DS);
define('LANG_PATH', APP_PATH . 'lang' . DS);
define('TEMP_PATH', DATA_PATH . 'temp' . DS);
define('CACHE_PATH', DATA_PATH . 'cache' . DS);
define('UPLOAD_PATH', DATA_PATH . 'attachment' . DS);


$create_rt_file = true;
// runtime config file
$rt_config = CACHE_PATH . APP_NAME . "_" . APP_ID . DS . "~config.php";
$config = [];
if (RUN_MODE != 'debug' && is_file($rt_config)) {
    $config = include($rt_config);
}
else if (file_exists(APP_PATH . 'config' . DS . 'config.php')) {
    $config = include(APP_PATH . 'config' . DS . 'config.php');
}

/**
 * 开发模式运行
 * 1. 不生成运行时文件
 * 2. 模板文件每次都重新解析生成
 * 3. 错误消息完整输出
 */
if (RUN_MODE == 'debug') {
    // load files
    $files = glob(RGX_PATH . 'class/*.php');
    // 优先加载基类
    include(RGX_PATH . 'class' . DS . 'rgx.class.php');
    // 加载框架核心文件
    foreach ($files as $file) {
        if (basename($file, '.class.php') != 'rgx') {
            include($file);
        }
    }
}

/**
 *  生产模式运行
 * 1. 生成运行时文件 (若框架文件有更新, 需要手动清除运行时文件)
 * 2. 模板文件会自动检测进行更新
 * 3. 错误消息只输出概要
 */
else {
    $runtimefile = CACHE_PATH . APP_NAME . '_' . APP_ID . DS . '~runtime.php';
    if (!is_file($runtimefile) || !file_exists($runtimefile)) {
        $files = [RGX_PATH . 'class' . DS . 'rgx.class.php'];
        array_walk(glob(RGX_PATH . 'class/*.php'), function ($file) use (&$files) {
            if (basename($file, '.class.php') != 'rgx') {
                $files[] = $file;
            }
        });

        // 基本库, 配置所需的驱动库及语言包
        $files[] = RGX_PATH . 'extra' . DS . 'lang'  . DS . 'default.' . ($config['lang'] ?: 'zh-cn') . '.php';

        array_walk(glob(RGX_PATH . 'extra/cache/*.php'), function ($file) use (&$files) {
            $files[] = $file;
        });
        array_walk(glob(RGX_PATH . 'extra/db/*.php'), function ($file) use (&$files) {
            $files[] = $file;
        });
        array_walk(glob(RGX_PATH . 'extra/log/*.php'), function ($file) use (&$files) {
            $files[] = $file;
        });
        array_walk(glob(RGX_PATH . 'extra/sess/*.php'), function ($file) use (&$files) {
            $files[] = $file;
        });
        array_walk(glob(RGX_PATH . 'extra/image/*.php'), function ($file) use (&$files) {
            $files[] = $file;
        });

        if (RUN_MODE == 'full') {
            $find = function ($type, $dir, &$ret, $callback) {
                foreach (glob($dir . '*') as $v) {
                    if (is_dir($v)) {
                        $callback($type, $v . DS, $ret, $callback);
                    }
                    else if (preg_match('/.+?\.' . $type . '\.php$/i', $v)) {
                        $ret[] = $v;
                    }
                }
            };
            $find('cls', INC_PATH . 'cls' . DS, $files, $find);
            $find('lib', INC_PATH . 'lib' . DS, $files, $find);
            $find('table', INC_PATH . 'table' . DS, $files, $find);
        }

        $out = '<?php namespace re\rgx {' . PHP_EOL;
        foreach ($files as $file) {
            $gets = trim(file_get_contents($file));
            if (substr($gets, 0, 5) == '<?php') {
                $gets = substr($gets, 5);
            }
            if (substr($gets, -2) == '?>') {
                $gets = substr($gets, 0, -2);
            }
            $out .= str_replace('namespace re\rgx;', '', trim($gets)) . PHP_EOL;
        }
        $out .= '}';

        // 创建 缓存 app 目录
        if (!is_dir(CACHE_PATH . APP_NAME . '_' . APP_ID)) {
            mkdir(CACHE_PATH . APP_NAME . '_' . APP_ID, 0755, true);
        }
        /**
         * 写入运行时文件
         */
        if (!file_put_contents($runtimefile, preg_replace('/\?\>\s*\<\?php\s+/is', "\r\n", $out), LOCK_EX)) {
            exit('Failed to create runtime file ');
        }
        $out = null;
        file_put_contents($runtimefile, php_strip_whitespace($runtimefile), LOCK_EX);
        $create_rt_file = true;
    }
    include ($runtimefile);
}

/**
 * 插件钩子
 */
plugin::notify('RGX_LOAD', 0);

/**
 * 初始化配置管理
 */
config::get_instance($config);

/**
 * 生成运行时配置文件
 */
$create_rt_file && config::get_instance()->sync(false);

/**
 * 设置语言
 */
lang::set_local(CFG('app.lang'));

/**
 * 设置自动加载处理方法
 */
spl_autoload_register(array('re\rgx\app', 'loader'));

/**
 * 初始化应用
 */
app::init();