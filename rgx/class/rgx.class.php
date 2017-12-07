<?php
namespace re\rgx;

/**
 * RGX基类
 * $Id: rgx.class.php 181 2017-08-14 03:39:03Z reginx $
 */
class rgx implements \ArrayAccess {

    /**
     * 是否存在
     * @param  [type] $offset [description]
     * @return [type]         [description]
     */
    public function offsetExists ($offset) {
        return property_exists($this, $offset);
    }

    /**
     * 获取
     * @param  [type] $offset [description]
     * @return [type]         [description]
     */
    public function offsetGet ($offset) {
        return $this->$offset;
    }

    /**
     * 设置
     * @param  [type] $offset [description]
     * @param  [type] $value  [description]
     * @return [type]         [description]
     */
    public function offsetSet ($offset, $value) {
        $this->$offset = $value;
    }

    /**
     * 重置
     * @param  [type] $offset [description]
     * @return [type]         [description]
     */
    public function offsetUnset ($offset) {
        $this->$offset = null;
    }
    
    /**
     * Setter
     *
     * @param unknown $key
     * @param unknown $value
     */
    public function __set ($key, $value) {
        $this->$key = $value;
    }
    
    /**
     * Getter
     *
     * @param mixed $key
     */
    public function __get ($key) {
        return isset($this->$key) ? $this->$key : null;
    }
    
    /**
     * 做为字符串时输出
     */
    public function __toString () {
        return get_class($this);
    }
    
    /**
     * Debug info
     */
    public function __debugInfo () {
        return RUN_MODE == 'debug' ? get_object_vars($this) : [
            get_class($this)
        ];
    }
    
}

/**
 * get local string
 */
function LANG () {
    return lang::get(func_get_args());
}


/**
 * get obj instance
 * @param unknown $class
 * @param string $single
 * @param unknown $extra
 */
function OBJ ($class, $single = true, $extra = null) {
    return app::get_instance($class, $single, $extra);
}

/**
 * get/set config item
 * @param unknown $key
 * @param unknown $value
 * @param string $sync
 */
function CFG ($key, $value = null, $sync = false) {
    if ($value !== null) {
        return config::get_instance()->set($key, $value, $sync);
    }
    return config::get_instance()->get($key);
}


/**
 * get/set cache item
 * @param unknown $key
 * @param unknown $value
 * @param string $sync
 */
function CACHE ($key, $value = null, $ttl = null) {
    // value 作为数据来源的callback
    if (is_callable($value)) {
        $ret = app::cache()->get($key);
        if (empty($ret)) {
            $ret = $value();
            app::cache()->set($key, $ret, $ttl);
        }
        return $ret;
    }
    // 有效的值
    else if ($value !== null) {
        return app::cache()->set($key, $value, $ttl);
    }
    return app::cache()->get($key);
}

/**
 * write log
 * @param unknown $mod
 * @param unknown $msg
 * @param string $trace
 */
function LOGS ($mod, $msg, $trace = true, $trace_stack = null) {
    app::log($mod)->write($msg, $trace, $trace_stack);
}

/**
 * URL generate
 */
function URL () {
    return router::url(func_get_args());
}

/**
 * Dump
 */
function DUMP () {
    if (!IS_CLI) {
        header("Content-Type: text/html;charset=utf-8");
    }
    call_user_func_array('var_dump', func_get_args());
    die;
}