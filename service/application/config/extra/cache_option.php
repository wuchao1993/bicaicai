<?php
//缓存业务相关配置
return [

    //缓存开关
    'switch' => [

    ],

    //缓存前缀
    'prefix' => [
        'admin'                           => 'admin:',
        'digital'                         => 'digital:',
        'sports'                          => 'sports:',
        'sports_api'                      => 'sports:api:',
        'sports_common'                   => 'sports:common:',
        'sports_order_lock'               => 'sports:lock:order_lock:',
        'sports_clearing_lock'            => 'sports:lock:clearing_lock:',
        'sports_football_schedule_lock'   => 'sports:lock:football_schedule_lock:',
        'sports_football_outright_lock'   => 'sports:lock:football_outright_lock:',
        'sports_basketball_schedule_lock' => 'sports:lock:basketball_schedule_lock:',
        'sports_basketball_outright_lock' => 'sports:lock:basketball_outright_lock:',
        'sports_tennis_schedule_lock'     => 'sports:lock:tennis_schedule_lock:',
        'sports_tennis_outright_lock'     => 'sports:lock:tennis_outright_lock:',
        'sports_collect'                  => 'sports:collect:',
        'withdraw'                        => 'withdraw:',
        'pay_center'                      => 'pay_center:',
        'pay_center_bank_list'            => 'pay_center:bank_list',
        'pay_center_merchant_list'        => 'pay_center:merchant_list',
    ],

];