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

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

    '__domain__'                               => [
        'pay.kosun.lab'  => 'pay',
        'pay1.kosun.lab' => 'pay',
        'pay2.kosun.lab' => 'pay',
    ],


    //路由跳转支付回掉
    'api/Notify/index'                         => 'pay/Notify/index',
    'api/Index/getMerchantGroup'               => 'pay/Index/getMerchantGroup',
    'api/Index/onlineRecharge'                 => 'pay/Index/onlineRecharge',
    'api/Index/friendRecharge'                 => 'pay/Index/friendRecharge',
    'api/Index/companyRecharge'                => 'pay/Index/companyRecharge',

];
