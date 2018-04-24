<?php
return [

    'sport_types' => [
        'football' => 1,
        'basketball' => 2,
        'tennis' => 7
    ],

    'sport_keep_status' => [
        'enable' => 1,
        'disable' => 2,
    ],

    'schedule_status' => [
      'before_match' => 0,
      'matching'     => 1,
      'middle_match' => 2,
      'match_end'    => 3,
      'match_error'  => 4,
    ],

    'schedule_type' => [
        'football'   => ['id'=>'sfs_id','status'=>'sfs_status'],
        'basketball' => ['id'=>'sbs_id','status'=>'sbs_status'],
        'tennis'     => ['id'=>'sts_id','status'=>'sts_status'],
    ],

    //有效用户条件
    'valid_user_condition' => [
        'recharge_amount' => 1000,
        'bet_amount' => 0,
    ],

];