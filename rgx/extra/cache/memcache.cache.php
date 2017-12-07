<?php
namespace re\rgx;
/**
 * Memcache
 * @copyright reginx.com
 * $Id: memcache.cache.php 5 2017-07-19 03:44:30Z reginx $
 */
class mem_cache extends cache {

    /**
     * 当前连接的 Server ID
     *
     * @var unknown_type
     */
    private $_serverid = null;

    /**
     * 配置信息
     *
     * @var unknown_type
     */
    private $_conf = array();

    /**
     * 当前配置信息
     *
     * @var unknown_type
     */
    // private $_serv = array();
    
    private $_pre = 'pre_';

    /**
     * 当前Memcache 对象
     *
     * @var unknown_type
     */
    private $_mobj = null;

    /**
     * 架构函数
     *
     * @param unknown_type $opts
     */
    public function __construct ($opts) {
        $this->_conf = &$opts;
        $this->_serverid = mt_rand(0, count($this->_conf) - 1);
        $this->_serv = $this->_conf[$this->_serverid];
        $this->_pre  = isset($this->_conf[$this->_serverid]['pre']) ? $this->_conf[$this->_serverid]['pre'] : $this->_pre;
        if (!class_exists('Memcache', false)) {
            throw new exception(LANG('does not support', '\Memcache '), exception::NOT_EXISTS);
        }
        if (empty($this->_mobj)) {
            $this->_mobj = new Memcache();
        }
        if (count($this->_conf) == 1) {
            // 单台 
            if (!$this->_mobj->connect($this->_serv['host'], $this->_serv['port'])) {
            throw new exception(LANG('config error', '\Memcache'), exception::CONFIG_ERROR);
            }
        }
        else {
            // 集群
            foreach ($this->_conf as $v) {
                $p = (!isset($v['p']) || $v['p']) ? true : false;
                $w = intval(isset($v['w']) ? $v['w'] : (100 / count($this->_conf)));
                $this->_mobj->addServer($v["host"], $v["port"], $p, $w);
            }
        }
    }

    /**
     * 存入值
     *
     * @param unknown_type $key
     * @param unknown_type $val
     * @param unknown_type $ttl
     */
    public function set ($key, $val, $ttl = 0) {
        $this->count('w');
        // 默认不使用 zlib压缩
        $this->_mobj->set($this->_getkey($key), $val, 0, intval($ttl));
    }

    /**
     * 获取值
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function get ($key) {
        $this->count('r');
        return $this->_mobj->get($this->_getkey($key));
    }

    /**
     * 清除全部缓存数据
     *
     * @param unknown_type $key
     */
    public function flush ($group = null) {
        $this->_mobj->flush();
    }

    /**
     * 删除单个缓存
     *
     * @param unknown_type $key
     * @param unknown_type $pre
     * @return unknown
     */
    public function del ($key) {
        return $this->_mobj->delete($this->_getkey($key));
    }

    /**
     * 运行状态统计
     */
    public function stat () {
        $stat = $this->_mobj->getStats();
        $ret   = array();
        $ret[] = array('命中率' , sprintf('%.2f', $stat["get_hits"] / ($stat["get_misses"] + $stat["get_hits"])));
        $ret[] = array('丢失率' , sprintf('%.2f', $stat["get_misses"] / ($stat["get_misses"] + $stat["get_hits"])));
        $ret[] = array('已读数' , sprintf('%.2f K', $stat["bytes_read"] / 1024 ));
        $ret[] = array('已写数' , sprintf('%.2f K', $stat["bytes_written"] / 1024 ));
        $ret[] = array('总内存' , sprintf('%.2f M', $stat["limit_maxbytes"] / 1024 / 1024 ));
        return $ret;
    }
    
    /**
     * 获取 key
     *
     * @param unknown_type $str
     * @return unknown
     */
    private function _getkey ($str) {
        return $this->_pre . $str;
    }

    /**
     * 析构函数
     */
    public function __destruct () {
        if ($this->_mobj) {
            $this->_mobj->close();
        }
    }
}