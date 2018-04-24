<?php
/**
 * 跟环境有关的配置文件
 * @createTime 2017/3/22 11:04
 */

use think\Env;

return [
    //站点域名
    'domain' => Env::get('app.domain', ''),

    //oss配置
    'oss_sports_url' => Env::get('oss.sports_url', ''),

    //是否签名
    'sign_check' => Env::get('app.sign_check', false),

    //签名key
    'sign_key' => Env::get('app.sign_key', ''),

    //Token签名key
    'token_sign_key' => Env::get('app.token_sign_key', ''),

    //Token有效时间,单位秒
    'token_expires' => 604800,
    'unverified_token_expires' => 600, //10分钟
    'guest_token_expires' => 2592000, //30天

    //不需要验证签名的IP
    'sign_uncheck_ip' => [
        '192.168.0.32',
        '192.168.0.82',
        '202.190.151.63',
        '47.52.141.144',
        '47.91.210.62',
        '172.31.109.26',
    ],
];
