<?php
namespace re\rgx;

class misc extends rgx {

    /**
     * 获取表达式对应的字节长度
     *
     * @param string $val
     * @return integer
     */
    public static function get_byte_len ($val) {
        $len  = floatval($val);
        switch (strtolower($val{strlen($val) - 1})) {
            case 'g': $len *= 1024;
            case 'm': $len *= 1024;
            case 'k': $len *= 1024;
        }
        return ceil($len);
    }

    /**
     * 获取随机字串
     * @param unknown_type $len
     * @return string
     */
    public static function randstr ($len = 4) {
        $ret = '';
        $randstr = '23456789abcdefghjkmnpqrstuvwxyABCDEFGHJKLMNPQRSTUVW';
        for ($i = 0; $i < $len; $i ++) {
            $ret .= $randstr[mt_rand(0, strlen($randstr) - 1)];
        }
        return $ret;
    }

    /**
     * 截取字符串
     * @param  [type] $str [description]
     * @param  [type] $len [description]
     * @return [type]      [description]
     */
    public static function cutstr ($str, $len, $suf = '') {
        $str = trim(filter::text($str));
        return mb_substr($str, 0, $len, 'utf-8') . (mb_strlen($str, 'utf-8') > $len ? $suf : '');
    }

    /**
     * 创建文件夹
     *
     * @param String $dir
     */
    public static function mkdir ($dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                file_put_contents($dir . '/index.html', '', LOCK_EX);
            }
            else {
                throw new exception(LANG('failed to create dir', misc::relpath($dir)), exception::IO_ERROR);
            }
        }
    }

    /**
     * 递归删除目录及文件 (仅限 data 或 /dev/shm 目录下)
     *
     * @param unknown_type $dir
     * @return unknown
     */
    public static function rmrf ($dir) {
        $nums = 0;
        if (is_dir($dir)) {
            $dir = realpath($dir) . DS;
            if ((strpos($dir, DATA_PATH) !== false && $dir != DATA_PATH)
                || (strpos($dir, '/dev/shm') !== false && $dir != '/dev/shm')) {
                foreach (glob($dir .'*') as $v) {
                    if (is_dir($v)) {
                        $nums += self::rmrf($v);
                    }
                    else {
                        unlink($v);
                        $nums ++;
                    }
                }
                rmdir($dir);
            }
        }
        return $nums;
    }

    /**
     * 获取相对路径 (仅供错误输出用)
     *
     * @param unknown_type $path
     * @return unknown
     */
    public static function relpath ($path) {
        return str_replace(INC_PATH, 'INC:',
                str_replace(APP_PATH, 'APP:',
                        str_replace(RGX_PATH, 'RGX:', $path)
                        )
                );
    }

    /**
     * 获取执行内存使用情况 (unit M)
     */
    public static function get_memory_usage () {
        return [
            'current'   => memory_get_usage(true) / 1048576,
            'peak'      => memory_get_peak_usage(true) / 1048576
        ];
    }

    /**
     * 获取状态信息
     */
    public static function get_profile () {
        return [
            'db'        => app::db()->get_profile(),
            'memory'    => self::get_memory_usage()
        ];
    }

    /**
     * [percent description]
     * @method percent
     * @param  [type]  $child  [description]
     * @param  [type]  $mother [description]
     * @return [type]  [description]
     */
    public static function pencent($child , $mother) {
        return $mother > 0 ? round($child / $mother , 4) * 100 . ' %' : '0 %';
    }
}