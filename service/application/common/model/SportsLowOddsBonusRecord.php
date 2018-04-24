<?php

namespace app\common\model;

use think\Model;

class SportsLowOddsBonusRecord extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'slobr_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'slobr_id'                  => '主键',
        'slobr_user_id'             => '用户ID',
        'slobr_order_no'            => '订单号',
        'slobr_amount'              => '充值金额',
        'slobr_traffic_amount'      => '打码量',
        'slobr_require_bet_amount'  => '已达投注量',
        'slobr_is_withdraw'         => '是否提现',
        'slobr_create_time'         => '创建时间',
        'slobr_modify_time'         => '修改时间',
    ];
}