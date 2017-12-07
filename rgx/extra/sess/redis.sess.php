<?php
namespace re\rgx;
/**
 * Redis session 实现
 * @copyright reginx.com
 * $Id: redis.sess.php 5 2017-07-19 03:44:30Z reginx $
 */
class redis_sess extends sess {
    
    /**
     * 存储域
     *
     * @var unknown_type
     */
    private $_uuid = null;
    
    /**
     * Cookie key
     *
     * @var unknown_type
     */
    private $_ckey = '';
    
    /**
     * Redis 操作对象
     *
     * @var unknown_type
     */
    private $_redis = null;
    
    /**
     * GC 概率配置
     *
     * @var unknown_type
     */
    private $_gc    = array(1, 100);
    
    /**
     * 默认生存周期
     *
     * @var unknown_type
     */
    private $_ttl   = 1800;
    
    /**
     * 架构函数
     *
     * @param unknown_type $conf
     */
    public function __construct ($conf, $sess_name = null, $sess_id = null) {
        if (!class_exists('\Redis', false)) {
            throw new exception(LANG('does not support', '\Redis '), exception::NOT_EXISTS);
        }
        $this->_gc   = empty($conf['gc']) ? $this->_gc : $conf['gc'];
        $this->_ttl  = $conf['ttl'] ? intval($conf['ttl']) : $this->_ttl;
        $this->_ckey = empty($sess_name) ? session_name() : $sess_name;

        $this->_redis = new \Redis();
        // connect
        if ($this->_redis->connect($conf['host'], $conf['port'])) {
            $this->_redis->select(intval($conf['db']));
        }
        else {
            throw new exception(LANG('config error', 'Redis'), exception::CONFIG_ERROR);
        }

        // 域
        $this->_uuid = empty($sess_id) ? (isset($_COOKIE[$this->_ckey]) ? $_COOKIE[$this->_ckey] : '') : $sess_id;
        if (!preg_match('/^RS\d{1,3}\-[\w\-]+$/i', $this->_uuid)) {
            // 新的 session 
            $this->_uuid = 'RS' . sprintf('%03d', explode('.', $_SERVER['SERVER_ADDR'])[0]) . 
                            md5(app::get_ip() . $_SERVER['HTTP_USER_AGENT']);
        }
        // 更新 session 开始时间
        $this->set('REX_DATE', REQUEST_TIME);
        
        header("Cache-control: private"); // 使用http头控制缓存
        // 更新 cookie ttl , + 30min
        setcookie($this->_ckey, $this->_uuid, REQUEST_TIME + $this->_ttl , "/" , parent::get_domain());
        // GC
        $this->gc();
    }

    /**
     * GC 实现
     *
     */
    public function gc () {
        $date = $this->get('REX_DATE');
        // 已过期
        if (!$date && $date + $this->_ttl < REQUEST_TIME) {
            $this->remove();
            // 记录 GC 任务
            $this->_redis->rPush('gc_task', $this->_uuid);
        }
        // GC
        if (mt_rand(0, $this->_gc[1]) <= $this->_gc[0]) {
            // delete 10 rows each time
            $tasks = (array)$this->_redis->lRange('gc_task', 0, 20);
            foreach ($tasks as $v) {
                $date = $this->_redis->hGet($v, 'REX_DATE');
                if (!$date || $date + $this->_ttl < REQUEST_TIME) {
                    // rm data
                    $this->_redis->del($v);
                    // rm task
                    $this->_redis->lRem('gc_task', $v, 0);
                }
            }
        }
        $this->set('REX_DATE', REQUEST_TIME);
    }
    
    /**
     * 获取 session ID
     *
     * @return unknown
     */
    public function sess_id () {
        return $this->_uuid;
    }
    
    /**
     * 获取当前配置信息
     *
     * @return unknown
     */
    public function get_config () {
        return array(
            'id'        => $this->_ckey,
            'name'      => $this->_uuid,
            'expires'   => 1800
        );
    }
    
    /**
     * 获取项目值
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function get ($key) {
        $ret = $this->_redis->hExists($this->_uuid, $key) ? $this->_redis->hGet($this->_uuid, $key) : null;
        if (!empty($ret)) {
            if (in_array(substr($ret, 0, 2), array('a:', 's:', 'i:', 'd:', 'N;', 'b:', 'O:'))
                    && in_array(substr($ret, -1), array(';', '}'))) {
                $ret = unserialize($ret);
            }
        }
        return $ret;
    }
    
    /**
     * 获取项目值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     * @return unknown
     */
    public function set ($key, $value) {
        if (is_array($value)) {
            $value = serialize($value);
        }
        return $this->_redis->hSet($this->_uuid, $key, $value);
    }
    
    /**
     * 删除项目值
     *
     * @param unknown_type $key
     * @param unknown_type $value
     * @return unknown
     */
    public function del ($key) {
        return $this->_redis->hDel($this->_uuid, $key);
    }
    
    /**
     * 验证是否存在某项目
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function exists ($key) {
        return $this->_redis->hExists($this->_uuid, $key);
    }
    
    /**
     * 销毁回话
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function remove () {
        // 设置 cookie 过期
        unset($_COOKIE[$this->_ckey]);
        setcookie($this->_ckey, null, -1);
        $this->_redis->lRem('gc_task', $this->_uuid, 0);
        return $this->_redis->del($this->_uuid);
    }
}