<?php
if (!isset($GLOBALS['_RL']) || empty($GLOBALS['_RL'])) {
    $GLOBALS['_RL'] = array(
        // core
        'invalid routing configuration'         => '无效的路由配置信息',
        'unknown parameter types'               => '未知的参数来源类型 %s',
        'failed to create directory'            => '创建目录 %s 失败',
        'write to file failed'                  => '写入文件 %s 失败, 请检查相关目录',
        'file not found'                        => '文件 %s 不存在',
        'directory does not exist'              => '%s 目录不存在',
        'class file not found'                  => '类文件 %s 不存在',
        'class name is invalid'                 => '无效的模块类名',
        'class type is not allowed'             => '类型 %s 不允许自动加载',
        'module action does not exist'          => '模块动作 %s 不存在',
        'driver file not found'                 => '驱动文件 %s 不存在',
        'does not support'                      => '%s 不支持',
        'connect to server failure'             => '连接 %s 服务失败',
        'cache'                                 => '缓存',
        'program'                               => '程序',
        'does not support this feature'         => '功能 %s 不支持',
        
        // database
        'db'                                    => '数据库',
        'table'                                 => '数据表',
        'db query fails'                        => 'SQL 执行失败了 %s',
        'db server connection failed'           => '连接到 %s 服务失败',
        'db does not exists'                    => '数据库 %s 不存在',
        'db configuration is unavailable'       => '数据库连接信息无效',
        'db configuration parsing fails'        => '数据库连接信息格式无效',
        'table field does not exist'            => '字段 %s 不存在(%s)',
        'no records found'                      => '该记录未找到',
        
        // table
        'invalid regular expression'            => '无效的正则表达式',
        'data to be processed is empty'         => '表模型 %s 无数据',
        'wrong number of arguments'             => '格式化失败,参数个数错误',
    
        // template
        'invalid variable processing tag'       => '无效的变量处理标签 %s',
        'template undefined constant'           => '未定义的模板常量 %s : %s',
        'be merged source file does not exist'  => '待合并的资源文件不存在 : %s',

        // block
        'invalid source module'                 => '无效的数据模块',
        'invalid block tag'                     => '无效的数据标签 %s',
        'please enter int value'                => '请输入一个数值',
        'wrong format'                          => '格式错误',
        'please enter the notes information'    => '请输入标签备注信息',
        'invalid block tag'                     => '无效的数据标签 %s',
        
        // misc
        'get help'                              => '获取帮助',
        'error has been recorded'               => '错误已经被记录, 给您带来的不便, 我们深感歉意!',
    
        // attachment && upload
        'attachment directory is not writable'  => '上传目录 %s 不可写',
        'operation not permitted'               => '操作 %s 不允许',
        'please select a file to upload'        => '请选择文件上传',
        'failed to write file'                  => '写入文件失败',
        'part of the file is missing'           => '文件部分内容丢失',
        'file size exceeds the system limit'    => '文件%s 大小 %s 请勿超过 %s',
        'file type is not allowed'              => '不允许上传 %s 类型文件',
        'Invalid upload files'                  => '无效的上传临时文件',
        'failed to move file'                   => '移动临时文件 %s 至 %s 失败',
        'upload File not found'                 => '上传文件不存在',
        
        // stat
        'operating system'                      => '操作系统',
        'http server soft'                      => 'http软件',
        'http server port'                      => 'http端口',
        'gateway interface'                     => '网关接口',
        'php info'                              => 'PHP信息',
        'memory usage'                          => '内存使用',
        'uptime'                                => '运行时长',
        'cache type'                            => '缓存类型',
        'cache entries'                         => '缓存总数',
        'cache size'                            => '缓存总量',
        'cache directory'                       => '缓存目录',
        'threads'                               => '活动线程',
        'questions'                             => '已查询数',
        'slow queries'                          => '慢查询数',
        'opens'                                 => '已打开表数',
        'open tables'                           => '当前打开数',
        'flush tables'                          => '已刷新表数',
        'queries per second avg'                => '每秒查询数',
        'db size'                               => '当前数据库大小',
        'number of connections'                 => '当前连接数'
    );
}