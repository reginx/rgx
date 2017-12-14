<?php
namespace re\rgx;

/*
  +-----------------------------------------------------------------
  + user_info 表模型
  + ----------------------------------------------------------------
  + @date 2017-12-13 15:26:39
  + @desc 若修改了表结构, 请使用下面的命令更新模型文件
  + @cmd  php ./build.php --prefix=../tests/helo --all --force
  + @generator RGX v1.0.0.20171212_RC
  +-----------------------------------------------------------------
*/
class user_info_table extends table {

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
        'user_id' => [
            'name'               => 'user_id',
            'type'               => 'int',
            'field_type'         => 'int',
            'min'                => 0,
            'max'                => 4294967295,
            'label'              => '用户ID',
            'allow_empty_string' => false,
            'allow_null'         => false
        ],
        'user_nick' => [
            'name'               => 'user_nick',
            'type'               => 'char',
            'field_type'         => 'varchar',
            'min'                => 0,
            'max'                => 255,
            'label'              => '昵称',
            'allow_empty_string' => false,
            'allow_null'         => false
        ],
        'user_email' => [
            'name'               => 'user_email',
            'type'               => 'char',
            'field_type'         => 'varchar',
            'min'                => 0,
            'max'                => 64,
            'label'              => '邮箱',
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
        'key' => 'user_id',
        'inc' => true
    ];

    /*
      +--------------------------
      + 字段默认值
      +--------------------------
    */
    public $defaults = [
        'user_id'     => 0,
        'user_nick'   => '',
        'user_email'  => '',
    ];

    /*
      +--------------------------
      + 字段过滤规则
      +--------------------------
    */
    public $filter = [
        'user_id'     => ['re\rgx\filter', 'int'],
        'user_nick'   => ['re\rgx\filter', 'char'],
        'user_email'  => ['re\rgx\filter', 'char'],
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