<?php
return [
    'Aes' => [
        'key' => '4c0d156453e48649', //自定义
        'iv' => '748a34f0fb8b99d8', //自定义
    ],
    'Rsa' => [
        'privateKey' => CONF_PATH . '/rsa_private_key.pem', //私钥文件路径
        'publicKey' => CONF_PATH . '/rsa_public_key.pem', //公钥文件路径
    ],
];
