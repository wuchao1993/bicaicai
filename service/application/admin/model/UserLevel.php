<?php
/**
 * 用户层级表
 */

namespace app\admin\model;

use think\Model;

class UserLevel extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'ul_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'ul_id'                       => '主键',
        'ul_name'                     => '名称',
        'ul_description'              => '描述',
        'ul_user_create_start_time'   => '会员注册开始时间',
        'ul_user_create_end_time'     => '会员注册截止时间',
        'ul_user_recharge_start_time' => '会员存款开始时间',
        'ul_user_recharge_end_time'   => '会员存款截止时间',
        'ul_recharge_count'           => '存款次数',
        'ul_recharge_amount'          => '存款总额',
        'ul_recharge_max_amount'      => '最大存款额度',
        'ul_withdraw_count'           => '提款次数',
        'ul_withdraw_amount'          => '提款总额',
        'ul_recharge_highest'         => '最高存款额',
        'ul_recharge_lowest'          => '最低存款额',
        'ul_user_count'               => '用户数',
        'ul_status'                   => '平台盈亏',
        'ul_default'                  => '平台实际盈亏',
    ];

}