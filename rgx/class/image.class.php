<?php

namespace re\rgx;

abstract class image extends rgx {
    
    /**
     * 左侧
     * @var unknown
     */
    const LEFT = 1;
    
    /**
     * 左上角
     * @var unknown
     */
    const LEFT_TOP = 2;
    
    /**
     * 左下角
     * @var unknown
     */
    const LEFT_BOTTOM = 3;
    
    /**
     * 右侧
     * @var unknown
     */
    const RIGHT = 4;
    
    /**
     * 右上角
     * @var unknown
     */
    const RIGHT_TOP = 5;
    
    /**
     * 右下角
     * @var unknown
     */
    const RIGHT_BOTTOM = 6;
    
    /**
     * 居中
     * @var integer
     */
    const CENTER = 7;
    
    /**
     * 获取图片信息
     *
     * @param unknown $file
     */
    abstract public function get_info ($file);
    
    /**
     * 生成缩略图
     * @param string $sfile
     * @param string $dfile
     * @param array $config
     */
    abstract public function thumb ($sfile, $dfile, $config = []);
    
    /**
     * 生成验证码
     * @param unknown $str
     * @param array $config
     */
    abstract public function captcha ($str, $config = []);
    
    /**
     * 图片裁剪
     * @param string $sfile
     * @param string $dfile
     * @param array $config
     */
    abstract public function cut ($sfile, $dfile, $config = []);
    
    /**
     * 生成水印图片
     * @param string $sfile
     * @param array $config
     */
    abstract public function water ($sfile, $dfile = null, $config = []);
    
    /**
     * 获取图片操作对象
     * @param array $config
     * @throws exception
     * @return object
     */
    public static final function get_instance ($driver = null) {
        static $ret = null;
        if (empty($ret)) {
            $type = 'gd';
            if ($driver == null) {
                if (class_exists('\Imagick', false)) {
                    $type = 'imagick';
                }
                else if (!defined('GD_VERSION')) {
                    throw new exception(LANG('driver file not found', "{$type}_image"), exception::NOT_EXISTS);
                }
            }
            else {
                $type = $driver;
            }
            if (RUN_MODE == 'debug') {
                $file = RGX_PATH . 'extra/image/' . $type . '.image.php';
                if (!is_file($file)) {
                    throw new exception(LANG('driver file not found', "{$type}_image"), exception::NOT_EXISTS);
                }
                include ($file);
            }
            $class =  __NAMESPACE__ . "\\" . $type . "_image";
            $ret =  new $class();
        }
        return $ret;
    }


    /**
     * 解析生成缩略图对应的url
     * @param string $image
     * @param string $size
     * @return string
     */
    public static final function get_thumb_name ($image, $size = '') {
        $ret = 'null.gif';
        $size = $size == 'auto' ? '' : $size;
        // 完整的 url 不做处理
        if (strpos($image, 'http://') !== false) {
            $ret = $image;
        }
        // 带有 null.gif 的 url 不做处理
        else if (strpos($image, 'null.gif') !== false) {
            $ret = $image;
        }
        // foo/a.gif
        else if (!empty($image)) {
            $tmp = explode('.', $image);
            $ret = $tmp[0] . (empty($size) ? "_thumb." : ('.' . $size . '.')) . $tmp[1];
        }
        return $ret;
    }
    
    /**
     * 获取相关位置对应的坐标
     * @param string $type
     * @param unknown $pos
     * @example $config = ['swidth' => 600, 'sheight' => 338, 'dwidth' => 200, 'dheight' => 140, 'padding' => 10]
     */
    public static final function get_position ($type, $pos = []) {
        $ret = [];
        $pos['padding'] = $pos['padding'] ?: 0;
        if ($type == self::LEFT_TOP) {
            $ret['x'] = $pos['padding'];
            $ret['y'] = $pos['padding'];
        }
        else if ($type == self::LEFT_BOTTOM) {
            $ret['x'] = $pos['padding'];
            $ret['y'] = $pos['sheight'] - $pos['dheight'] - $pos['padding'];
            $ret['y'] = $ret['y'] > $pos['padding'] ? $ret['y'] : $pos['padding'];
        }
        else if ($type == self::RIGHT_TOP) {
            $ret['x'] = $pos['swidth'] - $pos['dwidth'] - $pos['padding'];
            $ret['x'] = $ret['x'] > $pos['padding'] ? $ret['x'] : $pos['padding'];
            $ret['y'] = $pos['padding'];
        }
        else if ($type == self::RIGHT_BOTTOM) {
            $ret['x'] = $pos['swidth'] - $pos['dwidth'] - $pos['padding'];
            $ret['x'] = $ret['x'] > $pos['padding'] ? $ret['x'] : $pos['padding'];
            $ret['y'] = $pos['sheight'] - $pos['dheight'] - $pos['padding'];
            $ret['y'] = $ret['y'] > $pos['padding'] ? $ret['y'] : $pos['padding'];
        }
        else if ($type == self::CENTER) {
            $ret['x'] = ceil(($pos['swidth'] - $pos['dwidth'] - $pos['padding']) / 2);
            $ret['x'] = $ret['x'] > $pos['padding'] ? $ret['x'] : $pos['padding'];
            $ret['y'] = ceil(($pos['sheight'] - $pos['dheight'] - $pos['padding']) / 2);
            $ret['y'] = $ret['y'] > $pos['padding'] ? $ret['y'] : $pos['padding'];
        }
        return $ret;
    }

    /**
     * 获取生成缩略图相关参数
     *
     * @param unknown_type $swidth 原图片宽度
     * @param unknown_type $sheight 原图片高度
     * @param unknown_type $twidth 目标宽度
     * @param unknown_type $theight 目标高度
     * @return array
     */
    public static function get_thumb_size ($srcw, $srch, $dstw, $dsth) {
        $ret = [];
        $prop = sprintf('%.4f', $srcw / $srch);
        if ($dstw == 'auto' && $dsth != 'auto') {
            $ret['height'] = min($dsth, $srch);
            $ret['width']  = floor(min($dsth * $prop, $srcw));
        }
        else if ($dsth == 'auto' && $dstw != 'auto') {
            $ret['width']  = min($dstw, $srcw);
            $ret['height'] = floor(min($dstw / $prop, $srch));
        }
        else if ($dsth == 'auto' && $dstw == 'auto') {
            $ret['width']  = $srcw;
            $ret['height'] = $srch;
        }
        else {
            $ret['width']  = min($dstw, $srcw);
            $ret['height'] = min($dsth, $srch);
        }
        return $ret;
    }

    /**
     * 获取图片长宽
     * @param  [type] $img [description]
     * @return [type]      [description]
     */
    public static function get_image_size ($img) {
        $ret = getimagesize($img);
        return [
            'width'     => $ret[0],
            'height'    => $ret[1]
        ];
    }
}