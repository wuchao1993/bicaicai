<?php
namespace app\api\logic;

use think\Cache;
use think\Config;
use think\Loader;

class Withdraw{

    public $errorcode = EC_SUCCESS;

    public function getWithdrawCheckList($params)
    {
        $userId = USER_ID;
        $page = $params['page'] ? $params['page'] : 1;
        $count = $params['count'] ? $params['count'] : 10;

        $cacheName = Config::get('cache_option.prefix')['withdraw'].__FUNCTION__.$userId;
        if(Cache::has($cacheName)){
            $response = Cache::get($cacheName);
        }else{
            $needCheckRecords = Loader::model('UserRechargeRecord')->getNeedCheckRecords($userId);
            $endTime = current_datetime();
            $response = [];

            foreach ($needCheckRecords as $record) {
                $temp = [];
                $temp['id'] = $record['urr_id'];
                $temp['rechargeTime'] = $record['urr_createtime'];
                $temp['rechargeAmount'] = $record['urr_amount'];
                $temp['discountAmount'] = $record['urr_recharge_discount'];
                $temp['needBetAmount'] = $record['urr_traffic_amount'];
                $temp['realBetAmount'] = $record['urr_required_bet_amount'];
                $temp['startTime'] = $record['urr_createtime'];
                $temp['endTime'] = $endTime;
                $temp['status'] = $record['urr_required_bet_amount'] < $record['urr_traffic_amount']
                    ? Config::get('status.withdraw_check_status')['refuse']
                    : Config::get('status.withdraw_check_status')['pass'];
                $endTime = $record['urr_createtime'];
                $response[] = $temp;
            }

            if($response){
                Cache::set($cacheName, $response, 60*5);
            }
        }

        return array_slice($response, ($page-1) * $count, $count);
    }


    public function getWithdrawConfig($params){
        $userId = USER_ID;
        $cacheName = Config::get('cache_option.prefix')['withdraw'].'config:'.$userId;

        if(Cache::has($cacheName)){
            return Cache::get($cacheName);
        }

        $userInfo = Loader::model('common/User', 'logic')->getInfoByUid($userId, false, true);
        $payConfig = Loader::model('PayConfig')->getPayConfigByLevel($userInfo['ul_id']);
        $todayWithdrawDetail = Loader::model('UserWithdrawRecord')->getTodayWithdrawDetail($userId);
        $response = [
            'account_balance' => $userInfo['account_balance'],
            'allow_withdraw_amount' => $userInfo['allow_withdraw_amount'],
            'withdraw_time' => "00:00 - 24:00",
            'today_withdraw_amount' => $todayWithdrawDetail['amount'] ? $todayWithdrawDetail['amount'] : '0.00',
            'today_withdraw_times' => (int)$todayWithdrawDetail['times'],
            'everyday_withdraw_count' => $payConfig['pc_everyday_withdraw_count'],
            'everyday_withdraw_max_amount' => $payConfig['pc_everyday_withdraw_max_amount'],
            'everytime_withdraw_max_amount' => $payConfig['pc_everytime_withdraw_max_amount'],
            'everytime_withdraw_min_amount' => $payConfig['pc_everytime_withdraw_min_amount'],
            'withdrawFeeDescription' => "每日免手续费次数：".$payConfig['pc_everyday_withdraw_free_count']."次。 超出次数按(提款金额＊".$payConfig['pc_withdraw_fee']."%)收取",
            'everyday_withdraw_free_count' => $payConfig['pc_everyday_withdraw_free_count'],
            'withdraw_fee' => $payConfig['pc_withdraw_fee'],
        ];
        Cache::set($cacheName, $response, 60);

        return $response;
    }

}