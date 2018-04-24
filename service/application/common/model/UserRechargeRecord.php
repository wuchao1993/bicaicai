<?php
/**
 * 用户充值流水表
 * @createTime 2017/5/24 10:00
 */

namespace app\common\model;

use think\Model;
use think\Config;

class UserRechargeRecord extends Model {
    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'urr_id';

    public function getInfoByOrderId($orderId) {
        $condition = [
            'urr_no' => $orderId
        ];

        $result = $this->where($condition)->find();

        return $result ? $result->toArray() : false;
    }


    public function getUserRechargeStatistics($startDate, $endDate){
        $condition = [
            'urr_confirm_time' => ['between', [$startDate, $endDate]],
            'urr_status' => Config::get('status.recharge_status')['success']
        ];
        $fields = [
            'user_id',
            'sum(urr_amount)' => 'amount',
            'count(urr_id)' => 'times',
            'sum(if(urr_is_first=1, urr_amount, 0))' => 'first_recharge'
        ];
        $result = $this->where($condition)->field($fields)->group('user_id')->order('user_id asc')->select();
        return $result ? collection($result)->toArray() : false;
    }


    public function getNeedCheckRecords($userId, $page = 0, $limit = 0){
        $condition = [
            'user_id' => $userId,
            'urr_is_withdraw' => Config::get('status.recharge_record_withdraw_status')['no'],
            'urr_status' => Config::get('status.recharge_status')['success']
        ];
        if($page && $limit){
            $result = $this->where($condition)->order('urr_id desc')->page($page)->limit($limit)->select();
        }else{
            $result = $this->where($condition)->order('urr_id desc')->select();
        }

        return $result;
    }


    public function getRechargeCount($userId){
        $condition = [
            'user_id' => $userId,
            'urr_status' => Config::get('status.recharge_status')['success']
        ];

        return $this->where($condition)->count();
    }

    public function isFirst($userId){

        $where['urr_is_first']  = 1;
        $where['user_id']       = $userId;

        return $this->where($where)->find();
    }

    public function modify($condition, $data){
        return $this->where($condition)->update($data);
    }

}