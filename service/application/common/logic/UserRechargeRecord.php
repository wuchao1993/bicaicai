<?php

namespace app\common\logic;

use think\Exception;
use think\Loader;
use think\Config;
use think\Log;

class UserRechargeRecord{

    public function addOnlineRecord($orderNo, $userId, $payPlatformId, $amount, $discount, $trafficAmount, $orderCreatetime, $clientIp){
        try{
            $model = Loader::model('UserRechargeRecord');
            $order =[
                'urr_no' => $orderNo,
                'user_id' => $userId,
                'urr_recharge_account_id' => $payPlatformId,
                'urr_type' => Config::get('status.user_recharge_type')['online'],
                'urr_amount' => $amount,
                'urr_recharge_discount' => $discount,
                'urr_total_amount' => bcadd($amount, $discount, 2),
                'urr_traffic_amount' => $trafficAmount,
                'urr_required_bet_amount' => 0,
                'urr_client_ip' => $clientIp,
                'urr_status' => Config::get('status.recharge_status')['wait'],
                'urr_createtime' => $orderCreatetime,
            ];

            $result = $model->save($order);
            if($result){
                return $model->urr_id;
            }else{
                Log::info("预支付订单入库出错");
                return false;
            }
        }catch (Exception $exception){
            Log::info("预支付订单入库出错,错误信息：". $exception->getMessage());
            return false;
        }
    }

    public function updateChannId($orderId, $payPlatformId){
        $data['urr_id'] = $orderId;
        $data['urr_recharge_account_id'] = $payPlatformId;
        $result = Loader::model('UserRechargeRecord')->update($data);
        return $result;

    }

    public function updateSpecialRechargeStatus($orderId){
        $data['urr_status'] = Config::get('status.recharge_status')['success'];
        $data['urr_id'] = $orderId;
        return Loader::model('UserRechargeRecord')->update($data);
    }

}