<?php
/**
 * 缓存配置文件
 * @createTime 2017/3/22 11:04
 */

use think\Env;

return [
    // 使用复合缓存类型
    'type'    => 'complex',
    // 默认使用的缓存
    'default' => [
        // 驱动方式
        'type'   => 'redis',
        // 服务器地址
        'host'   => Env::get('redis.hostname', '127.0.0.1'),
        'password' => Env::get('redis.auth', ''),
        'port'   => Env::get('redis.port', 6379),
        // 缓存前缀
        'prefix' => Env::get('redis.prefix', 'ds').':admin:',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
        'select' => Env::get('redis.select', 0)
    ],
    // 文件缓存
    'file' => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => 'admin:'  . ':',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ]
];


