<?php

use think\Env;

return [
    'appName'     => 'sports',
    //应用名称
    'domain'      => Env::get('passport.internal_url'),
    //客户简称
    'siteIdentity' => Env::get('passport.site_identity'),
    //Passport域名
    'redisConfig' => [
        'type'     => 'redis',
        'host'     => Env::get('passport.redis_hostname'),
        'port'     => Env::get('passport.redis_port'),
        'password' => Env::get('passport.redis_auth'),
        'select'   => Env::get('passport.redis_select'),
    ],
    //Passport Redis配置
];
