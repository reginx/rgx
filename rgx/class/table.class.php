<?php
namespace re\rgx;
/**
 * 表模型类
 *
 * @author rgx
 * @copyright reginx.com
 * $Id: table.class.php 805 2017-12-03 03:59:43Z reginx $
 */

class table extends rgx {

    /**
     * 低优先级
     */
    const SQL_LOW = 'low_priority';

    /**
     * 高优先级
     */
    const SQL_HIGH = 'high_priority';

    /**
     * 延迟
     */
    const SQL_DELAY = 'delayed';

    /**
     * insert action
     */
    const OP_INSERT = 1;

    /**
     * update action
     */
    const OP_UPDATE = 2;

    /**
     * replace action
     */
    const OP_REPLACE = 3;

    /**
     * where and
     */
    const LOGIC_AND = 1;

    /**
     * where or
     */
    const LOGIC_OR  = 2;

    /**
     * 表 DB 配置
     *
     * @var unknown_type
     */
    public $dbconf = [];

    /**
     * 表数据
     *
     * @var unknown_type
     */
    protected $data = [];

    /**
     * 字段输入验证
     *
     * @var unknown_type
     */
    public $validate = [];

    /**
     * 字段默认
     *
     * @var unknown_type
     */
    public $defaults = [];

    /**
     * 唯一性约束
     * @var array
     */
    public $unique_check = [];

    /**
     * 约束检测失败提示语
     * @var array
     */
    public $unique_msg = [];

    /**
     * 字段过滤规则
     *
     * @var unknown_type
     */
    public $filter = [];

    /**
     * 字段信息
     * @var unknown
     */
    protected $_fields = [];

    /**
     * 主键信息
     * @var unknown
     */
    protected $_primary_key = [];

    /**
     * 默认编码
     * @var unknown
     */
    protected $_charset = 'utf8';

    /**
     * 数据验证错误信息
     *
     * @var unknown_type
     */
    private $_error_msg = [];

    /**
     * 数据库连接对象
     *
     * @var unknown_type
     */
    private $_dbobj = null;

    /**
     * sql语句构造数组
     *
     * @var unknown_type
     */
    private $_sql = [];

    /**
     * 表模型配置信息
     *
     * @var unknown_type
     */
    public $_conf = [];

    /**
     * 是否保留上一次查询的条件
     *
     * @var unknown_type
     */
    private $_keep = FALSE;

    /**
     * 执行一次sql之后, 是否清空当前对象data
     * 生效次数一次
     *
     * @var unknown_type
     */
    private $_reset = true;

    /**
     * 数组键字段
     *
     * @var unknown_type
     */
    private $_akey = null;

    /**
     * 是否使用静默模式
     *
     * @var unknown_type
     */
    private $_quiet = false;

    /**
     * 回调
     * @var unknown
     */
    private $_map = false;

    /**
     * 是否通过 load 方式装载
     * @var boolean
     */
    private $_isload = false;


    /**
     * 构造函数
     * 支持单表定义配置
     */
    public function __construct ($class = null) {
        $config = CFG('db');
        $this->_dbobj = app::db();
        $this->_dbobj->set_charset($this->_charset);
        if (empty($this->_conf)) {
            $this->_conf['pre'] = $config['pre'];
        }
        $class = empty($class) ? get_class($this) : $class;
        if ($class != __CLASS__) {
            $this->_conf['table'] = str_replace('re\\rgx\\', '', substr($class, 0, -6));
            $this->_conf['table_name'] = '`' . $this->_conf['pre'] . $this->_conf['table'] . '`';
        }
    }

    /**
     * clone 对象属性初始化
     * @return [type] [description]
     */
    public function __clone () {
        $this->_dbobj = clone $this->_dbobj;
        $this->data = [];
        $this->_sql = [];
        $this->_error_msg = [];
        $this->_keep    = false;
        $this->_reset   = true;
        $this->_akey    = null;
        $this->_map     = false;
        $this->_isload  = false;
    }

    /**
     * 获取表名称
     *
     * @return unknown
     */
    public function get_name () {
        return $this->_conf['table_name'];
    }

    /**
     * 获取所有表
     *
     * @return unknown
     */
    public function get_tables () {
        return $this->_dbobj->get_tables($this->_conf['pre'], $this->get_quiet());
    }

    /**
     * 获取 SQL 查询记录
     * @return [type] [description]
     */
    public function get_sqls () {
        return $this->_dbobj->get_sql();
    }

    /**
     * 设置 _reset
     *
     * @param unknown_type $bool
     */
    public function reset ($bool = false) {
        $this->_reset = (bool) $bool;
        return $this;
    }


    /**
     * 获取字段信息
     *
     * @return array
     */
    public function get_fields () {
        return $this->_fields;
    }

    /**
     * where
     * @param  [type] $condition 条件语句
     * @param  [type] $logic_op
     * @return [type]
     */
    public function where ($condition, $logic_op = self::LOGIC_AND) {
        if (!empty($condition) && is_array($condition)) {
            $is_not = false;
            if (($logic_op & ~self::LOGIC_AND) & ($logic_op & ~self::LOGIC_OR)) {
                $is_not = true;
                $logic_op = ~$logic_op;
            }
            $is_and = $logic_op & self::LOGIC_AND;

            foreach ((array)$condition as $k => $v) {
                if (is_array($v)) {
                    if ($this->_fields[$k]['type'] == 'int' || $this->_fields[$k]['type'] == 'float') {
                        $tmp[] = "{$k} " . ($is_not ? 'not' : '') . " in (" . join(', ', $v) . ")";
                    }
                    else {
                        $tmp[] = "{$k} " . ($is_not ? 'not' : '') . " in (" . join(', ', array_map(function ($value) {
                            return "'{$value}'";
                        }, $v)) . ")";
                    }
                }
                else if (is_numeric($k)) {
                    $tmp[] = $v;
                    continue;
                }
                // 返回条数
                else if (is_callable($v)) {
                    $tmp[] = $v($k);
                }
                else if ($k == '__limit__') {
                    $this->limit($v);
                }
                // 检测字段是否存在 (排除 abc_table.name 格式)
                else if (!isset($this->_fields[$k]) && strpos($k, '.') === false) {
                    throw new exception(LANG('not exists', "field {$this->_conf['table_name']}.{$k}"), exception::NOT_EXISTS);
                }
                // 'id' => 1
                else {
                    if ($this->_fields[$k]['type'] == 'int' || $this->_fields[$k]['type'] == 'float') {
                        $tmp[] = "{$k} " . ($is_not ? '!=' : '=') . " {$v}";
                    }
                    else {
                        $tmp[] = "{$k} " . ($is_not ? '!=' : '=') . " '{$v}'";
                    }
                }
            }
            $this->_sql['where'][] = join($is_and ? ' and ' : ' or ', $tmp);
        }
        else {
            $this->_sql['where'][] = $condition;
        }
        return $this;
    }

