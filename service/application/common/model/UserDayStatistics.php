<?php
namespace app\common\model;

use think\Model;
use think\Config;

class UserDayStatistics extends Model{

    public $pk = 'uds_id';

    public function getAgentIds($date){
        $condition = [
            'user_is_agent' => Config::get('status.user_is_agent')['yes'],
            'uds_date' => $date
        ];

        return $this->where($condition)->column('user_id');
    }


    public function getUserInfosGroupPid($date){
        $condition = [
            'uds_date' => $date
        ];

        return $this->where($condition)->group('user_pid')->column('user_id,user_pid,user_grade,user_is_agent');
    }


    public function getAgentStatisticsInfo($userId, $date){
        $condition = [
            'user_pid|user_id' => $userId,
            'uds_date' => $date,
        ];
        $fields = [
            'sum(uds_recharge)' => 'ads_recharge',
            'sum(uds_withdraw)' => 'ads_withdraw',
            'sum(uds_deduction)' => 'ads_deduction',
            'sum(uds_bet)' => 'ads_bet',
            'sum(uds_bet_times)' => 'ads_bet_times',
            'sum(uds_bonus)' => 'ads_bonus',
            'sum(uds_rebate)' => 'ads_rebate',
            'sum(uds_discount)' => 'ads_discount',
            'sum(uds_cancel_order_times)' => 'ads_cancel_order_times',
            'sum(uds_cancel_order)' => 'ads_cancel_order',
            'sum(if(uds_first_recharge>0, uds_first_recharge, 0))' => 'ads_first_recharge',
            'count(if(uds_first_recharge>0, uds_first_recharge, null))' => 'ads_first_recharge_times',
            'sum(uds_recharge_times)' => 'ads_recharge_times',
            'sum(uds_withdraw_times)' => 'ads_withdraw_times',
            'sum(uds_platform_profit)' => 'ads_platform_profit',
            'sum(uds_platform_actual_profit)' => 'ads_platform_actual_profit',
        ];
        $result = $this->where($condition)->field($fields)->find();

        return $result ? $result->toArray() : false;
    }


    public function getSubordinateStatistics($userId, $startDate, $endDate){
        $condition = [
            'user_pid' => $userId,
            'uds_date' => ['between', [$startDate, $endDate]],
        ];

        $fields = [
            'sum(uds_recharge)' => 'recharge',
            'sum(uds_bet)' => 'bet',
            'user_id',
        ];
        $result = $this->where($condition)->field($fields)->group('user_id')->select();
        return $result ? collection($result)->toArray() : false;
    }

}