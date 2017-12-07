<?php
namespace re\rgx;
define('CWD', getcwd() . DIRECTORY_SEPARATOR);

chdir(__DIR__);
date_default_timezone_set('PRC');

class release {

    public $opts = [];

    public function __construct () {
        $this->parse_opts();
    }


    public function error ($msg) {
        exit(date('Y-m-d h:i:s ') . $msg . PHP_EOL);
    }


    public function tpl () {
        $rc_file = isset($_SERVER['argv'][1]) ? realpath(CWD . $_SERVER['argv'][1]) : null;
        if (!$rc_file) {
            $this->error('config file not found');
        }
        $rc  = include($rc_file);

        echo(PHP_EOL . "- task config info");
        echo(PHP_EOL . " app dir    : {$rc['app_dir']} ");
        echo(PHP_EOL . " app url    : {$rc['app_url']} " . PHP_EOL);
        echo(" source dir : {$rc['src_dir']} " . PHP_EOL);
        echo(" target dir : {$rc['dst_dir']} " . PHP_EOL);
        echo("-" . PHP_EOL);

        echo(PHP_EOL. "please confirm the config info, press Y to continue ..." . PHP_EOL  );

        $s = trim(stream_get_line(STDIN, 1));
        if (strtolower($s) != 'y') {
            $this->error('abort');
        }

        if (!file_exists($rc['app_dir'] . 'index.php')) {
            exit("{$rc['app_dir']}index.php does not exists" . PHP_EOL);
        }

        putenv('request_uri=index');
        putenv('request_hold=1');
        putenv("app_url={$rc['app_url']}");

        chdir($rc['app_dir']);

        include($rc['app_dir'] . 'index.php');

        if (!defined('CTPL_URL')) {
            app::tpl()->define_ctpl_url();
        }

        $this->compile_dir($rc['src_dir'], $rc['dst_dir'], app::tpl());

    }


    private function compile_dir ($src, $dst, $tpl) {

        if (!is_dir($dst)) {
            mkdir($dst, 0755, 1);
            echo("@@@ created dir {$dst}" . PHP_EOL);
        }

        foreach (glob($src . "*") as $v) {
            if (!is_dir($v)) {
                $p = pathinfo($v);
                if ($p['extension'] == 'html') {
                    echo("compile {$v} => " . $dst . $p['filename'] . ".php" . PHP_EOL);
                    $tpl->parse_tpl($dst . $p['filename'] . ".php", $v);
                }
                else {
                    copy($v, $dst . $p['basename']);
                    echo("*** copy file {$v} => {$dst}{$p['basename']}" . PHP_EOL);
                }
            }
            else {
                $this->compile_dir($v . DIRECTORY_SEPARATOR, $dst . basename($v) . DIRECTORY_SEPARATOR, $tpl);
            }
        }

        echo("###### compile {$src}  success !" . PHP_EOL . PHP_EOL);
    }

    /**
     * 解析输入参数
     */
    public function parse_opts () {
        $argv = $_SERVER['argv'];
        array_shift($argv);
        if (!empty($argv)) {
            $argv = array_map(function ($row) {
                return substr($row, 1);
            }, $argv);
            parse_str(join("&", $argv), $this->opts);
        }
    }

}


(new release())->tpl();