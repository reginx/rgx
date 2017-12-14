<?php
namespace re\rgx;

/*
  +-----------------------------------------------------------------
  + user 表模型
  + ----------------------------------------------------------------
  + @date 2017-12-13 15:26:39
  + @desc 若修改了表结构, 请使用下面的命令更新模型文件
  + @cmd  php ./build.php --prefix=../tests/helo --all --force
  + @generator RGX v1.0.0.20171212_RC
  +-----------------------------------------------------------------
*/
class user_table extends table {

    /*
      +--------------------------
      + 编码
      +--------------------------
    */
    protected $_charset = 'utf8mb4';

    /*
      +--------------------------
      + 字段
      +--------------------------
    */
    protected $_fields = [
        'id' => [
            'name'               => 'id',
            'type'               => 'int',
            'field_type'         => 'int',
            'min'                => 0,
            'max'                => 4294967295,
            'label'              => 'ID',
            'allow_empty_string' => false,
            'allow_null'         => false
        ],
        'name' => [
            'name'               => 'name',
            'type'               => 'char',
            'field_type'         => 'varchar',
            'min'                => 0,
            'max'                => 255,
            'label'              => '姓名',
            'allow_empty_string' => false,
            'allow_null'         => false
        ],
    ];

    /*
      +--------------------------
      + 主键
      +--------------------------
    */
    protected $_primary_key = [
        'key' => 'id',
        'inc' => true
    ];

    /*
      +--------------------------
      + 字段默认值
      +--------------------------
    */
    public $defaults = [
        'id'  => 0,
        'name'=> '',
    ];

    /*
      +--------------------------
      + 字段过滤规则
      +--------------------------
    */
    public $filter = [
        'id'  => ['re\rgx\filter', 'int'],
        'name'=> ['re\rgx\filter', 'char'],
    ];

    /*
      +--------------------------
      + 唯一性检测
      +--------------------------
    */
    public $unique_check = [
        
    ];

    /*
      +--------------------------
      + 自定义字段验证规则
      +--------------------------
    */
    public $validate = [];

}