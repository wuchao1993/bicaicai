<?php
/**
 * 数字彩投注表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;
use think\Config;

class LotteryOrder extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'order_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'order_id'                => '主键',
        'user_id'                 => '用户ID',
        'lottery_id'              => '彩票ID',
        'lottery_category_id'     => '分类',
        'issue_no'                => '期号',
        'lottery_type_id'         => '玩法类型',
        'play_id'                 => '玩法ID',
        'order_no'                => '订单号',
        'order_bet_amount'        => '下注金额',
        'order_max_bonus'         => '预计最大中奖金额',
        'order_winning_bonus'     => '实际中奖金额',
        'order_rebate_amount'     => '返水金额',
        'order_stake_count'       => '注数',
        'order_stake_price'       => '单注价格',
        'order_bet_content'       => '投注内容',
        'order_bet_position'      => '投注号位位置',
        'order_bet_odds'          => '赔率',
        'order_bet_rebate'        => '投注返点',
        'order_bet_mode'          => '投注模式',
        'order_calculate_status'  => '算奖状态',
        'order_distribute_status' => '派奖状态',
        'order_calculate_time'    => '算奖时间',
        'order_distribute_time'   => '派奖时间',
        'follow_id'               => '追号ID',
        'order_createtime'        => '创建时间',
        'order_bet_ip'            => '投注IP',
        'order_status'            => '投注状态',
        'order_pay_status'        => '支付状态',
    ];

    public function getInfo($id) {

        $condition = [
            'order_id' => $id,
        ];

        return $this->where($condition)->find()->toArray();
    }


    public function cancel($orderId) {
        $condition                 = [];
        $condition['order_id']     = $orderId;
        $condition['order_status'] = Config::get('status.lottey_order_status')['wait'];

        $data                 = [];
        $data['order_status'] = Config::get('status.lottey_order_status')['cancel'];

        return $this->where($condition)->update($data);
    }

    public function getNoPrizeOrders($lotteryId, $issueNo, $count){
        $condition = [];
        $condition['lottery_id']    = $lotteryId;
        $condition['issue_no']      = $issueNo;
        $condition['order_status']  = Config::get('status.lottey_order_status')['wait'];

        return $this->where($condition)->limit($count)->select();
    }

    public function getNoPrizeOrdersCount($lotteryId, $issueNo){
        $condition = [];
        $condition['lottery_id']    = $lotteryId;
        $condition['issue_no']      = $issueNo;
        $condition['order_status']  = Config::get('status.lottey_order_status')['wait'];

        return $this->where($condition)->count();
    }

}