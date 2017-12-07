<?php
chdir(__DIR__);
date_default_timezone_set('PRC');
class build {

    /**
     * options
     * @var unknown
     */
    public $opts = [];

    /**
     * app info
     * @var unknown
     */
    public $app  = [];

    /**
     * 架构函数
     */
    public function __construct () {
        // version compare
        if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50400) {
            exit("Requires at least version PHP v5.40");
        }
        if (php_sapi_name() !== 'cli') {
            exit("Please use the command line to run");
        }
        $this->parse_opts();

        if (array_key_exists('c', $this->opts)) {
            $this->create();
        }
        else if (array_key_exists('t', $this->opts)) {
            $this->create_table_files($this->opts['t']);
        }
        else {
            $this->help();
        }
    }

    /**
     * 创建 app
     */
    public function create () {
        if (empty($this->app)) {
            $app['app_id'] = isset($this->opts['i']) ? $this->opts['i'] : null;
            if (!preg_match('/^[a-z][0-9a-z]+$/i', $app['app_id'])) {
                $this->error('Invalid application ID  (^[a-z][0-9a-z]+)');
            }
            $app['app_name'] = isset($this->opts['n']) ? $this->opts['n'] : null;
            if (!preg_match('/^[a-z][0-9a-z]+$/i', $app['app_name'])) {
                $this->error('Invalid application name  (^[a-z][0-9a-z]+)');
            }

            $app['out_dir'] = isset($this->opts['d']) ? $this->opts['d'] : ("../" . $this->opts['i']);
            if (!is_dir($app['out_dir'])) {
                if (!mkdir($app['out_dir'], 0755, true)) {
                    $this->error('Failed to create application directory [ ' . $app['out_dir'] . ' ]');
                }
            }
            else {
                echo("Application already exists, overwrite ? (type Y to continue) ");
                $s = trim(stream_get_line(STDIN, 1));
                if (strtolower($s) != 'y') {
                    exit('Abort' . PHP_EOL);
                }
            }
            $app['out_dir'] = realpath($app['out_dir']);

            $app['data_dir'] = isset($this->opts['dd']) ? $this->opts['dd'] : ($app['out_dir'] . "/data");
            if (!is_dir($app['data_dir'])) {
                if (!mkdir($app['data_dir'], 0755, true)) {
                    $this->error('Failed to create data directory [ ' . $app['data_dir'] . ' ]');
                }
            }
            $app['data_dir'] = realpath($app['data_dir']);

            // dirs
            foreach (['attachment', 'cache', 'temp', 'log'] as $key => $value) {
                if (!is_dir("{$app['data_dir']}/{$value}") && !mkdir("{$app['data_dir']}/{$value}", 0755)) {
                    $this->error('Failed to create directory [ ' . "{$app['data_dir']}/{$value}" . ' ]');
                }
            }

            $app['inc_dir'] = isset($this->opts['id']) ? $this->opts['id'] : ($app['out_dir'] . "/include");
            if (!is_dir($app['inc_dir'])) {
                if (!mkdir($app['inc_dir'], 0755, true)) {
                    $this->error('Failed to create include directory [ ' . $app['inc_dir'] . ' ]');
                }
            }
            $app['inc_dir'] = realpath($app['inc_dir']);

            foreach (['cls', 'lib', 'table'] as $key => $value) {
                if (!is_dir("{$app['inc_dir']}/{$value}") && !mkdir("{$app['inc_dir']}/{$value}", 0755)) {
                    $this->error('Failed to create directory [ ' . "{$app['inc_dir']}/{$value}" . ' ]');
                }
            }

            foreach (['module', 'template/default', 'config', 'plugin'] as $key => $value) {
                if (!is_dir("{$app['out_dir']}/{$value}") && !mkdir("{$app['out_dir']}/{$value}", 0755, true)) {
                    $this->error('Failed to create directory [ ' . "{$app['out_dir']}/{$value}" . ' ]');
                }
            }

            $this->create_index_file($app);
            $this->create_config_file($app);
            echo("Success !" . PHP_EOL);
            echo("App dir {$app['out_dir']}" . PHP_EOL);
        }
    }


    public function get_relative_path ($from, $to, $ds = DIRECTORY_SEPARATOR) {
        $from_list = explode($ds, rtrim($from, $ds));
        $to_list   = explode($ds, rtrim($to, $ds));
        while (count($from_list) && count($to_list) && ($from_list[0] == $to_list[0])) {
            array_shift($from_list);
            array_shift($to_list);
        }
        return str_pad('', count($from_list) * 3, '..' . $ds) . implode($ds, $to_list);
    }

    /**
     * 创建入口文件
     * @param unknown $app
     */
    public function create_index_file ($app) {
        $cmd = join(' ', $_SERVER['argv']);
        $inc_path = $this->get_relative_path($app['out_dir'], $app['inc_dir']);
        $data_path = $this->get_relative_path($app['out_dir'], $app['data_dir']);
        $rgx_path  = $this->get_relative_path($app['out_dir'], realpath('./'));
        $tpl = [
            "<?php",
            "/*",
            "  +-------------------------------------------------------",
            "  + {$app['app_name']} App entry file",
            "  + ------------------------------------------------------",
            "  + @update " . date('Y-m-d H:i:s', time()),
            "  + @cmd /bin/php {$cmd}",
            "  +-------------------------------------------------------",
            "*/",
            "define('IN_RGX',   true);",
            "define('RUN_MODE', 'debug');",
            "define('APP_VER', '1.0.0');",
            "define('DS',       DIRECTORY_SEPARATOR);",
            "define('NS',       'com\\\\{$app['app_name']}_{$app['app_id']}');",
            "define('APP_ID',   '{$app['app_id']}');",
            "define('APP_NAME', '{$app['app_name']}');",
            "define('APP_PATH', realpath('./') . DS);",
            "define('INC_PATH', realpath('{$inc_path}') . DS);",
            "define('DATA_PATH', realpath('{$data_path}') . DS);",
            "include('{$rgx_path}/rgx.php');"
        ];
        if (!file_put_contents("{$app['out_dir']}/index.php", join(PHP_EOL, $tpl), LOCK_EX)) {
            $this->error('Failed to create file [ ' . "{$app['out_dir']}/index.php" . ' ]');
        }
        $index_module = [
            "<?php",
            "namespace com\\{$app['app_name']}_{$app['app_id']};",
            "use \\re\\rgx as RGX;",
            "",
            "class index_module extends RGX\\module {",
            "",
            "   public function index_action () {",
            "       \$this->display('index.tpl');",
            "   }",
            "",
            "}",

        ];
        if (!file_put_contents("{$app['out_dir']}/module/index.module.php", join(PHP_EOL, $index_module), LOCK_EX)) {
            $this->error('Failed to create file [ ' . "{$app['out_dir']}/module/index.php" . ' ]');
        }

        $index_tpl = [
            "<!doctype html>",
            "<html>",
            "   <head>",
            "   <title>RGX</title>",
            "   <meta charset=\"utf-8\"/>",
            "   </head>",
            "",
            "   <body>",
            "       <h1>Hello world ...</h1>",
            "   </body>",
            "</html>"
        ];
        if (!file_put_contents("{$app['out_dir']}/template/default/index.tpl.html", join(PHP_EOL, $index_tpl), LOCK_EX)) {
            $this->error('Failed to create file [ ' . "{$app['out_dir']}/template/default/index.tpl.html" . ' ]');
        }
    }

    /**
     * 创建配置文件
     * @param unknown $app
     */
    public function create_config_file ($app) {
        $tpl = [
            "<?php",
            "   // http://dev.rgx.re/wiki/config.html",
            "   // @see rgx/class/config.class.php",
            "   return [];"
        ];
        if (!file_put_contents("{$app['out_dir']}/config/config.php", join(PHP_EOL, $tpl), LOCK_EX)) {
            $this->error('Failed to create config file [ ' . "{$app['out_dir']}/config/config.php" . ' ]');
        }
    }

    /**
     * 错误输出
     * @param unknown $msg
     * @param string $exit
     */
    public function error ($msg, $exit = true) {
        echo("#error# {$msg}" . PHP_EOL);
        if ($exit) {
            exit();
        }
    }

    /**
     * 解析输入参数
     */
    public function parse_opts () {
        $argv = $_SERVER['argv'];
        foreach ($argv as $key => $value) {
            $value = trim($value);
            $opt = substr($value, 0, 2);
            if (in_array($opt, ['-c', '-d', '-i', '-n', '-dd', '-id', '-f', '-tb', '-t',  '-db', '-h', '-u', '-p', '-P'])) {
                if ($opt == '-c') {
                    $this->opts[substr($opt, 1)] = 1;
                }
                else {
                    list($k, $v) = explode("=", substr($value, 1));
                    $this->opts[$k] = $v ?: null;
                }
            }
        }
    }

    /**
     * 创建表模型文件
     */
    public function create_table_files ($tabs = null) {
        if (!class_exists('PDO', false)) {
            $this->error('extension PDO not found ' . PHP_EOL);
        }
        $app_dir = isset($this->opts['d']) ? realpath($this->opts['d']) : '';

        if (empty($app_dir) || !is_dir($app_dir)) {
            $this->error('invalid app dir');
        }
        $entry_file = $app_dir . "/index.php";
        if (!file_exists($entry_file)) {
            $this->error('entry file not found');
        }
        $defines = @file_get_contents($entry_file);
        if (empty($defines)) {
            $this->error('invalid entry file');
        }
        $matches = [];
        preg_match_all('/\s*define\(\'(NS|INC_PATH)\',\s*\'?(.+?)\'?\);/is', $defines, $matches);
        if (count($matches[1]) != 2) {
            $this->error('invalid entry file');
        }
        list($ns, $inc_path) = $matches[2];
        $ns = str_replace('\\\\', '\\', $ns);
        $inc_path = preg_replace('/(realpath\(\')|(\'\)\s*\.\s*DS)/is', '', $inc_path);
        $inc_path = realpath($app_dir . "/" . $inc_path);
        echo(PHP_EOL . "/-*- app info -*-/" . PHP_EOL);
        echo("app dir       : {$app_dir}" . PHP_EOL);
        echo("include dir   : {$inc_path}" . PHP_EOL);
        echo("namespace     : {$ns}" . PHP_EOL . PHP_EOL);

        $app_config = include("{$app_dir}/config/config.php");
        if (!isset($app_config['db'][$app_config['db']['type']]['default'])) {
            $this->error('DSN is empty' . PHP_EOL);
        }
        if ($app_config['db']['type'] != 'mysql') {
            $this->error("{$app_config['db']['type']} does not support" . PHP_EOL);
        }
        $dsn = $app_config['db'][$app_config['db']['type']]['default'];
        parse_str(join('&', explode(';', $dsn)), $dsn);

        $host   = $dsn['host'] ?: '127.0.0.1';
        $user   = $dsn['user'] ?: 'root';
        $passwd = $dsn['passwd'] ?: '';
        $port   = $dsn['port'] ?: 3306;
        $charset= $dsn['charset'] ?: 'utf8';
        $dbname = $dsn['db'] ?: null;
        $prefix = $app_config['db']['pre'];

        if (empty($dbname)) {
            $this->error('need to specify a database' . PHP_EOL);
        }

        echo("/-*- mysql info -*-/" . PHP_EOL);
        echo("mysql host    : {$host}" . PHP_EOL);
        echo("mysql user    : {$user}" . PHP_EOL);
        echo("mysql passwd  : {$passwd}" . PHP_EOL);
        echo("mysql prot    : {$port}" . PHP_EOL);
        echo("charset       : {$charset}" . PHP_EOL);
        echo("database name : {$dbname}" . PHP_EOL);
        echo("prefix        : {$prefix}" . PHP_EOL . PHP_EOL);
        try {
            $pdo = new PDO("mysql:host={$host};port={$port}", $user, $passwd);
        }
        catch (Exception $e) {
            $this->error($e->getMessage());
        }
        if ($pdo->exec("use {$dbname}") === false) {
            $this->error('database not exists');
        }
        $pdo->exec("set names {$charset}");

        $sql_append = '';
        if (!empty($tabs)) {
            // -t=pre_order*'
            if (strpos($tabs, '*') !== false ) {
                $sql_append = " like '" . str_replace('*', '%', $tabs) . "'";
                $tabs = null;
            }
            // -t=pre_order,pre_user or -t=pre_order
            else {
                $tabs = explode(',', $tabs);
            }
        }
        $cmd = join(' ', $_SERVER['argv']);
        foreach ($pdo->query("show tables {$sql_append}", PDO::FETCH_COLUMN, 0) as $k => $v) {
            if (preg_match('/^' . preg_quote($prefix) . '.+?$/i', $v)) {
                if (is_array($tabs) && !in_array($v, $tabs)) {
                    continue;
                }
                $tab_name = str_replace($prefix, '', $v);
                $path = str_replace('_', DIRECTORY_SEPARATOR, $tab_name);
                $file = $inc_path . DIRECTORY_SEPARATOR . "table" . DIRECTORY_SEPARATOR . "{$path}.table.php";
                if (!is_dir(dirname($file))) {
                    mkdir(dirname($file), 0755, true);
                }

                $table_tpl = [
                    "<?php",
                    "/*",
                    "  +-------------------------------------------------------",
                    "  + {$tab_name} 表模型",
                    "  + ------------------------------------------------------",
                    "  + @update " . date('Y-m-d H:i:s', time()),
                    "  + @desc 若修改了表结构, 请使用下面的命令更新模型文件",
                    "  + @cmd /bin/php {$cmd}",
                    "  +-------------------------------------------------------",
                    "*/",
                    "namespace re\\rgx;",
                    "",
                    "class {$tab_name}_table extends table {",
                    "",
                    "    /*",
                    "      +--------------------------",
                    "      + 编码",
                    "      +--------------------------",
                    "    */",
                    "    protected \$_charset = '-*-charset-*-';",
                    "",
                    "    /*",
                    "      +--------------------------",
                    "      + 字段",
                    "      +--------------------------",
                    "    */",
                    "    protected \$_fields = [",
                    "        -*-fields-*-",
                    "    ];",
                    "",
                    "    /*",
                    "      +--------------------------",
                    "      + 主键",
                    "      +--------------------------",
                    "    */",
                    "    protected \$_primary_key = [",
                    "        -*-primary_key-*-",
                    "    ];",
                    "",
                    "    /*",
                    "      +--------------------------",
                    "      + 字段默认值",
                    "      +--------------------------",
                    "    */",
                    "    public \$defaults = [",
                    "        -*-defaults-*-",
                    "    ];",
                    "",
                    "    /*",
                    "      +--------------------------",
                    "      + 字段过滤规则",
                    "      +--------------------------",
                    "    */",
                    "    public \$filter = [",
                    "        -*-filter-*-",
                    "    ];",
                    "",
                    "    /*",
                    "      +--------------------------",
                    "      + 唯一性检测",
                    "      +--------------------------",
                    "    */",
                    "    public \$unique_check = [",
                    "        -*-unique_check-*-",
                    "    ];",
                    "",
                    "    /*",
                    "      +--------------------------",
                    "      + 自定义字段验证规则",
                    "      + @example ",
                    "           [",
                    "               // 字段名称",
                    "               'name'  => 'user_name',",
                    "               // 验证类型, 0 使用filter::\$rule的规则进行验证",
                    "               //         1 使用正则表达式验证",
                    "               //         2 使用自定义方法验证",
                    "               'type'  => 0,",
                    "               // 若type为 0 则 rule 表示规则名称",
                    "               //         1 则 rule 为正则表达式 (/^\w+\$/)",
                    "               //         2 则 rule 为自定义方法或函数 (['re\\rgx\\filter', 'char']] 或 'is_numeric')",
                    "               'rule'  => 'require',",
                    "               // 验证失败返回的文案",
                    "               // 若要使用语言变量, 则用#开头. (例如: #user name)",
                    "               'error' => '您输入的用户名格式有误'",
                    "           ]",
                    "      +--------------------------",
                    "    */",
                    "    public \$validate = [];",
                    "",
                    "}",
                ];
                $table_tpl = join(PHP_EOL, $table_tpl);
                if (file_exists($file)) {
                    if (isset($this->opts['f'])) {
                        echo ("@update@ {$tab_name}.table.php " . PHP_EOL);
                        $table_tpl = file_get_contents($file);
                        // reset
                        $table_tpl = preg_replace('/protected \$_charset = \'[^;]+?\';/is',
                                "protected \$_charset = '-*-charset-*-';", $table_tpl);
                        $table_tpl = preg_replace('/protected \$_fields = \[[^;]+?\];/is',
                                "protected \$_fields = [" . PHP_EOL . "        -*-fields-*-" . PHP_EOL . "    ];", $table_tpl);
                        $table_tpl = preg_replace('/protected \$_primary_key = \[[^;]+?\];/is',
                                "protected \$_primary_key = [" . PHP_EOL . "        -*-primary_key-*-" . PHP_EOL . "    ];", $table_tpl);
                        $table_tpl = preg_replace('/public \$defaults = \[[^;]+?\];/is',
                                "public \$defaults = [" . PHP_EOL . "        -*-defaults-*-" . PHP_EOL . "    ];", $table_tpl);
                        $table_tpl = preg_replace('/public \$filter = \[[^;]+?\];/is',
                                "public \$filter = [" . PHP_EOL . "        -*-filter-*-" . PHP_EOL . "    ];", $table_tpl);
                        $table_tpl = preg_replace('/public \$unique_check = \[[^;]+?\];/is',
                                "public \$unique_check = [" . PHP_EOL . "        -*-unique_check-*-" . PHP_EOL . "    ];", $table_tpl);
                        //$table_tpl = preg_replace('/public \$validate = \[[^;]+?\];/is',
                        //        "public \$validate = [" . PHP_EOL . "        -*-validate-*-" . PHP_EOL . "    ];", $table_tpl);
                        $table_tpl = preg_replace('/@cmd (.+?)\n/is', "@cmd /bin/php {$cmd}" . PHP_EOL, $table_tpl);
                        $table_tpl = preg_replace('/@update (.+?)\n/is', "@update " . date('Y-m-d H:i:s', time()) . PHP_EOL, $table_tpl);
                    }
                    else {
                        echo ("#skip# {$tab_name}.table.php exists " . (isset($this->opts['f']) ? 1 : 0) . PHP_EOL);
                        continue;
                    }
                }

                $fields = $defaults = $validate = $filter = $primary_key = [];
                $constrct = $pdo->query("show create table {$v}");
                $row = $constrct->fetch(PDO::FETCH_ASSOC);
                preg_match('/CHARSET=(\w+)/i', $row['Create Table'], $match);
                $table_tpl = str_replace('-*-charset-*-', isset($match[1]) ? $match[1] : 'utf8', $table_tpl);

                $res = $pdo->query("show full fields from {$v}");
                $field_list = [];
                $field_max_len = 0;
                $tmp_pk = [];
                $tmp_incr = 'false';
                while (($row = $res->fetch(PDO::FETCH_ASSOC)) != false) {
                    $field_list[$row['Field']] = $row;
                    $field_max_len = $field_max_len < strlen($row['Field']) ? strlen($row['Field']) : $field_max_len;
                    if ($row['Key'] == 'PRI') {
                        $tmp_incr = $row['Extra'] == 'auto_increment' ? 'true' : 'false';
                        $tmp_pk[] = $row['Field'];
                    }
                }

                if (count($tmp_pk) == 1) {
                    $primary_key[] = "'key' => '{$tmp_pk[0]}',";
                    $primary_key[] = "'inc' => {$tmp_incr}";
                }
                else if (count($tmp_pk) > 1) {
                    $primary_key[] = "'key' => ['" . join("', '", $tmp_pk) . "'],";
                    $primary_key[] = "'inc' => false";
                }
                else {
                    $primary_key[] = "'key' => '',";
                    $primary_key[] = "'inc' => false,";
                }


                foreach ($field_list as $row) {
                    $space_append = str_repeat(' ', ceil($field_max_len / 4) * 4 - strlen($row['Field']));
                    $field = mysql_extra::get_field_default($row);
                    if (!isset($field['null'])) {
                        var_dump($field, 333);
                    }
                    $fields_attrs = ['['];
                    $fields_attrs[] = "            'name'               => '{$field['field']}',";
                    $fields_attrs[] = "            'type'               => '{$field['class']}',";
                    $fields_attrs[] = "            'field_type'         => '{$field['type']}',";
                    $fields_attrs[] = "            'min'                => {$field['min']},";
                    $fields_attrs[] = "            'max'                => {$field['max']},";
                    $fields_attrs[] = "            'label'              => '{$field['label']}',";

                    $filter_method  = $field['class'];
                    if ($field['class'] == 'int' || $field['class'] == 'float') {
                        $defaults[] = "'{$field['field']}'{$space_append}=> {$field['default']},";
                    }
                    else if ($field['class'] == 'char') {
                        $defaults[] = "'{$field['field']}'{$space_append}=> '{$field['default']}',";
                    }
                    else if ($field['class'] == 'date') {
                        $defaults[] = "'{$field['field']}'{$space_append}=> '{$field['default']}',";
                        $fields_attrs[] = "            'validate'           => {$field['validate']},";
                        $filter_method = null;
                    }
                    else if ($field['class'] == 'set') {
                        $defaults[] = "'{$field['field']}'{$space_append}=> '{$field['default']}',";
                        $fields_attrs[] = "            'options'        => {$field['options']},";
                        $filter_method = null;
                    }

                    if (!empty($filter_method)) {
                        $filter[]   = "'{$field['field']}'{$space_append}=> ['re\\rgx\\filter', '{$filter_method}'],";
                    }

                    $fields_attrs[] = "            'allow_empty_string' => true,";
                    $fields_attrs[] = "            'allow_null'         => " . ($field['null'] ? 'true' : 'true');
                    $fields_attrs[] = "        ]";
                    $fields_attrs = join(PHP_EOL, $fields_attrs);
                    $fields[] = "'{$row['Field']}' => {$fields_attrs},";
                }

                $output = str_replace('-*-defaults-*-', join(PHP_EOL . "        ", $defaults), $table_tpl);
                $output = str_replace('-*-filter-*-', join(PHP_EOL . "        ", $filter), $output);
                $output = str_replace('-*-validate-*-', '[]', $output);
                $output = str_replace('-*-fields-*-', join(PHP_EOL . "        ", $fields), $output);
                $output = str_replace('-*-primary_key-*-', join(PHP_EOL . "        ", $primary_key), $output);

                // unique check
                $index_query = $pdo->query("show index from {$v}");
                $unique_check = [];
                while (($row = $index_query->fetch(PDO::FETCH_ASSOC)) != false) {
                    if (!$row['Non_unique']) {
                        if (($row['Column_name'] != $tmp_pk[0]) || count($tmp_pk) > 1) {
                            $unique_check[$row['Key_name']][] = $row['Column_name'];
                        }
                        else if (!$tmp_incr) {
                            $unique_check[$row['Key_name']][] = $row['Column_name'];
                        }
                        else {
                            echo("@@@ unique check skip auto_increment field `{$v}`.`{$row['Column_name']}`" . PHP_EOL);
                        }
                    }
                }
                if (!empty($unique_check)) {
                    $unique_check = array_map(function ($keys) {
                        return '[\'' . join('\', \'', $keys) . '\']';
                    }, $unique_check);
                }
                $output = str_replace('-*-unique_check-*-', join("," . PHP_EOL . "        ", $unique_check), $output);

                if (!file_put_contents($file, $output, LOCK_EX)) {
                    $this->error('Failed to create file [ ' . $file . ' ]');
                }
                echo ("create table file {$tab_name}_table success ..." . PHP_EOL);

            }
            else {
                echo ("#skip# {$v}" . PHP_EOL);
            }
        }
    }

    /**
     * 打印帮助信息
     */
    public function help () {
        echo('Usage: php build.php <-c>|<-t> [options...]' . PHP_EOL);
        echo('php build.php -c <-d> <-i> <-n> [-dd, -id]        create & init a application' . PHP_EOL);
        echo('  -d            application save dir' . PHP_EOL);
        echo('  -i            application id   [0-9a-z]' . PHP_EOL);
        echo('  -n            application name [0-9a-z]' . PHP_EOL);
        echo('  -dd           application data dir' . PHP_EOL);
        echo('  -id           application inc  dir' . PHP_EOL);
        echo(PHP_EOL);
        echo('php build.php -t <-d> <-db> <-h, -u, -p, -P, -ch>           create table class files' . PHP_EOL);
        echo('  -d            application  dir' . PHP_EOL);
        echo('  -db           database name' . PHP_EOL);
        echo('  -h            mysql host (default localhost)' . PHP_EOL);
        echo('  -u            mysql user (default root)' . PHP_EOL);
        echo('  -p            passwd (default empty string)' . PHP_EOL);
        echo('  -P            port (default 3306)' . PHP_EOL);
        echo('  -ch           charset (default utf8)' . PHP_EOL);
        echo('  -pre          table && filed prefix (default pre_)' . PHP_EOL);
    }
}