    /**
     * set 字段值
     *
     * @param unknown_type $k
     * @param unknown_type $v
     */
    public function set ($k, $v = null) {
        $k = is_array($k) ? $k : [$k => $v];
        foreach ($k as $field => $value) {
            // 检测字段是否存在
            if (!isset($this->_fields[$field])) {
                throw new exception(LANG('not exists', "field {$this->_conf['table_name']}.{$field}"), exception::NOT_EXISTS);
            }
            $this->data[$field] = is_callable($value) ? $value : addslashes(stripslashes($value));
        }
        return $this;
    }

    /**
     * limit
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function limit ($str) {
        $str = trim($str);
        if ($str != '') {
            $tmp = explode(',', $str);
            $this->_sql['limit'] = array_map('intval', $tmp);
        }
        return $this;
    }

    /**
     * union limit
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function ulimit ($str) {
        $str = trim($str);
        if ($str != '') {
            $tmp = explode(',', $str);
            $this->_sql['ulimit'] = array_map('intval', $tmp);
        }
        return $this;
    }

    /**
     * 结果集处理回调设置
     * @param unknown $callback
     * @throws exception
     */
    public function map ($callback) {
        if (!is_callable($callback)) {
            throw new exception(LANG('invalid callback'), exception::INVALID_CALLBACK);
        }
        $this->_map = $callback;
        return $this;
    }

    /**
     * 获取一条记录
     *
     * @return unknown
     */
    public function get ($where = []) {
        $this->_sql['limit'] = [1];
        if (is_numeric($where)) {
            if (empty($this->_primary_key['key']) || !$this->_primary_key['inc']) {
                throw new exception(LANG('primary key does not exist', $this->get_name()), exception::TABLE_ERROR);
            }
            $this->where("{$this->_primary_key['key']} = " . intval($where));
            $where = null;
        }
        else if (!empty($where) && is_array($where)) {
            $this->where($where);
            $where = null;
        }
        $ret = $this->_dbobj->get($this->parse_sql('select'), $this->get_quiet(), is_callable($this->_map) ? $this->_map : false);
        $this->_map = false;
        return $ret;
    }


    /**
     * 获取一条记录
     *
     * @return unknown
     */
    public function get_row ($sql = null, $param = array()) {
        if (!empty($sql) && is_array($sql)) {
            $this->where($sql);
            $sql = null;
        }
        $ret = $this->_dbobj->get(
                empty($sql) ? $this->parse_sql('select') : $this->_format($sql, $param),
                $this->get_akey(), $this->get_quiet(), is_callable($this->_map) ? $this->_map : false);
        $this->_map = false;
        return $ret;
    }

    /**
     * 获取数据集合
     *
     * @return unknown
     */
    public function get_all ($sql = null, $param = array()) {
        if (!empty($sql) && is_array($sql)) {
            $this->where($sql);
            $sql = null;
        }
        $ret = $this->_dbobj->get_all(
                empty($sql) ? $this->parse_sql('select') : $this->_format($sql, $param),
                $this->get_akey(), $this->get_quiet(), is_callable($this->_map) ? $this->_map : false);
        $this->_map = false;
        return $ret;
    }

    /**
     * 执行sql
     *
     * @return unknown
     */
    public function exec ($sql, $param = array()) {
        return $this->_dbobj->query($this->_format($sql, $param), $this->get_quiet());
    }

    /**
     * 执行 unbuf sql
     *
     * @return unknown
     */
    public function unbufquery ($sql, $param = array()) {
        return $this->_dbobj->unbufquery($this->_format($sql, $param), $this->get_quiet());
    }

    /**
     * 格式化sql
     *
     * @return unknown
     */
    private function _format ($sql, $param = array()) {
        $sql = preg_replace('/(\w+?)_table/i', $this->_conf['pre'] . '\\1', $sql);
        if (!empty($param)) {
            $m = array();
            preg_match_all('/\s*\%[a-zA-Z]\s*/i', $sql, $m);
            if (count($m[0]) > count($param)) {
                LOGS('db', [LANG('invalid format', 'table format'), $sql], true);
                throw new exception(LANG('invalid format', 'table format' . (RUN_MODE == 'debug' ? $sql : '')),
                        exception::TABLE_ERROR);
            }
            $sql = vsprintf($sql, $param);
        }
        return $sql;
    }

    /**
     * 输出sql
     *
     * @param unknown_type $sql
     */
    public function get_sql ($act = 'select') {
        if ($this->_error_msg) {
            return $this->_error_msg;
        }
        return $this->parse_sql($act);
    }

    /**
     * 事务
     * @param  array  $sql_list [description]
     * @return [type]           [description]
     */
    public function transaction ($sql_list = []) {
        return $this->_dbobj->transaction(array_map([$this, '_format'], $sql_list));
    }

    /**
     * sql 优先级
     *
     * @param unknown_type $pri
     */
    public function priority ($pri = self::SQL_HIGH) {
        $this->_sql['priority'] = $pri;
        return $this;
    }

    /**
     * 设置返回数据数组的索引键
     *
     * @param unknown_type $k
     * @return unknown
     */
    public function akey ($k = null) {
        $this->_akey = empty($k) ? (!is_array($this->_primary_key['key']) ? $this->_primary_key['key'] : null) : $k;
        return $this;
    }


    /**
     * 返回数据数组的索引键
     *
     * @return unknown
     */
    private function get_akey () {
        $akey = $this->_akey;
        $this->_akey = null;
        return $akey;
    }

    /**
     * union all
     *
     * @param tab $obj
     * @return mixed
     */
    public function union_all ($obj) {
        return $this->union($obj, 1);
    }

    /**
     * union distinct
     *
     * @param unknown_type $obj
     * @return unknown
     */
    public function union_distinct ($obj) {
        return $this->union($obj, 2);
    }

