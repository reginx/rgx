<?php
namespace re\rgx;
/**
 * 数据库访问接口抽象类
 * @copyright reginx.com
 * $Id: database.class.php 5 2017-07-19 03:44:30Z reginx $
 */
abstract class database {
    
    /**
     * 是否开启 profile
     * @var unknown
     */
    public $profiling = false;
    
    /**
     * 报告
     * @var unknown
     */
    public $profile   = [
        'totals'    => 0,
        'reads'     => 0,
        'writes'    => 0,
        'trace'     => []
    ];

    /**
     * 获取数据操作对象
     *
     * @param array $conf
     * @param boolean $mode
     * @return mixed
     */
    public static final function get_instance ($conf = []) {
        static $dbobj = null;
        if (empty($dbobj)) {
            if (empty($conf)) {
                throw new exception(LANG('config error', 'database'), exception::NOT_EXISTS);
            }
            if (RUN_MODE == 'debug') {
                $file = RGX_PATH . 'extra/db/' . $conf['type'] . '.db.php';
                if (!is_file($file)) {
                    throw new exception(LANG('driver file not found', $conf['type']), exception::NOT_EXISTS);
                }
                include($file);
            }
            $class = __NAMESPACE__ . "\\" . $conf['type'] . '_db';
            $dbobj = new $class($conf[$conf['type']]);
        }
        return $dbobj;
    }
    
    /**
     * select db
     * @param unknown $db
     */
    abstract public function select_db ($db);
    
    /**
     * 查询
     * @param unknown $sql
     */
    abstract public function query ($sql);

    /**
     * 获取单条记录
     * @param unknown $sql
     * @param string $quiet
     * @param string $callback
     */
    abstract public function get ($sql, $quiet = false, $callback = false);

    /**
     * 获取多条记录
     * @param unknown $sql
     * @param unknown $key
     * @param string $quiet
     * @param unknown $callback
     */
    abstract public function get_all ($sql, $key = null, $quiet = false, $callback = false);
    
    /**
     * 获取错误描述
     */
    abstract public function get_error ();
    
    /**
     * 更新
     * @param unknown $sql
     * @param unknown $quiet
     */
    abstract public function update ($sql, $quiet = false);
    
    /**
     * 添加
     * @param unknown $sql
     * @param unknown $quiet
     * @param unknown $mode
     */
    abstract public function insert ($sql, $quiet = false);
    
    /**
     * 删除
     * @param unknown $sql
     * @param string $quiet
     */
    abstract public function delete ($sql, $quiet = false);
    
    /**
     * 获取单条单字段
     * @param unknown $sql
     * @param string $quiet
     */
    abstract public function fetch ($sql, $quiet = false, $callback = false);

    /**
     * 执行SQL
     * @param  [type]  $sql      SQL 语句
     * @param  boolean $quiet    是否为静默模式
     * @param  [type]  $callback 回调函数
     * @return [type]            [description]
     */
    abstract public function exec ($sql, $callback = null);
    
    /**
     * 执行事务
     * @param array $sql
     */
    abstract public function transaction ($sql_list = []);
    
    /**
     * 设置编码
     * @param string $charset
     */
    abstract public function set_charset ($charset = 'utf8');

    /**
     * 获取查询SQL记录
     * @return [type] [description]
     */
    abstract public function get_sql ();
    
    /**
     * 获取sql执行报告
     * @param mixed $id
     */
    abstract public function get_profile ($id = null);
}