<?php
namespace app\common\model;

use think\Model;

class UserDayAccountRecord extends Model{

    public $pk = 'usda_id';

    public function getUsersAccountStatistics($userIds, $startDate = null, $endDate = null){
        $startDate = $startDate ? $startDate : date('Ymd', strtotime('-1 day'));
        $endDate   = $endDate ? $endDate : date('Ymd', strtotime('-1 day'));
        if($endDate > $startDate){
            $condition['usda_day'] = ['between', [$startDate, $endDate]];
        }else {
            $condition['usda_day'] = $startDate;
        }
        $condition['user_id'] = ['in', $userIds];
        $fields = "user_id,IFNULL(SUM(usda_recharge), 0) recharge,IFNULL(SUM(usda_withdraw), 0) withdraw,
        IFNULL(SUM(usda_bet), 0) bet,IFNULL(SUM(usda_bonus), 0) bonus,IFNULL(SUM(usda_agent_rebate), 0) income";

        return $this->where($condition)->group('user_id')->column($fields, 'user_id');
    }


}