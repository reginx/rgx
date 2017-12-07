<?php
namespace re\rgx;
/**
 * APC 缓存驱动类
 * @copyright reginx.com
 * $Id: apc.cache.php 5 2017-07-19 03:44:30Z reginx $
 */
class apc_cache extends cache {
    
    /**
     * 配置信息
     *
     * @var unknown_type
     */
    private $_pre = 'pre_';
    
    /**
     * 架构函数
     *
     * @param unknown_type $conf
     */
    public function __construct ($conf = array()) {
        $this->_pre = isset($conf['pre']) ? $conf['pre'] : 'pre_';
        if (!function_exists('apc_fetch')) {
            throw new exception(LANG('does not support', 'APC '), exception::NOT_EXISTS);
        }
    }
    
    /**
     * 获取值
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function get ($key) {
        $this->count('r');
        return apc_fetch(strtolower($this->_pre . $key));
    }
    
    /**
     * 写入缓存
     *
     * @param String $key
     * @param Mixed $val
     * @param Integer $ttl
     * @return Mixed
     */
    public function set ($key, $val, $ttl = null) {
        $this->count('w');
        return apc_store(strtolower($this->_pre . $key) , $val , $ttl ? $ttl : 86400);
    }

    /**
     * 清除缓存数据
     *
     * @param unknown_type $key
     */
    public function flush ($group = null) {
        // 清除指定前缀缓存
        if (!empty($group)) {
            $list = apc_cache_info('user');
            if (!empty($list['cache_list'])) {
                $key = $this->_pre . $group;
                $len = strlen($key);
                foreach ((array)$list['cache_list'] as $v) {
                    if (substr($v['info'], 0, $len) == $key) {
                        apc_delete($v['info']);
                    }
                }
            }
        }
        // 清除全部
        else {
            apc_clear_cache('user');
        }
    }
    
    
    /**
     * 删除单个缓存
     *
     * @param unknown_type $key
     * @param unknown_type $pre
     * @return unknown
     */
    public function del ($key) {
        return apc_delete(strtolower($this->_pre . $key));
    }
    
    
    /**
     * 运行状态统计
     */
    public function stat(){
        $ret  = array();
        $info = apc_cache_info('user');
        $ret[] = array(LANG('cache type'), 'APC');
        $ret[] = array(LANG('cache entries'), $info['num_entries']);
        $ret[] = array(LANG('cache size'), sprintf('%.2f K' , $info['mem_size']  / 1024));
        $ret[] = array(LANG('uptime'), core::duration($info['start_time']));
        return $ret;
    }
    
}// End Class
?>