class mysql_extra {

    private static $_validate = [
        'tinyint'   => [
            'byte'      => 1,
            'signed'    => [-128, 127],
            'unsigned'  => [0, 255],
        ],
        'smallint'  => [
            'byte'      => 2,
            'signed'    => [-32768, 32767],
            'unsigned'  => [0, 65535],
        ],
        'mediumint' => [
            'byte'      => 3,
            'signed'    => [-8388608, 8388607],
            'unsigned'  => [0, 16777215],
        ],
        'int'       => [
            'byte'      => 4,
            'signed'    => [-2147483648, 2147483647],
            'unsigned'  => [0, 4294967295],
        ],
        'bigint'    => [
            'byte'      => 8,
            'signed'    => [-9223372036854775808, 9223372036854775807],
            'unsigned'  => [0, 18446744073709551615],
        ],
        'serial'    => [
            'byte'      => 8,
            'signed'    => [-9223372036854775808, 9223372036854775807],
            'unsigned'  => [0, 18446744073709551615],
        ],
        'decimal'   => [
            'byte'      => 0,
            'signed'    => [66, 30],
            'unsigned'  => [65, 30],
        ],
        'float'     => [
            'byte'      => 4,
            'signed'    => [64, 30],
            'unsigned'  => [64, 30],
        ],
        'bit'       => [
            'range'     => [1, 64]
        ],
        'boolean'       => [
            'range'     => [0, 1]
        ],
        'char'          => [
            'range'     => [0, 255]
        ],
        'varchar'       => [
            'range'     => [0, 65536]
        ],
        'tinytext'      => [
            'range'     => [0, 255]
        ],
        'text'          => [
            'range'     => [0, 65535]
        ],
        'mediumtext'    => [
            'range'     => [0, 16777215]
        ],
        'longtext'      => [
            'range'     => [0, 4294967295]
        ],
        'tinyblob'      => [
            'range'     => [0, 255]
        ],
        'blob'          => [
            'range'     => [0, 65535]
        ],
        'mediumblob'    => [
            'range'     => [0, 16777215]
        ],
        'longblob'      => [
            'range'     => [0, 4294967295]
        ],
        'date'          => [
            'year'      => "['re\\rgx\\filter', 'is_mysql_year']",
            'date'      => "['re\\rgx\\filter', 'is_mysql_date']",
            'datetime'  => "['re\\rgx\\filter', 'is_mysql_datetime']",
            'timestamp' => "['re\\rgx\\filter', 'is_mysql_timestamp']",
            'time'      => "['re\\rgx\\filter', 'is_mysql_time']"
        ]
    ];