    /**
     * union
     *
     * @param tab $obj
     * @param boolean $all
     * @return mixed
     */
    public function union ($obj = null, $ctype = false) {
        if (!empty($obj)) {
            if (!is_array($obj)) {
                $obj = [$obj];
            }
            $ctype = $ctype ? ($ctype == '1' ? 'all' : 'distinct') : '';
            $sql = '( ' . $this->parse_sql('select') . ') ';
            foreach ($obj as $v) {
                $sql .= ' union ' . $ctype . '  ( ' . $v->parse_sql('select') . ' ) ';
            }
            // order
            if (!empty($this->_sql['uorder'])) {
                $sql .= ' order by ' . implode(' , ', $this->_sql['uorder']);
            }
            // limit
            if (!empty($this->_sql['ulimit'])) {
                $sql .= ' limit  ' . implode(' , ', $this->_sql['ulimit']);
            }
            $this->_sql = [];

            $ret = $this->_dbobj->get_all($sql, $this->get_akey(),
                        $this->get_quiet(), is_callable($this->_map) ? $this->_map : null);
            $this->_map = null;

            return $ret;
        }
        return [];
    }

    /**
     * 解析 where
     * @param  array  $where [description]
     * @return [type]        [description]
     */
    protected function parse_where ($where = []) {
        $ret = [];
        foreach ((array)$where as $str) {
            if (empty($str)) {
                continue;
            }
            $str = preg_replace('/(\w+?)_table/i', $this->_conf['pre'] . '\\1', $str);
            $instr = $in = $fun = $funstr = $blocks = array();
            // in , not in
            preg_match_all('/([\w\.]+\s+(not)?\s+in\s+\(.+?\))/i', $str, $in);
            foreach ($in[1] as $k => $v) {
                $str = str_replace($v, '#####' . $k . '#####', $str);
                $tmp = preg_split('/\s+(not\s*)?in[\s*|\(]/i', $v);
                $instr[$k] = str_replace(trim($tmp[0]), $this->escape($tmp[0]), $v);
            }
            // exists , not exists
            preg_match_all('/((?:not\s*)?exists\s*\(.+?\))/i', $str, $fun);
            foreach ($fun[1] as $k => $v) {
                $str = str_replace($v, '@@@@@' . $k . '@@@@@', $str);
                $funstr[$k] = preg_replace('/(\w+?)_table/i', $this->_conf['pre'] . '\\1', $v);
            }
            // 会改动查询的原始条件
            //$str = str_replace(')', ') ', str_replace('(', ' (', $str));
            preg_match_all(
                    '/([0-9a-zA-Z\.\_\#]+)\s*(\>\=|\<\=|\!\=|\=|\>|\<|\slike\s|\sor\s|\sand\s)\s*([^\s]+)/ui',
                    $str, $blocks);
            for ($i = 0, $max = count($blocks[0]); $i < $max; $i ++) {
                $temp = $this->escape($blocks[1][$i]) . ' ' . $blocks[2][$i] . ' ';
                if (strpos($blocks[3][$i], '\'') === false && !is_numeric($blocks[3][$i])
                        && (strpos($blocks[3][$i], '"') !== false || strpos($blocks[3][$i], '.') !== false)) {
                    $temp .= $this->escape($blocks[3][$i]);
                }
                else {
                    $temp .= $blocks[3][$i];
                }
                $str = preg_replace(
                        '/(\s|^)' . preg_quote($blocks[0][$i], '/') . '(\s|$)/i',
                        ' ' . $temp . ' ', $str);
            }
            if (!empty($instr)) {
                foreach ($instr as $k => $v) {
                    $str = str_replace('#####' . $k . '#####', $v, $str);
                }
                $instr = null;
            }
            if (!empty($funstr)) {
                foreach ($funstr as $k => $v) {
                    $str = str_replace('@@@@@' . $k . '@@@@@', $v, $str);
                }
                $funstr = null;
            }
            $ret[] = $str;
        }
        return $ret ? (' and (' . join(') and (' , $ret) . ') ') : ' ';
    }


