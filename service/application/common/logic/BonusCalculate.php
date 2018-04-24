<?php
/**
 * 算奖逻辑
 * @createTime 2017/5/8 14:56
 */

namespace app\common\logic;

use think\Config;
use think\Loader;
use think\Log;
use think\Model;

class BonusCalculate extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 计算单笔订单的奖金
     * @param $orderNo 订单号
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function calculateOrder($orderNo) {
        //事务处理
        $this->db()->startTrans();

        //获取订单信息
        $orderInfo = Loader::model('SportsOrders')
            ->field('so_id,so_user_id,so_event_type,so_st_id,so_bet_amount,so_bet_info,so_status')
            ->lock(true) //TODO 记得加索引
            ->where(['so_no' => $orderNo])
            ->find();
        $orderInfo = $orderInfo->toArray();

        //判断订单状态
        if (!in_array($orderInfo['so_status'], [Config::get('status.order_status')['wait'], Config::get('status.order_status')['wait_hand_clearing']])) {
            $this->db()->commit();
            $this->errorcode = EC_CM_ORDER_CALCULATE_BONUS_STATUS_ERROR;
            return false;
        }

        $sportInfo = Loader::model('SportsTypes', 'logic')->getInfoById($orderInfo['so_st_id']);
        $ret = $this->doCalculateOrder($orderInfo, $sportInfo['st_eng_name']);

        !$ret && $this->errorcode = EC_CM_ORDER_CALCULATE_BONUS_ERROR;
        return $ret;
    }

    /**
     * 计算并更新足球的订单信息
     * @param $order
     * @param $sport  football,basketball,tennis
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function doCalculateOrder($order, $sport) {
        $betInfo = json_decode($order['so_bet_info'], true);
        $parlay  = count($betInfo) > 1 ? true : false; //是否是综合过关
        if($parlay) {
            $result = Loader::model('common/BonusCalculate', $sport)->calculateParlay($order['so_user_id'], $betInfo, $order['so_bet_amount'], $order['so_event_type']);
        } else {
            $result = Loader::model('common/BonusCalculate', $sport)->calculateSingle($order['so_user_id'], $betInfo[0], $order['so_bet_amount'], $order['so_event_type']);
        }
        if(!$result) {
            $this->db()->commit();
            return false;
        }

        //修改订单信息
        $where = [
            'so_id' => $order['so_id']
        ];
        $update = [
            'so_bet_info'           => json_encode($result['bet_info'], JSON_UNESCAPED_UNICODE),
            'so_bet_status'         => $result['bet_status'],
            'so_rebate_amount'      => $result['rebate_amount'],
            'so_rebate_ratio'       => $result['rebate_ratio'],
            'so_bonus'              => $result['bonus'],
            'so_bonus_no_principal' => bcsub($result['bonus'], $order['so_bet_amount']),
            'so_status'             => $result['status'],
            'so_calculate_time'     => date('Y-m-d H:i:s'),
            'so_modify_time'        => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('SportsOrders')->where($where)->update($update);
        if(!$ret) {
            $this->db()->rollback();
            return false;
        }

        $this->db()->commit();
        return true;
    }
}