<?php
namespace app\common\model;

use think\Model;

class AgentDayStatistics extends Model{

    public $pk = 'ads_id';

    public function getInfoByDate($userId, $date){
        $condition = [
            'user_id' => $userId,
            'ads_date' => $date
        ];
        $fields ='ads_recharge,ads_bet';
        $result = $this->where($condition)->field($fields)->find();

        return is_object($result) ? $result->toArray() : $result;
    }


    public function statisticsBetweenDate($userId, $startDate, $endDate){
        $startDate = date('Ymd', strtotime($startDate));
        $endDate = date('Ymd', strtotime($endDate));
        $condition = [
            'user_id' => $userId,
            'ads_date' => ['between', [$startDate, $endDate]],
        ];
        $fields = [
            'sum(ads_recharge)' => "totalRecharge",
            'sum(ads_withdraw)' => "totalWithdraw",
            'sum(ads_bet)' => "totalBet",
            'sum(ads_bonus)' => "totalBonus",
            'sum(ads_discount)' => "totalDiscount",
            'sum(ads_rebate)' => "betRebate",
            'sum(ads_first_recharge)' => "firstRecharge",
            'sum(ads_first_recharge_times)' => "firstRechargeUserCount",
            'sum(ads_team_profit)' => "profit"
        ];
        $result = $this->where($condition)->field($fields)->find();

        return is_object($result) ? $result->toArray() : $result;
    }

    public function getAgentDayStatisticsList($userId, $startDate, $endDate){
        $startDate = date('Ymd', strtotime($startDate));
        $endDate = date('Ymd', strtotime($endDate));
        $condition = [
            'user_id' => $userId,
            'ads_date' => ['between', [$startDate, $endDate]],
        ];
        $fields = [
            'ads_date' => 'date',
            'ads_recharge' => "totalRecharge",
            'ads_withdraw' => "totalWithdraw",
            'ads_bet' => "totalBet",
            'ads_bonus' => "totalBonus",
            'ads_discount' => "totalDiscount",
            'ads_rebate' => "betRebate",
            'ads_first_recharge' => "firstRecharge",
            'ads_first_recharge_times' => "firstRechargeUserCount",
            'ads_team_profit' => "profit"
        ];
        $result = $this->where($condition)->field($fields)->select();

        return $result ? collection($result)->toArray() : [];
    }

}