    private static $_types = [
        'bit|bool|boolean'                                  => 'bit',
        'decimal|float|double|real'                         => 'float',
        'tinyint|smallint|mediumint|int|bigint|serial'      => 'int',
        'char|varchar|tinytext|text|mediumtext|longtext'    => 'char',
        'blob|varblob|tinyblob|blob|mediumblob|longblob'    => 'blob',
        'date|datetime|time|timestamp|year'                 => 'date',
        'set|enum'                                          => 'set',
    ];

    /**
     * 获取字段信息
     * @param unknown $row
     * @return number[]|unknown[]|mixed[]|NULL[]
     */
    public static function get_field_default ($row) {
        $ret = [
            'field' => $row['Field'],
            'field_length'  => count($row['Field'])
        ];
        foreach (self::$_types as $k => $v) {
            $m = [];
            preg_match("/^({$k}).*?(\(.*\))?/is", $row['Type'], $m);
            if (isset($m[1]) && !empty($m[1])) {
                $ret['type'] = $m[1];
                $ret['null'] = $row['Null'] == 'NO' ? false : true;
                $ret['label'] = $row['Comment'] ?: $row['Field'];
                $ret['label'] = preg_replace('/\(.+\)/is', '', $ret['label']);
                $us = strpos($row['Type'], 'unsigned') !== false;

                if ($v == 'int') {
                    $ret['class'] = 'int';
                    $ret['default'] = $row['Default'] ?: 0;
                    $range = self::$_validate[$m[1]][$us ? 'unsigned' : 'signed'];
                    if (isset($m[2])) {
                        $max = (int)str_replace(['(', ')'], '', $m[2]);
                        $max = (int)str_repeat(9, $max);
                        $min = $us ? 0 : (0 - $max);
                        $ret['min'] = $min < $range[0] ? $range[0] : $min;
                        $ret['max'] = $max > $range[1] ? $range[1] : $max;
                        $ret['min'] = $range[0];// : $min;
                        $ret['max'] = $range[1];// : $max;

                    }
                    else {
                        $ret['min'] = $range[0];
                        $ret['max'] = $range[1];
                    }
                }
                else if ($v == 'float') {
                    $ret['class'] = 'float';
                    $ret['default'] = $row['Default'] ?: 0;
                    if (isset($m[2])) {
                        list($ret['min'], $ret['max']) = explode(',', str_replace(['(', ')'], '', $m[2]));
                    }
                    // 取默认值
                    else {
                        $range = self::$_validate[$m[1]][$us ? 'unsigned' : 'signed'];
                        $ret['min'] = $range[0];
                        $ret['max'] = $range[1];
                    }
                }
                else if ($v == 'char') {
                    $ret['class'] = 'char';
                    $ret['default'] = $row['Default'] ?: '';
                    if (!isset($m[2])) {
                        $range = self::$_validate[$m[1]]['range'];
                        $ret['min'] = $range[0];
                        $ret['max'] = $range[1];
                    }
                    else {
                        $ret['min'] = 0;
                        $ret['max'] = str_replace(['(', ')'], '', $m[2]);
                    }
                }
                else if ($v == 'date') {
                    $ret['class'] = 'date';
                    $ret['min'] = 0;
                    $ret['max'] = 0;
                    $ret['default'] = $row['Default'] ?: '';
                    $ret['validate'] = self::$_validate['date'][$m[1]];
                }
                else if ($v == 'set' || $v == 'enum') {
                    $ret['class'] = 'set';
                    $ret['min'] = 0;
                    $ret['max'] = 0;
                    $ret['default'] = $row['Default'] ?: '';
                    $ret['options'] = str_replace(['(', ')'], ['[', ']'], $m[2]);
                }

                break;
            }
        }
        return $ret;
    }

}
new build();