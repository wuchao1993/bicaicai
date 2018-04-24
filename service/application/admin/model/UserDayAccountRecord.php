<?php
/**
 * 用户每日统计表
 */

namespace app\admin\model;

use think\Model;

class UserDayAccountRecord extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'usda_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'usda_id'                     => '主键',
        'user_id'                     => '用户ID',
        'user_pid'                    => '父级ID',
        'user_grade'                  => '用户代理层级',
        'usda_day'                    => '天',
        'usda_recharge'               => '总充值',
        'usda_withdraw'               => '总提现',
        'usda_deduction'              => '总扣除',
        'usda_bet'                    => '总下注额',
        'usda_bet_count'              => '总投注笔数',
        'usda_bonus'                  => '中奖金额',
        'usda_rebate'                 => '销售返点',
        'usda_agent_rebate'           => '代理返点',
        'usda_discount'               => '活动优惠',
        'usda_team_profit'            => '团队盈亏',
        'usda_platform_profit'        => '平台盈亏',
        'usda_platform_actual_profit' => '平台实际盈亏',
        'usda_recharge_num'           => '充值次数',
        'usda_withdraw_num'           => '提款次数',
    ];

}