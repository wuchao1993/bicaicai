<?php

namespace app\admin\model;

use think\Model;

class AgentDayStatistics extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'ads_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'ads_id'                     => '主键',
        'user_id'                    => '用户ID',
        'user_pid'                   => '父级ID',
        'user_grade'                 => '用户代理层级',
        'ads_date'                    => '天',
        'ads_recharge'               => '总充值',
        'ads_withdraw'               => '总提现',
        'ads_deduction'              => '总扣除',
        'ads_bet'                    => '总下注额',
        'ads_bet_times'              => '总投注笔数',
        'ads_bonus'                  => '中奖金额',
        'ads_rebate'                 => '销售返点',
        'ads_rebate'                 => '代理返点',
        'ads_discount'               => '活动优惠',
        'ads_team_profit'            => '团队盈亏',
        'ads_platform_profit'        => '平台盈亏',
        'ads_platform_actual_profit' => '平台实际盈亏',
        'ads_recharge_times'         => '充值次数',
        'ads_recharge_times'         => '提款次数',
        'ads_first_recharge_times'   => '首次充值人数',
        'ads_first_recharge'         => '首次充值金额',
    ];

}