    /**
     * 解析sql
     *
     * @return unknown
     */
    protected function parse_sql ($act = false) {
        $sep = RUN_MODE == 'debug' ? " \n" : ' ';
        // action
        $this->_sql['act'] = $act ? $act : $this->_sql['act'];
        $sql = $this->_sql['act'] . ' ';

        // select
        if ($this->_sql['act'] == 'select') {
            // priority
            if (isset($this->_sql['priority']) &&
                     $this->_sql['priority'] == self::SQL_HIGH) {
                $sql .= $this->_sql['priority'] . ' ';
            }
            // fields
            if (isset($this->_sql['fields']) && ! empty($this->_sql['fields'])) {
                $sql .= implode(' , ', $this->_sql['fields']);
            }
            else {
                $sql .= ' * ';
            }
            $sql .= $sep;
            if (! $this->_keep) {
                $this->_sql['fields'] = null;
            }

            $sql .= 'from ' . $this->_conf['table_name'] . $sep;

            // join
            if (! empty($this->_sql['join'])) {
                $sql .= implode(" \n", $this->_sql['join']);
                if (! $this->_keep) {
                    $this->_sql['join'] = null;
                }
            }
            $sql .= $sep;
            // where
            if (! empty($this->_sql['where'])) {
                $sql .= 'where 1 = 1' . $sep . $this->parse_where($this->_sql['where']);
                if (!$this->_keep) {
                    $this->_sql['where'] = null;
                }
            }
            // group
            if (!empty($this->_sql['group'])) {
                $sql .= 'group by ' . implode(' , ', $this->_sql['group']) . $sep;
                if (! $this->_keep) {
                    $this->_sql['group'] = null;
                }
            }
            // order
            if (!empty($this->_sql['order'])) {
                $sql .= 'order by ' . implode(' , ', $this->_sql['order']) . $sep;
                if ($this->_keep) {
                    $this->_sql['order'] = null;
                }
            }
            // limit
            if (!empty($this->_sql['limit'])) {
                $sql .= 'limit  ' . implode(' , ', $this->_sql['limit']) . $sep;
                if ($this->_keep) {
                    $this->_sql['limit'] = null;
                }
            }
        }
        // update
        else if ($this->_sql['act'] == 'update') {
            // priorty
            if (isset($this->_sql['priority']) &&
                     $this->_sql['priority'] == self::SQL_LOW) {
                $sql .= $this->_sql['priority'] . ' ';
            }
            // ignore
            if (isset($this->_sql['ignore'])) {
                $sql .= ' ignore ';
            }
            $sql .= $this->_conf['table_name'] . ' set ' . $sep;
            // set 数据
            if (!empty($this->data) && is_array($this->data)) {
                // 是否存在自增主键
                $has_inc_prikey = !is_array($this->_primary_key['key']) && $this->_primary_key['inc'];
                foreach ($this->data as $k => $v) {
                    // 自增主键
                    if ($has_inc_prikey && $k == $this->_primary_key['key']) {
                        $this->where("{$k} = {$v}");
                        unset($this->data[$k]);
                    }
                    // 闭包
                    else if (is_callable($v)) {
                        $this->data[$k] = $this->escape($k) . " = " . $v($this->escape($k));
                    }
                    else if ($this->_fields[$k] == 'int' || $this->_fields[$k] == 'float') {
                        $this->data[$k] = $this->escape($k) . " = {$v}";
                    }
                    else {
                        $this->data[$k] = $this->escape($k) . " = '{$v}'";
                    }
                }
                $sql .= implode(', ' . $sep, $this->data);
                if ($this->_reset) {
                    $this->data = [];
                }
                else {
                    $this->_reset = true;
                }
            }
            // 无数据 抛出异常信息
            else {
                throw new exception(LANG('no data', "{$this->_conf['table']}_table->parse_sql(update)"),
                                exception::TABLE_ERROR, 'table');
            }
            // where
            if (!empty($this->_sql['where'])) {
                $sql .= ' where 1 = 1 ' . $sep . $this->parse_where($this->_sql['where']);
                $this->_sql['where'] = null;
                unset($this->_sql['where']);
            }
        }
        // delete
        else if ($this->_sql['act'] == 'delete') {
            $sql .= ' from ' . $this->_conf['table_name'] . $sep;
            // where
            if (!empty($this->_sql['where'])) {
                $sql .= 'where 1 = 1 ' . $sep . $this->parse_where($this->_sql['where']);
                $this->_sql['where'] = null;
                unset($this->_sql['where']);
            }
        }
        // insert && replace
        else if ($this->_sql['act'] == 'insert' || $this->_sql['act'] == 'replace') {
            // insert priority
            if ($this->_sql['act'] == 'insert') {
                $sql .= (isset($this->_sql['priority']) ? $this->_sql['priority'] : '') . ' ';
                if (isset($this->_sql['ignore'])) {
                    $sql .= ' ignore ';
                }
            }
            // replace priority
            else if (isset($this->_sql['priority']) && $this->_sql['priority'] != self::SQL_HIGH) {
                $sql .= $this->_sql['priority'] . ' ';
            }
            $sql .= ' into ';
            $sql .= $this->_conf['table_name'] . '( ';
            $keys = $vals = array();
            // form data
            if (! empty($this->data) && is_array($this->data)) {
                foreach ($this->data as $k => $v) {
                    $keys[] = $this->escape($k);
                    $vals[] = "'" . $v . "'";
                }
            }
            else {
                throw new exception(LANG('data to be processed is empty', $this->_conf['table']), 'DTBPIE', 1);
            }
            $sql .= implode(', ', $keys) . ') ' . $sep . ' values( ';
            $sql .= implode(', ', $vals) . ') ';
            // ODKU
            if ($this->_sql['act'] == 'insert' && !empty($this->_sql['odku'])) {
                $sql .= ' on duplicate key update ' . $this->_sql['odku'];
            }
        }// insert && replace end

        // 是否保留当前装载的from data
        if ($this->_reset) {
            $this->data = [];
        }
        // 只能保持一次 , 下次执行会被释放掉
        else {
            $this->_reset = true;
        }
        // 是否保留当前已设置的sql属性条件
        if (!$this->_keep) {
            $this->_sql = [];
        }
        // 只能保持一次, 下次执行将会被释放掉
        else {
            $this->_keep = FALSE;
        }
        $this->_isload = false;

        return $this->_format($sql);
    }

    /**
     * ignore for insert
     */
    public function ignore () {
        $this->_sql['ignore'] = 1;
        return $this;
    }

    /**
     * ON DUPLICATE KEY UPDATE
     */
    public function odku ($str) {
        if (is_string($str)) {
            $this->_sql['odku'] = $str;
        }
        else if (is_array($str)) {
            $tmp = array();
            foreach ($str as $k => $v) {
                if (isset($this->_conf['fields']['list'][$k])) {
                    $tmp[] = $k . '=' . "'{$v}'";
                }
            }
            $this->_sql['odku'] = join(',', $tmp);
        }
        return $this;
    }

    /**
     * 保存
     * @return boolean
     */
    public function save () {
        $action = 'insert';
        if (!empty($this->_sql['where'])) {
            $action = 'update';
        }
        // 表结构中有自增整型主键
        else if (!is_array($this->_primary_key['key']) && $this->_primary_key['inc']) {
            $pri_key = $this->_primary_key['key'];
            if (isset($this->data[$pri_key]) && $this->data[$pri_key]) {
                $action = 'update';
            }
        }
        // replace
        else {
            $action = 'replace';
        }
        return $this->$action();
    }


    /**
     * 执行sql
     *
     * @return unknown
     */
    public function update ($sql = null, $param = []) {
        if (!empty($sql) && is_array($sql)) {
            foreach ((array)$sql as $k => $v) {
                if (isset($this->_fields[$k])) {
                    // 当遇到主键时, 作为更新的条件. 而非内容, (不适用于联合主键)
                    if (!is_array($this->_primary_key['key']) && $this->_primary_key['key'] == $k) {
                        $this->where([$k => $v]);
                        // data 记录主键值
                        //$this->data[$k] = $v;
                        continue;
                    }
                    $this->set("{$k}", $v);
                }
            }
            $sql = null;
        }
        if (empty($sql)) {
            // 若通过 load 方式载入的数据, 则跳过验证
            if ($this->_isload) {
                $ret = $this->_dbobj->update($this->parse_sql('update'), $this->get_quiet());
            }
            else if ($this->_validate(self::OP_UPDATE)) {
                $ret = $this->_dbobj->update($this->parse_sql('update'), $this->get_quiet());
            }
            else {
                $ret = [
                    'code'  => 1,
                    'rows'  => 0,
                    'error' => $this->get_error()
                ];
            }
        }
        else {
            $ret = $this->_dbobj->update($this->_format($sql, $param), $this->get_quiet());
        }
        return  $ret;
    }

