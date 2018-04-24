<?php
/**
 * 跟环境有关的配置文件
 * @createTime 2017/3/22 11:04
 */

return [
    //站点域名
    'make_image_ext' => 'png',
    'agl_status' => [
        "delete" => -1,
        "enable" => 1,
        "disable" => 2
    ],
    'rand_qrcode_strlen' => 6,
    'qrcode_img_param' => [
        'size' => 300,
        'padding' => 10,
        'ErrorCorrection' =>'high',
        'ForegroundColor' => 0,
        'BackgroundColor'=>255,
        'BackgroundColorEnd' => 0,
        'label' => '用户邀请码',
        'LabelFontSize' => 16,
    ],
    'user_play_type' => [
        'player' => 1,
    ],
    'limit_num' => [
        'page' => 0,
        'count' => 10,
    ],
    'sports_agent_rebate_status' => [
        'enable' => 1,
        'disable' => 2,
        'delete'  => -1,
    ],
    'agent_link_default' => [
        'use_count'     => 99999,
        'expire_time'   => '0000-00-00 00:00:00',
    ],
    'qrcode_max_num' => 10,
    'qrcode_getlist_min_count' => 1,
];
