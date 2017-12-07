<?php
namespace re\rgx;
/**
 * 配置管理类
 */
class config extends rgx {
    
    /**
     * app config
     * @var unknown
     */
    private $app = [
        'lang'      => 'zh_cn',
        'timezone'  => 8,
    ];

    /**
     * template parse config
     * @var unknown
     */
    private $tpl = [
        'style'     => 'default',
        '404_tpl'   => '404.tpl',
        'msg_tpl'   => 'msg.tpl',
        'ob'        => true,
        'native'    => false,
        'tpl_pre'   => '{',
        'tpl_suf'   => '}',
        'cmod'      => false,
        'charset'   => 'utf-8',
        'allow_php' => false
    ];
    
    /**
     * cache config
     * @var unknown
     */
    private $cache  = [
        'type'      => 'file',
        'pre'       => 'rgx_',
        'file'      =>  []
    ];

    /**
     * route config
     * @var unknown
     */
    private $route  = [
        'def_mod'   => 'index',     // 默认 module
        'def_act'   => 'index',     // 默认 action
        'ma_sep'    => '/',         // module action 连接符
        'ap_sep'    => '?',         // action querystring 连接符
        'pg_sep'    => '&',         // kv 组连接符
        'pp_sep'    => '=',         // key value 连接符
        'suf'       => '.html',     // 伪静态后缀
        'rewrite'   => false,       // 是否开启rewrite
        '404_tpl'   => '404.tpl'    // 404 模板页
    ];

    /**
     * log config
     * @var unknown
     */
    private $log  = [
        'type'      => 'file',
        'file'      => [
            'dev'   => false
        ],
    ];

    /**
     * database config
     * @var unknown
     */
    private $db  = [
        'pre'       => 'pre_',
        'type'      => 'mysql',
        'mysql'     => [
            'default'   => 'host=127.0.0.1;port=3306;db=rgx;user=root;passwd=;charset=utf8;profile=true',
            // 'read'      => ['...', '...'],
            // 'write'     => ['', ''],
        ],
    ];
    
    /**
     * session config
     * @var unknown
     */
    private $sess = [
        'type'      => 'php',
        'php'       => array(
            'ttl'   => 600
        )
    ];
    
    /**
     * construct
     * @param array $opts
     */
    protected function __construct ($opts = []) {
        foreach ((array)$opts as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = is_array($v) ? array_merge($this->$k, $v) : $v;
            }
            else {
                $this->$k = $v;
            }
        }
        return $this;
    }
    
    /**
     * get instance
     * @param array $opts
     */
    public static function get_instance ($opts = []) {
        static $instance = null;
        if (empty($instance)) {
            $instance = new config ($opts);
        }
        return $instance;
    }
    
    /**
     * get time
     */
    public  function request_time () {
        return defined('IS_CLI') && IS_CLI ? time() : REQUEST_TIME;
    }
    
    /**
     * get item
     * @param string $key
     *
     * @example
     *      get('abc.efg.0')
     *      get('lang')
     */
    public function get ($key) {
        $paths = explode('.', $key);
        $ref = $this;
        while (!empty($paths)) {
            $k = array_shift($paths);
            if (is_object($ref)) {
                if (!property_exists($ref, $k)) {
                    $ref->$k = [];
                }
                $ref = &$ref->$k;
            }
            else if (is_array($ref)) {
                if (!array_key_exists($k, $ref)) {
                    $ref[$k] = [];
                }
                $ref = &$ref[$k];
            }
        }
        return $ref;
    }

    /**
     * 设置
     * @param string $key
     * @param mixed $lang
     * @param bool  $sync
     *
     * @example
     *      set('abc.efg.0', ['foo' => 1])
     *      set('lang', 'zh_cn')
     */
    public function set ($key, $value, $sync = false) {
        $paths = explode('.', $key);
        $ref = $this;
        while (!empty($paths)) {
            $k = array_shift($paths);
            if (is_object($ref)) {
                if (!property_exists($ref, $k)) {
                    $ref->$k = [];
                }
                $ref = &$ref->$k;
            }
            else if (is_array($ref)) {
                if (!array_key_exists($k, $ref)) {
                    $ref[$k] = [];
                }
                $ref = &$ref[$k];
            }
        }
        $ref = $value;
        $sync && $this->sync();
    }
    
    /**
     * sync config to file
     * @param string $source
     */
    public function sync ($source = true) {
        $dist_file = APP_PATH . "config" . DS . "config.php";
        if (!$source) {
            $dist_file = CACHE_PATH . APP_NAME . "_" . APP_ID . DS . "~config.php";
        }
        // write
        $out  = "<?php" . PHP_EOL
                . "/**" . PHP_EOL
                . " * app " . APP_ID ." config file " . PHP_EOL
                . " * @modified by RGX v" . RGX_VER . PHP_EOL
                . " */" . PHP_EOL
                . " return " . var_export(get_object_vars($this), true) . ";";
        // 创建目录
        if (!is_dir(dirname($dist_file))) {
            misc::mkdir(dirname($dist_file));
        }
        // 写入文件
        if (!file_put_contents($dist_file, $out, LOCK_EX)) {
            exit("写入配置文件失败");
        }
    }
}