    /**
     * 删除操作
     *
     * @return unknown
     */
    public function delete ($sql = null, $param = array()) {
        if (is_array($sql)) {
            foreach ((array)$sql as $k => $v) {
                if (isset($this->_fields[$k])) {
                    if (is_callable($v)) {
                        $this->where($v($k));
                    }
                    else if ($this->_fields[$k]['type'] == 'int' || $this->_fields[$k]['type'] == 'float') {
                        if (is_array($v)) {
                            array_map(function ($val) {
                                if (!is_numeric($val)) {
                                    throw new exception(LANG('invalid data type', "field {$k} => {$val}"),
                                            exception::INVALID_TYPE, 'table');
                                }
                            }, $v);
                            $this->where("{$k} in (" . join(',', $v) . ")");
                        }
                        else if (!is_numeric($v)) {
                            throw new exception(LANG('invalid data type', "field {$k} => {$v}"), exception::INVALID_TYPE, 'table');
                        }
                        else {
                            $this->where("{$k} = {$v}");
                        }
                    }
                    else if ($this->_fields[$k]['type'] == 'char') {
                        if (is_array($v)) {
                            $this->where("{$k} in ('" . join("','", $v) . "')");
                        }
                        else {
                            $this->where("{$k} = '{$v}'");
                        }
                    }
                }
            }
            $sql = null;
        }
        return  $this->_dbobj->delete(empty($sql) ?
                $this->parse_sql('delete') : $this->_format($sql, $param),
                $this->get_quiet()
        );
    }

    /**
     * 静默模式
     *
     * @param unknown_type $value
     * @return unknown
     */
    public function quiet ($value = true) {
        $this->_quiet = $value ? true : false;
        return $this;
    }

    /**
     * 获取静默状态
     *
     * @return unknown
     */
    private function get_quiet () {
        $ret = $this->_quiet;
        $this->_quiet = false;
        return $ret;
    }

    /**
     * 新增
     * @return boolean
     */
    public function insert ($sql = null, $param = array()) {
        $ret = [
            'code'      => 1,
            'rows'      => 0,
            'error'     => 'unkown'
        ];
        if (is_array($sql)) {
            foreach ((array)$sql as $k => $v) {
                if (isset($this->_fields[$k])) {
                    $this->data[$k] = $v;
                }
            }
            $sql = null;
        }
        // 使用串行操作生成sql
        if (empty($sql)) {
            if ($this->_isload) {
                $ret = $this->_dbobj->insert($this->parse_sql('insert'), $this->get_quiet());
            }
            else if ($this->_validate(self::OP_INSERT)) {
                $ret = $this->_dbobj->insert($this->parse_sql('insert'), $this->get_quiet());
            }
            else {
                $ret = [
                    'code'  => 1,
                    'rows'  => 0,
                    'error' => $this->get_error()
                ];
            }
        }
        // 直接执行 sql
        else {
            $ret = $this->_dbobj->insert($this->_format($sql, $param), $this->get_quiet());
        }

        return $ret;
    }

    /**
     * replace 操作
     *
     * @return unknown
     */
    public function replace ($sql = null, $param = []) {
        $ret = [
            'code'      => 1,
            'rows'      => 0,
            'error'     => 'unkown'
        ];
        if (is_array($sql)) {
            foreach ((array)$sql as $k => $v) {
                if (isset($this->_fields[$k])) {
                    $this->data[$k] = $v;
                }
            }
            $sql = null;
        }
        if (empty($sql)) {
            if ($this->_isload) {
                $ret = $this->_dbobj->update($this->parse_sql('replace'), $this->get_quiet());
            }
            else if ($this->_validate(self::OP_REPLACE)) {
                $ret = $this->_dbobj->update($this->parse_sql('replace'), $this->get_quiet());
            }
            else {
                $ret = [
                    'code'  => 1,
                    'rows'  => 0,
                    'error' => $this->get_error()
                ];
            }
        }
        else {
            $ret = $this->_dbobj->update($this->_format($sql, $param), $this->get_quiet());
        }

        return $ret;
    }

    /**
     * 统计
     * @param unknown $fields
     * @param string $mode
     */
    public function count ($fields = null) {
        // 字段, 对于MySQL应优先使用建立了辅助索引的字段, 需要代码控制.
        if (!empty($fields)) {
            $this->fields('count( ' . $fields . ' ) as nums');
        }
        // 主键 MySQL Innodb 应避免使用主键统计
        else if (!empty($this->_conf['fields']['prikey'])) {
            $this->fields('count( ' . $this->_conf['fields']['prikey'] . ' ) as nums');
        }
        // 常量
        else {
            $this->fields('count( 1 ) as nums');
        }

        return (int)$this->_dbobj->fetch($this->parse_sql('select'), $this->get_quiet(), function () {
            $this->clear('fields');
        });
    }

    /**
     * 获取单记录的单字段
     * @param unknown $fields
     * @param string $mode
     */
    public function fetch ($sql = null) {
        if (!empty($sql) && is_array($sql)) {
            $this->where($sql);
            $sql = null;
        }
        return $this->_dbobj->fetch($sql ? $this->_format($sql): $this->parse_sql('select'), $this->get_quiet());
    }

    /**
     * 调用存储过程或函数
     * @param unknown $fields
     * @param string $mode
     */
    public function call ($func) {
        if (empty($func) || !is_string($func)) {
            throw new Exception(LANG('invalid name of procedure or function', $func), exception::INVALID_TYPE, true);
        }
        return $this->_dbobj->call("call {$func}");
    }

    /**
     * 保存本次sql值
     */
    public function keep () {
        $this->_keep = true;
        return $this;
    }

    /**
     * 联合查询
     * join('left', 'foo_table', [
     *     'foo_table.id'   => 1,
     *     'name'       => function ($k) {
     *         return " > 10";
     *     }
     * ])
     * @param unknown_type $ctype
     * @param unknown_type $tab
     * @param unknown_type $key
     * @param unknown_type $fkey
     * @param unknown_type $iseq
     * @return tab
     */
    private function join ($ctype = 'left', $tab, $key, $fkey = null, $iseq = true) {
        if (!isset($this->_sql['tabs'])) {
            $this->_sql['tabs'] = [];
        }
        if (!empty($tab) && !empty($key)) {
            $key = is_array($key) ? $key : [$key => $fkey];
            $alias = null;
            if (strpos($tab, ' as ') !== false) {
                list($tab, $alias)   = explode(' as ', $tab, 2);
                $this->_sql['tabs'][$alias] = $tab;
            }
            $exprs = [];
            foreach ($key as $k => $v) {
                $k = str_replace($tab . '.', '', trim($k));
                if (strpos($k, '.') === false) {
                    $k = $this->escape($k);
                }
                if (is_callable($v)) {
                    $exprs[] = "{$k} " . $v($k);
                }
                else if (is_integer($v)) {
                    $exprs[] = "{$k} = {$v}";
                }
                else {
                    if (strpos($v, '.') === false) {
                        $v = $this->escape((empty($alias) ? $tab : $alias) . '.' . $v);
                    }
                    $exprs[] = "{$k} = {$v}";
                }
            }
            $str = $ctype . " join " . $this->table($tab) . " " . (empty($alias) ? '' : "as $alias ")
                    . ' on ( ' . join(' and ', $exprs) . ' )';
            $this->_sql['join'][] = $str;
        }
        return $this;
    }

