<?php
namespace app\clearing\logic;

use think\Loader;
use think\Db;
use think\Log;

class AgentDayStatistics{

    public function statisticsUserDayInfos($params){
        $startDate = $params['startDate'];
        $endDate   = $params['endDate'];
        $date = date('Ymd', strtotime($startDate));
        Loader::model('UserDayStatistics')->where(['uds_date' => $date])->delete();
        $sportsStatistics = Loader::model('UserAccountRecord')->getSportsStatistics($startDate, $endDate);
        if(empty($sportsStatistics)){
            Log::write($date."无统计数据！");
            return true;
        }
        $userRechargeStatistics = Loader::model('UserRechargeRecord')->getUserRechargeStatistics($startDate, $endDate);
        $userWithdrawStatistics = Loader::model('UserWithdrawRecord')->getUserWithdrawStatistics($startDate, $endDate);
        $userRechargeStatistics = reindex_array($userRechargeStatistics, 'user_id');
        $userWithdrawStatistics = reindex_array($userWithdrawStatistics, 'user_id');
        $statisticsInfos = [];
        Db::startTrans();
        foreach ($sportsStatistics as $key => $info){
            $statisticsInfo = [
                'user_id' => $info['user_id'],
                'user_pid' => $info['user_pid'],
                'user_grade' => $info['user_grade'],
                'user_is_agent' => $info['user_is_agent'],
                'uds_date' => $date,
                'uds_recharge' => $userRechargeStatistics[$info['user_id']]['amount'],
                'uds_withdraw' => $userWithdrawStatistics[$info['user_id']]['amount'],
                'uds_recharge_times' => $userRechargeStatistics[$info['user_id']]['times'],
                'uds_withdraw_times' => $userWithdrawStatistics[$info['user_id']]['times'],
                'uds_deduction' => $info['uds_deduction'],
                'uds_bet' => $info['uds_bet'],
                'uds_bet_times' => $info['uds_bet_times'],
                'uds_bonus' => $info['uds_bonus'],
                'uds_rebate' => $info['uds_rebate'],
                'uds_discount' => $info['uds_discount'],
                'uds_first_recharge' => $userRechargeStatistics[$info['user_id']]['first_recharge'],
                'uds_cancel_order' => $info['uds_cancel_order'],
                'uds_cancel_order_times' => $info['uds_cancel_order_times'],
                'uds_createtime' => current_datetime(),
                'uds_modifytime' => current_datetime(),
            ];
            $statisticsInfo['uds_team_profit'] = $statisticsInfo['uds_discount'] + $statisticsInfo['uds_rebate']
                + $statisticsInfo['uds_bonus'] + $statisticsInfo['uds_cancel_order']
                - $statisticsInfo['uds_bet'] - $statisticsInfo['uds_deduction'];

            $statisticsInfo['uds_platform_profit'] = $statisticsInfo['uds_bet'] - $statisticsInfo['uds_rebate'] - $statisticsInfo['uds_bonus'];
            $statisticsInfo['uds_platform_actual_profit'] = $statisticsInfo['uds_recharge'] - $statisticsInfo['uds_withdraw'];

            //获取用户有效投注
            $userSportStatistics = Loader::model('SportsOrders')->getSportsBetAmount($info['user_id'], $startDate, $endDate);
            $statisticsInfo['uds_bet_valid'] = $userSportStatistics['bet'];

            $statisticsInfos[$key] = $statisticsInfo;
        }

        if($statisticsInfos){
            $result = Loader::model('UserDayStatistics')->insertAll($statisticsInfos);
            if($result === false){
                Db::rollback();
                return false;
            }
        }
        Db::commit();
        return true;
    }


    public function statisticsAgentDayInfos($params){
        $date = date('Ymd', strtotime($params['startDate']));
        Loader::model('AgentDayStatistics')->where(['ads_date' => $date])->delete();
        $userInfos = Loader::model('UserDayStatistics')->getUserInfosGroupPid($date);
        $userPids = extract_array($userInfos, 'user_pid');
        $agentStatisticsList = [];

        foreach ($userInfos as $userInfo){
            if($userInfo['user_pid'] == 0 && in_array($userInfo['user_id'], $userPids)){
                continue;
            }elseif ($userInfo['user_pid'] == 0 && $userInfo['user_is_agent']){
                $userId = $userInfo['user_id'];
            }else{
                $userId = $userInfo['user_pid'];
            }
            $agentStatisticsInfo = Loader::model('UserDayStatistics')->getAgentStatisticsInfo($userId, $date);
            $agentStatisticsInfo['ads_date'] = $date;
            //团队盈亏 = 活动+销售返水+中奖金额+取消注单-总下注额-人工扣除
            $agentStatisticsInfo['ads_team_profit'] = $agentStatisticsInfo['ads_discount'] + $agentStatisticsInfo['ads_rebate']
                + $agentStatisticsInfo['ads_bonus']  + $agentStatisticsInfo['ads_cancel_order']
                - $agentStatisticsInfo['ads_bet'] - $agentStatisticsInfo['ads_deduction'];
            $agentStatisticsInfo['user_id'] = $userId;
            $agentStatisticsInfo['user_pid'] = $userInfo['user_pid'];
            $agentStatisticsInfo['user_grade'] = $userInfo['user_grade'];

            if($userId == 1){
                $agentStatisticsInfo['user_pid'] = 0;
                $agentStatisticsInfo['user_grade'] = 0;
            }

            $agentStatisticsList[$userId]= $agentStatisticsInfo;
        }

        return Loader::model('AgentDayStatistics')->insertAll($agentStatisticsList);
    }


}