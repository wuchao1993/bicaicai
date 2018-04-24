<?php
/**
 * 用户提现流水表
 * @createTime 2017/5/24 10:04
 */

namespace app\common\model;

use think\Model;
use think\Config;

class UserWithdrawRecord extends Model {
    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'uwr_id';

    public function getUserWithdrawStatistics($startDate, $endDate){
        $condition = [
            'uwr_confirmtime' => ['between', [$startDate, $endDate]],
            'uwr_status' => Config::get('status.withdraw_status')['confirm'],
            'uwr_type' => Config::get('status.user_withdraw_type')['online']
        ];
        $fields = [
            'user_id',
            'sum(uwr_apply_amount)' => 'amount',
            'count(uwr_id)' => 'times'
        ];
        $result = $this->where($condition)->field($fields)->group('user_id')->order('user_id asc')->select();

        return $result ? collection($result)->toArray() : false;
    }

    public function getTodayWithdrawDetail($userId){
        $condition = [
            'user_id' => $userId,
            'uwr_confirmtime' => ['between', [date('Y-m-d H:s:i', time()-24*60*60), current_datetime()]],
            'uwr_status' => Config::get('status.withdraw_status')['confirm'],
        ];

        $fields = [
            'user_id',
            'sum(uwr_apply_amount)' => 'amount',
            'count(uwr_id)' => 'times'
        ];

        return $this->where($condition)->field($fields)->find();
    }

}