    /**
     * left join
     *
     * @param unknown_type $tab
     * @param unknown_type $key
     * @param unknown_type $fkey
     * @param boolean $iseq
     */
    public function left_join ($tab, $key, $fkey = null, $iseq = true) {
        return $this->join('left', $tab, $key, $fkey, $iseq);
    }

    /**
     * right join
     *
     * @param unknown_type $tab
     * @param unknown_type $key
     * @param unknown_type $fkey
     * @param boolean $iseq
     */
    public function right_join ($tab, $key, $fkey = null, $iseq = true) {
        return $this->join('right', $tab, $key, $fkey, $iseq);
    }

    /**
     * inner join
     *
     * @param unknown_type $tab
     * @param unknown_type $key
     * @param unknown_type $fkey
     */
    public function inner_join ($tab, $key, $fkey, $iseq = true) {
        return $this->join('inner', $tab, $key, $fkey, $iseq = true);
    }

    /**
     * Order By
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function order ($str) {
        $str = trim($str);
        if ($str != '') {
            $match = array();
            preg_match_all('/(.+?)(desc|asc)\s*?\,?/si', $str, $match);
            if (!empty($match[1])) {
                for ($i = 0, $max = count($match[1]); $i < $max; $i ++) {
                    $field = array();
                    $tmp = explode(',', $match[1][$i]);
                    foreach ($tmp as $v) {
                        $v = trim($v);
                        if ($v != '') {
                            if (preg_match('/^[0-9a-z\_\s]+$/iu', $v)) {
                                $field[] = $this->escape($v);
                            }
                            else {
                                $field[] = $v;
                            }
                        }
                    }
                    $this->_sql['order'][] = implode(' , ', $field) . ' ' . $match[2][$i];
                }
                $match = null;
                unset($match);
            }
            else {
                // field(name , '1','2','3','4')
                $this->_sql['order'][] = preg_replace('/(\w+?)_table/i',
                        '`' . $this->_conf['pre'] . '\\1' . '`', strtolower(trim($str)));
            }
        }
        return $this;
    }

    /**
     * union order
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function uorder ($str) {
        $str = trim($str);
        if ($str != '') {
            $match = array();
            preg_match_all('/(.+?)(desc|asc)\,?/si', $str, $match);
            for ($i = 0, $max = count($match[1]); $i < $max; $i ++) {
                $field = array();
                $tmp = explode(',', $match[1][$i]);
                foreach ($tmp as $v) {
                    $field[] = $v;
                }
                $this->_sql['uorder'][] = implode(' , ', $field) . ' ' . $match[2][$i];
            }
            $match = null;
            unset($match);
        }
        return $this;
    }

    /**
     * Group By
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function group ($str) {
        $str = trim($str);
        if ($str != '') {
            $tmp = explode(',', $str);
            foreach ($tmp as $v) {
                $this->_sql['group'][] = $this->escape($v);
            }
        }
        return $this;
    }

    /**
     * 设置不获取的字段
     * @param  [type] $fields [description]
     * @return [type]         [description]
     */
    public function except ($fields) {
        $sets = $this->_fields;
        $fields = explode(',', $fields);
        foreach ((array)$fields as $v) {
            $field = trim($v);
            // 检测字段是否存在
            if (!isset($this->_fields[$field])) {
                throw new exception(LANG('not exists', "field {$this->_conf['table_name']}.{$field}"), exception::NOT_EXISTS);
            }
            unset($sets[$field]);
        }
        if (empty($sets)) {
            $this->fields('*');
        }
        else {
            $this->clear('fields')->fields(join(',', array_keys($sets)));
        }
        return $this;
    }

    /**
     * 设置查询字段
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function fields ($str) {
        $tmp = is_array($str) ? $str : explode(',', trim(strtolower($str)));
        foreach ($tmp as $v) {

            if (strpos($v, '(') !== false) {
                $this->_sql['fields'][] = $v;
            }
            else if (strpos($v, ' as ') !== false) {
                $temp = explode(' as ', $v);
                $matchs = array();
                // count() , sum() , max() , min() ...
                preg_match('/(\w+)\(\s*?([^\)]+?)\s*?\)/i', trim($temp[0]), $matchs);
                if ($matchs[1] && $matchs[2]) {
                    $matchs = array_map('trim', $matchs);
                    // distinct
                    if (($pos = strpos($matchs[2], 'distinct')) === false) {
                        $this->_sql['fields'][] = $matchs[1] . '( ' .
                                 $this->escape($matchs[2]) . ' ) as ' . trim(($temp[1]));
                    }
                    else {
                        $keyword = substr($matchs[2], 0, $pos + 8);
                        $field = substr($matchs[2], $pos + 8 - strlen($matchs[2]));
                        $this->_sql['fields'][] = $matchs[1] . '( ' . $keyword . ' ' .
                                 $this->escape($field) . ' ) as ' . trim(($temp[1]));
                    }
                }
                else {
                    $this->_sql['fields'][] = $this->escape($temp[0]) . ' as ' .
                             trim(($temp[1]));
                }
            }
            else {
                $this->_sql['fields'][] = $this->escape($v);
            }
        }
        return $this;
    }

    /**
     * 字段转义
     *
     * @param unknown_type $str
     * @return unknown
     */
    public function escape ($str) {
        $ret = $str = strtolower(trim($str));
        // 数字常量
        if (is_numeric($str)) {
            $ret = intval($str);
        }
        // 表字段
        else if (isset($this->_fields[$str])) {
            $ret = $this->_conf['table_name'] . '.`' . $str . '`';
        }
        // info_tab.id as iid
        else if (strpos($str, '.') !== false) {
            $tmp = explode('.', $str);
            if (strpos($str, ' as ') !== false) {
                $temp = explode(' as ', str_replace($tmp[0] . '.', '', $str));
                $ret = $this->table($tmp[0]) . '.`' . trim($temp[0]) .
                        '` as `' . trim($temp[1]) . '`';
            }
            else {
                if (trim($tmp[1]) == '*') {
                    $ret = $this->table($tmp[0]) . '.*';
                }
                else {
                    $ret = $this->table($tmp[0]) . '.`' . trim($tmp[1]) . '`';
                }
            }
        }
        // (jack) as name => "jack" as name
        else if (substr($str, 0, 1) == '(' && substr($str, -1, 1) == ')') {
            $ret = '"' . trim(substr($str, 1, -1)) . '"';
        }
        else {
            $ret = ($str == '*' || substr($str, 0, 5) == '#####') ?
                $str : ('`' . $str . '`');
        }
        return $ret;
    }

