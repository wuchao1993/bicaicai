<?php
/**
 * session配置文件
 */

use think\Env;

return [
    'id'             => '',

    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',

    // 是否自动开启 SESSION
    'auto_start'   => true,

    // SESSION 前缀
    'prefix' => '',

    // 驱动方式 支持redis memcache memcached
    'type'        => 'redis',

    // redis主机
    'host'       => Env::get('redis.hostname', '127.0.0.1'),

    // redis端口
    'port'       => Env::get('redis.port', 6379),

    // 密码
    'password'   => Env::get('redis.auth', ''),

    //有效时间
    'expire'     => 86400,

    // sessionkey前缀
    'session_name' => Env::get('redis.prefix', 'ds').':admin:ss_',

    // 操作库
    'select'    => Env::get('redis.select', '0')
];