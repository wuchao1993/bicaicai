<?php
/**
 * 缓存配置文件
 * @createTime 2017/3/22 11:04
 */

use think\Env;

!isset($GLOBALS['auth_identity']) && $GLOBALS['auth_identity'] = 'normal';
if (in_array($GLOBALS['auth_identity'], ['guest', 'special'])) {
    $identity = 'guest';
} else {
    $identity = 'normal';
}

return [
    // 使用复合缓存类型
    'type'    => 'complex',
    // 默认使用的缓存
    'default' => [
        // 驱动方式
        'type'     => 'redis',
        // 服务器地址
        'host'     => Env::get('redis.hostname', '127.0.0.1'),
        'password' => Env::get('redis.auth', ''),
        'port'     => Env::get('redis.port', 6379),
        // 缓存前缀
        'prefix'   => Env::get('redis.prefix', 'ds') . ':' . $identity . ':',
        // 缓存有效期 0表示永久缓存
        'expire'   => 0,
        'select'   => Env::get('redis.select', 0)
    ],
    // 文件缓存
    'file'    => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => Env::get('redis.prefix', 'ds') . ':' . $identity . ':',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ]
];