    /**
     * 加载表单数据至当前对象data属性
     *
     * @param unknown_type $var
     * @return unknown
     */
    public function load ($var = null) {
        $this->_isload = true;
        $ret = false;
        $this->_error_msg = [];
        if (!empty($var) && is_array($var)) {
            $data = $var;
        }
        else {
            $data = router::get(empty($var) ? $this->_conf['table'] : $var, 'P');
        }
        foreach ((array)$data as $k => $v) {
            // 过滤非当前表字段的内容
            if (isset($this->_fields[$k])) {
                $this->data[$k] = $v;
            }
        }
        $data = null;
        // 单主键 && 自增
        if (!empty($this->_primary_key['key']) &&
                $this->_primary_key['inc'] &&
                !is_array($this->_primary_key['key'])) {
            $op_action = self::OP_INSERT;
            // id
            if ($this->data[$this->_primary_key['key']] > 0) {
                $op_action = self::OP_UPDATE;
            }

            $ret = $this->_validate($op_action);
        }
        // 对于联合主键 或无自增主键 的情况, 默认跳过
        else {
            $ret = true;
        }
        return $ret;
    }

    /**
     * 执行字段验证
     * @param  [type] $data  [description]
     * @param  [type] $field [description]
     * @return [type]        [description]
     */
    private function _exec_validate ($data, $field) {
        $ret = [
            'code'  => 1,
            'msg'   => ''
        ];
        // 回调处理, 默认成功
        if (is_callable($data)) {
            $ret['code'] = 0;
        }
        // 整型
        else if ($field['type'] == 'int') {
            if (!is_numeric($data)) {
                $ret['msg'] = LANG('invalid field values', $field['label'], $data);
            }
            else if ($data > $field['max']) {
                $ret['msg'] = LANG('field values is too large', $field['label'] , $field['max']);
            }
            else if ($data < $field['min']) {
                $ret['msg'] = LANG('field values is too small', $field['label'] , $field['min']);
            }
            else if (!$field['allow_empty_string'] && !is_numeric($data)) {
                $ret['msg'] = LANG('field values cannot be an empty string', $field['label']);
            }
            else {
                $ret['code'] = 0;
            }
        }
        // 浮点型
        else if ($field['type'] == 'float') {
            // 验证小数点前的位数, 例如: 字段 foo float (10, 2), 则验证小数点前数字位数是否超过10
            // 数据库会默认处理精度, 超出部分会被舍弃, 需要在业务代码里控制.
            $max = str_repeat(9, $field['min'] - $field['max']);
            if (!is_numeric($data)) {
                $ret['msg'] = LANG('invalid field values', $field['label'], $data);
            }
            else if (explode('.', $data)[0] > $max) {
                $ret['msg'] = LANG('field values is too large', $field['label'] , $max);
            }
            else {
                $ret['code'] = 0;
            }
        }
        // 字符型
        else if ($field['type'] == 'char' && !$field['allow_empty_string'] && $data == '') {
            $ret['msg'] = LANG('field values cannot be an empty string', $field['label']);
        }
        // 字符型
        else if ($field['type'] == 'char' && mb_strlen($data, 'utf-8') > $field['max']) {
            $ret['msg'] = LANG('field values is too long', $field['label'], $field['max']);
        }
        // 日期验证
        else if ($field['type'] == 'date' && !(bool)call_user_func_array($field['validate'], [$data])) {
            $ret['msg'] = LANG('invalid field values', $field['label'], mb_substr($data, 0, 70));
        }
        // set 验证
        else if ($field['type'] == 'set') {
            $ret['code'] = 0;
            // 若支持空字串输入, 则修改字段属性 allow_empty_string 为 true
            if (!$field['allow_empty_string'] && $data == '') {
                $ret['msg'] = LANG('field values cannot be an empty string', $field['label']);
            }
            // set
            else if ($field['field_type'] == 'set') {
                foreach (explode(',', $data) as $v) {
                    if (!in_array($v, $field['options']) && $v != '') {
                        $ret['code'] = 1;
                        $ret['msg'] = LANG('invalid field values', $field['label'] , mb_substr($v, 0, 70));
                        break;
                    }
                }
            }
            // enum
            else {
                if (!in_array($data, $field['options']) && $data != '') {
                    $ret['code'] = 1;
                    $ret['msg'] = LANG('invalid field values', $field['label'] , mb_substr($data, 0, 70));
                }
            }
        }
        else {
            $ret['code'] = 0;
        }

        return $ret;
    }


    /**
     * 判断是否为主键
     * @param  [type]  $key [description]
     * @return boolean      [description]
     */
    private function _is_primary_key ($key, $check_inc = false) {
        $ret = false;
        if (!empty($this->_primary_key['key']) && !is_array($this->_primary_key['key'])) {
            $ret = $key == $this->_primary_key['key'] && ($check_inc ? $this->_primary_key['inc'] : true);
        }
        return $ret;
    }

