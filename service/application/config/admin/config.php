<?php
/**
 * 跟环境有关的配置文件
 * @createTime 2017/3/22 11:04
 */

return [
    //存储token创建时间的缓存key
    'token_cache_key' => 'admin_token:',

    //Token签名key
    'token_sign_key'  => 'EB99258A-8112664A-F0889B08-2B42747F',

    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => '7w5fSD*Nz_9sg/#OX]oJ^', //默认数据加密KEY

    //Token有效时间,单位秒
    'token_expires'   => 21600,
];