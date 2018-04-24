<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Env;

//试玩库
if (isset($GLOBALS['auth_identity']) && in_array($GLOBALS['auth_identity'], ['guest', 'special'])) {
    return [
        // 数据库类型
        'type'           => 'mysql',
        'deploy'         => Env::get('try_mysql.deploy', 0),
        'rw_separate'    => Env::get('try_mysql.rw_separate', false),
        // 服务器地址
        'hostname'       => Env::get('try_mysql.hostname', '127.0.0.1'),
        // 数据库名
        'database'       => Env::get('try_mysql.database', 'dscp'),
        // 用户名
        'username'       => Env::get('try_mysql.username', 'dscp'),
        // 密码
        'password'       => Env::get('try_mysql.password', ''),
        // 端口
        'hostport'       => Env::get('try_mysql.port', '3306'),
        // 连接dsn
        'dsn'            => '',
        // 数据库连接参数
        'params'         => [],
        // 数据库表前缀
        'prefix'         => Env::get('try_mysql.prefix', 'ds_'),
        // 数据库调试模式
        'debug'          => Env::get('try_mysql.debug', false),
        // 是否需要进行SQL性能分析
        'sql_explain'    => false,
    ];
}
return [
    // 数据库类型
    'type'           => 'mysql',
    'deploy'         => Env::get('mysql.deploy', 0),
    'rw_separate'    => Env::get('mysql.rw_separate', false),
    // 服务器地址
    'hostname'       => Env::get('mysql.hostname', '127.0.0.1'),
    // 数据库名
    'database'       => Env::get('mysql.database', 'dscp'),
    // 用户名
    'username'       => Env::get('mysql.username', 'dscp'),
    // 密码
    'password'       => Env::get('mysql.password', ''),
    // 端口
    'hostport'       => Env::get('mysql.port', '3306'),
    // 连接dsn
    'dsn'            => '',
    // 数据库连接参数
    'params'         => [],
    // 数据库表前缀
    'prefix'         => Env::get('mysql.prefix', 'ds_'),
    // 数据库调试模式
    'debug'          => Env::get('mysql.debug', false),
    // 是否需要进行SQL性能分析
    'sql_explain'    => false,
];