    /**
     * 数据验证
     *
     * @return unknown
     */
    private function _validate ($op_action = self::OP_INSERT) {
        // 重置错误消息数组
        $this->_error_msg = [];

        // 当操作为 insert 的时候 , 合并默认数据
        if (self::OP_INSERT == $op_action && !empty($this->defaults)) {
            foreach ($this->_fields as $k => $v) {
                $is_prikey = $this->_is_primary_key($k, true);

                if (!$is_prikey && (!isset($this->data[$k]) ||
                        (in_array($v['type'], ['int', 'float']) && $this->data[$k] === ''))) {
                        $this->data[$k] = null;
                }

                // 非自增主键not null 的列, 若为null 则返回错误
                if (!$v['allow_null'] && !$is_prikey && (!isset($this->data[$k]) || $this->data[$k] === null)) {
                    $this->_error_msg[$k] = LANG('field not allow null', $v['label']);
                    break;
                }
                else if ($v['allow_null'] && !isset($this->data[$k])) {
                    $this->data[$k] = $this->defaults[$k];
                }
            }
            // 默认数据. 只针对 allow null 的字段生效.
            $this->data = array_merge($this->defaults, $this->data);
        }

        // 执行过滤&验证
        if (empty($this->_error_msg)) {
            foreach ($this->data as $k => $v) {
                // 移除不相关的数据
                if (!isset($this->_fields[$k])) {
                    unset($this->data[$k]);
                    continue;
                }
                // 字段基本验证
                $result = $this->_exec_validate($this->data[$k], $this->_fields[$k]);
                if ($result['code'] > 0) {
                    $this->_error_msg[$k] = $result['msg'];
                }
                else if (!is_callable($this->data[$k])) {
                    // 基本过滤
                    if (isset($this->filter[$k])) {
                        $this->data[$k] = call_user_func_array($this->filter[$k], [$this->data[$k]]);
                    }
                    // 默认过滤
                    else {
                        $this->data[$k] = filter::normal($v);
                    }
                }
            }
        }

        // 自定义验证
        if (empty($this->_error_msg)) {
            foreach ($this->validate as $k => $v) {
                // 执行更新时候,若数据不存在(或默认值一致的不做验证),跳过; 只验证存在的数据
                if ((!isset($this->data[$v['name']]) && self::OP_UPDATE == $op_action)
                        || $this->defaults[$v['name']] == $this->data[$v['name']] ) {
                    continue;
                }

                $empty = substr($v['empty'], 0, 1) == '#' ? LANG(substr($v['empty'], 1)) : $v['empty'];
                $error = substr($v['error'], 0, 1) == '#' ? LANG(substr($v['error'], 1)) : $v['error'];

                // 数据验证
                switch (intval($v['type'])) {
                    case 0:
                        // 使用 filter 类提供的规则验证
                        if (!(bool)preg_match(filter::$rules[$v['rule']], $this->data[$v['name']])) {
                            $this->_error_msg[$v['name']] = $error;
                        }
                        break;
                    case 1:
                        // 使用自定义的正则表达式验证
                        try {
                            $result = preg_match($v['rule'], $this->data[$v['name']]);
                        }
                        catch (Exception $e) {
                            throw new exception(LANG('invalid RegEx', $v['rule']), exception::INVALID_REGEX, 'table');
                        }
                        if (!$result) {
                            $this->_error_msg[$v['name']] = $error;
                        }
                        break;
                    case 2:
                        // 使用自定义方法验证
                        if ($v['rule'][0] == get_class($this)) {
                            if (!method_exists($this, $v['rule'][1])) {
                                throw new exception(LANG('not exists', "invalid callback {$v['rule'][0]}::{$v['rule'][1]}"), exception::NOT_EXISTS, 'table');
                            }
                            if (!(bool)call_user_func_array([$this, $v['rule'][1]], [$this->data[$v['name']]])) {
                                $this->_error_msg[$v['name']] = $error;
                            }
                        }
                        else {
                            if (!method_exists($v['rule'][0], $v['rule'][1])) {
                                throw new exception(LANG('not exists', "invalid callback {$v['rule'][0]}::{$v['rule'][1]}"), exception::NOT_EXISTS, 'table');
                            }
                            if (!(bool)call_user_func_array($v['rule'], [$this->data[$v['name']], $this->data])) {
                                $this->_error_msg[$v['name']] = $error;
                            }
                        }
                        break;
                }
            }
        }
        // 唯一性验证
        if (empty($this->_error_msg) && !empty($this->unique_check)) {
            foreach ((array)$this->unique_check as $v) {
                $this->_exec_unique_check($v, $op_action);
            }
        }
        return empty($this->_error_msg);
    }

    /**
     * 执行唯一性约束验证
     * @param  array  $fields    [description]
     * @param  [type] $op_action [description]
     * @return [type]            [description]
     */
    private function _exec_unique_check ($fields = [], $op_action = self::OP_INSERT) {
        $tab = $this->get_mirror();
        // @todo replace, 无自增主键的表(暂不支持)
        if ($op_action != self::OP_REPLACE) {
            $fields_name = [];
            $where = [];
            foreach ($fields as $v) {
                if (isset($this->data[$v])) {
                    $where[] = "{$v} = '{$this->data[$v]}'";
                    $fields_name[] = $this->_fields[$v]['label'] ?: $v;
                }
                // 多字段唯一索引, 在data数据不全的情况下. 不做验证, 待优化
                else {
                    $where = [];
                    $fields_name = [];
                    break;
                }
            }
            if (!empty($fields_name)) {
                $tab->where($where);
                $pri_key = $this->_primary_key['key'];
                if (!is_string($pri_key)) {
                    throw new exception(LANG('does not support', LANG('unique index validate', $this->get_name())),
                             exception::NOT_SUPPORT, 'table');
                }
                if (isset($this->data[$pri_key]) && $this->data[$pri_key]) {
                    $tab->where("{$pri_key} != {$this->data[$pri_key]}");
                }
                if ($tab->count()) {
                    $this->_error_msg[$fields[0]] = isset($this->unique_msg[join('-', $fields)]) ?
                            $this->unique_msg[join('-', $fields)] : LANG('duplicate entry not allow', join(' - ', $fields_name));
                }
            }
        }
    }

    /**
     * 根据类名获取表名
     *
     * @param unknown_type $class
     * @return unknown
     */
    public function table ($class) {
        return preg_replace('/(\w+?)_table/i', '`' . $this->_conf['pre'] . '\\1' . '`',
                    strtolower(trim($class)));
    }

    /**
     * 清除sql条件
     *
     * @param unknown_type $key
     * @return unknown
     */
    public function clear ($key) {
        if (isset($this->_sql[$key])) {
            $this->_sql[$key] = null;
            unset($this->_sql[$key]);
        }
        return $this;
    }

    /**
     * 获取错误消息
     *
     * @return unknown
     */
    public function get_error () {
        $error_msg = $this->_error_msg;
        $this->_error_msg = null;
        return $error_msg;
    }

    /**
     * 获取第一条错误消息
     *
     * @return unknown
     */
    public function get_first_error () {
        $error_msg = $this->_error_msg;
        $this->_error_msg = null;
        return !empty($error_msg) ? array_splice($error_msg, 0, 1) : null;
    }

    /**
     * 获取错误文本
     *
     * @return unknown
     */
    public function get_error_desc ($limit = 1) {
        $error_msg = $this->_error_msg;
        $this->_error_msg = null;
        return !empty($error_msg) ? join(', ', array_splice($error_msg, 0, $limit)) : null;
    }

    /**
     * 镜像分身
     * @return [type] [description]
     */
    public function get_mirror () {
        return clone $this;
    }
}