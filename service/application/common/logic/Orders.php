<?php
/**
 * 公共订单业务逻辑
 * @createTime 2017/5/10 14:17
 */

namespace app\common\logic;

use think\Cache;
use think\Loader;
use think\Model;
use think\Config;

class Orders extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 修改订单审核状态
     * @param $id
     * @param $checkStatus 审核状态
     * @param $status 订单状态
     * @param string $remark 备注
     * @return bool
     */
    public function updateCheckStatusById($id, $checkStatus, $status, $remark = '') {
        $update = [
            'so_check_status' => $checkStatus,
            'so_status' => $status,
        ];
        if (!empty($remark)) {
            $update['so_remark'] = $remark;
        }
        $ret = Loader::model('SportsOrders')->where(['so_id' => $id])->update($update);
        return false === $ret ? false : true;
    }

    /**
     * 修改订单审核状态
     * @param $orderNo
     * @param $checkStatus 审核状态
     * @param $status 订单状态
     * @param string $remark 备注
     * @return bool
     */
    public function updateCheckStatusByOrderNo($orderNo, $checkStatus, $status, $remark = '') {
        $update = [
            'so_check_status' => $checkStatus,
            'so_status' => $status,
        ];
        if (!empty($remark)) {
            $update['so_remark'] = $remark;
        }
        $ret = Loader::model('SportsOrders')->where(['so_no' => $orderNo])->update($update);
        return false === $ret ? false : true;
    }

    /**
     * 根据订单号获取对阵信息
     * @param $no
     * @param $field
     * @param bool $isCache 是否走缓存
     * @return bool|mixed
     */
    public function getInfoByNo($no, $field = '', $isCache = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'orders:order_info_'  . md5($no . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsOrders')->field($field)->where(['so_no' => $no])->find();
        if (!$info) {
            return false;
        }
        $info = $info->toArray();
        Cache::set($cacheKey, $info, Config::get('common.cache_time')['order_info']);
        return $info;
    }

    /**
     * 未派奖订单的撤销操作
     * @param $orderNo
     * @param string $remark 撤单原因
     * @param string $cancelType 撤单类型，system_cancel 系统撤单，hand_cancel 人工撤单
     * @param string $masterGameId 主盘口ID
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function handleCancel($orderNo, $remark = '', $cancelType = 'system_cancel', $masterGameId = '') {
        //事务处理
        $this->db()->startTrans();

        //获取订单信息
        $orderInfo = Loader::model('SportsOrders')
            ->lock(true) //记得加索引
            ->where(['so_no' => $orderNo])
            ->find();
        if (!$orderInfo) {
            $this->db()->commit();
            $this->errorcode = EC_CM_ORDER_CANCEL_NO_ORDER;
            return false;
        }
        $orderInfo = $orderInfo->toArray();

        //等待审核不能撤单
        if ($orderInfo['so_check_status'] == Config::get('status.order_check_status')['wait']) {
            $this->db()->commit();
            $this->errorcode = EC_CM_ORDER_CANCEL_ERROR_WAIT_CHECK;
            return false;
        }

        //判断订单状态，防止已经被修改
        $allowCancel = [
            Config::get('status.order_status')['wait'],
            Config::get('status.order_status')['wait_hand_clearing'],
            Config::get('status.order_status')['wait_cancel'],
            Config::get('status.order_status')['result_abnormal'],
        ];
        if (!in_array($orderInfo['so_status'], $allowCancel)) {
            $this->db()->commit();
            $this->errorcode = EC_CM_ORDER_CANCEL_STATUS_ERROR;
            return false;
        }

        //撤串关中的一场比赛
        if ($masterGameId) {
            $betInfo = json_decode($orderInfo['so_bet_info'], true);
            if ($orderInfo['so_event_type'] == Config::get('status.order_event_type')['parlay']) {
                foreach($betInfo as &$item) {
                    if ($masterGameId == $item['master_game_id']) {
                        $item['calculate_result'] = RESULT_ABNORMAL;
                        break;
                    }
                }

                //修改订单信息
                $update = [
                    'so_bet_info' => json_encode($betInfo, JSON_UNESCAPED_UNICODE),
                    'so_modify_time' => date('Y-m-d H:i:s'),
                ];
                !empty($remark) && $update['so_remark'] = $remark;
                $ret = Loader::model('SportsOrders')->where(['so_id' => $orderInfo['so_id']])->update($update);
                if (!$ret) {
                    $this->db()->rollback();
                    $this->errorcode = EC_CM_ORDER_CANCEL_UPDATE_STATUS_ERROR;
                    return false;
                }

                $this->db()->commit();
                return true;
            }
        }

        //user_id有索引的情况下为行级排它锁
        $userExtendInfo = Loader::model('UserExtend')
            ->field('ue_account_balance')
            ->lock(true)
            ->find($orderInfo['so_user_id']);

        //返还用户下注金额
        $ret = Loader::model('User', 'logic')->balanceAdd($orderInfo['so_user_id'], $orderInfo['so_bet_amount']);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_CANCEL_BALANCE_ADD_ERROR;
            return false;
        }

        //添加到账户明细表
        $recordData = [
            'user_id'              => $orderInfo['so_user_id'],
            'uar_source_id'        => $orderInfo['so_id'],
            'uar_source_type'      => Config::get('status.user_account_record_source_type')['sports_order'],
            'uar_transaction_type' => Config::get('status.account_record_transaction_type')['cancel_order'],
            'uar_action_type'      => Config::get('status.account_record_action_type')['deposit'],
            'uar_amount'           => $orderInfo['so_bet_amount'],
            'uar_before_balance'   => $userExtendInfo->ue_account_balance,
            'uar_after_balance'    => bcadd($userExtendInfo->ue_account_balance, $orderInfo['so_bet_amount']),
            'uar_createtime'       => date('Y-m-d H:i:s'),
            'uar_finishtime'       => date('Y-m-d H:i:s'),
            'uar_status'           => Config::get('status.account_record_status')['yes'],
        ];
        $ret = Loader::model('UserAccountRecord')->insert($recordData);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_CANCEL_ACCOUNT_RECORD_ERROR;
            return false;
        }

        //修改订单状态
        $update = [
            'so_status' => Config::get('status.order_status')[$cancelType],
            'so_modify_time' => date('Y-m-d H:i:s'),
        ];
        !empty($remark) && $update['so_remark'] = $remark;
        $ret = Loader::model('SportsOrders')->where(['so_id' => $orderInfo['so_id']])->update($update);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_CANCEL_UPDATE_STATUS_ERROR;
            return false;
        }

        $this->db()->commit();

        return true;
    }

    /**
     * 已派奖订单直接撤单
     * @param $orderNo 订单号
     * @param string $remark 备注
     * @param string $cancelType 撤单类型，system_cancel 系统撤单，hand_cancel 人工撤单
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function handleDistributedCancel($orderNo, $remark = '', $cancelType = 'hand_cancel') {
        //事务处理
        $this->db()->startTrans();

        //获取订单信息
        $orderInfo = Loader::model('SportsOrders')
            ->lock(true) //记得加索引
            ->where(['so_no' => $orderNo])
            ->find();
        if (!$orderInfo) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_DISTRIBUTED_CANCEL_NO_ORDER;
            return false;
        }
        $orderInfo = $orderInfo->toArray();

        //判断订单状态，防止已经被修改
        if ($orderInfo['so_status'] != Config::get('status.order_status')['distribute']) {
            $this->db()->commit();
            $this->errorcode = EC_CM_ORDER_CANCEL_STATUS_ERROR;
            return false;
        }

        //user_id有索引的情况下为行级排它锁
        $userExtendInfo = Loader::model('UserExtend')
            ->field('ue_account_balance')
            ->lock(true)
            ->find($orderInfo['so_user_id']);

        //用户赢钱返还给平台；和局so_bonus_no_principal为0
        $recordData = [];
        if ($orderInfo['so_bonus_no_principal'] > 0) {
            $returnAmount = bcadd($orderInfo['so_bonus_no_principal'], $orderInfo['so_rebate_amount']);

            /*****这段代码暂时不要；余额不足，直接扣成负数
            if ($userExtendInfo->ue_account_balance < $returnAmount) {
                $this->db()->rollback();
                $this->errorcode = EC_CM_ORDER_DISTRIBUTED_CANCEL_BALANCE_NOT_ENOUGH;
                return false;
            }
             *****/

            $recordData = [
                'user_id'              => $orderInfo['so_user_id'],
                'uar_source_id'        => $orderInfo['so_id'],
                'uar_source_type'      => Config::get('status.user_account_record_source_type')['sports_order'],
                'uar_transaction_type' => Config::get('status.account_record_transaction_type')['cancel_order'],
                'uar_action_type'      => Config::get('status.account_record_action_type')['fetch'],
                'uar_amount'           => $returnAmount,
                'uar_before_balance'   => $userExtendInfo->ue_account_balance,
                'uar_after_balance'    => bcsub($userExtendInfo->ue_account_balance, $returnAmount),
                'uar_createtime'       => date('Y-m-d H:i:s'),
                'uar_finishtime'       => date('Y-m-d H:i:s'),
                'uar_status'           => Config::get('status.account_record_status')['yes'],
            ];
            $ret = Loader::model('User', 'logic')->balanceDeduct($orderInfo['so_user_id'], $returnAmount);
            if (!$ret) {
                $this->db()->rollback();
                $this->errorcode = EC_CM_ORDER_DISTRIBUTED_CANCEL_BALANCE_DEDUCT_ERROR;
                return false;
            }
        //用户输钱还给他
        } elseif ($orderInfo['so_bonus_no_principal'] < 0) {
            $returnAmount = abs(bcadd($orderInfo['so_bonus_no_principal'], $orderInfo['so_rebate_amount']));
            $recordData = [
                'user_id'              => $orderInfo['so_user_id'],
                'uar_source_id'        => $orderInfo['so_id'],
                'uar_source_type'      => Config::get('status.user_account_record_source_type')['sports_order'],
                'uar_transaction_type' => Config::get('status.account_record_transaction_type')['cancel_order'],
                'uar_action_type'      => Config::get('status.account_record_action_type')['deposit'],
                'uar_amount'           => $returnAmount,
                'uar_before_balance'   => $userExtendInfo->ue_account_balance,
                'uar_after_balance'    => bcadd($userExtendInfo->ue_account_balance, $returnAmount),
                'uar_createtime'       => date('Y-m-d H:i:s'),
                'uar_finishtime'       => date('Y-m-d H:i:s'),
                'uar_status'           => Config::get('status.account_record_status')['yes'],
            ];
            $ret = Loader::model('User', 'logic')->balanceAdd($orderInfo['so_user_id'], $returnAmount);
            if (!$ret) {
                $this->db()->rollback();
                $this->errorcode = EC_CM_ORDER_DISTRIBUTED_CANCEL_BALANCE_ADD_ERROR;
                return false;
            }
        }

        //添加一条帐变记录
        if ($recordData) {
            $ret = Loader::model('UserAccountRecord')->insert($recordData);
            if (!$ret) {
                $this->db()->rollback();
                $this->errorcode = EC_CM_ORDER_DISTRIBUTED_CANCEL_ACCOUNT_RECORD_ERROR;
                return false;
            }
        }

        //撤销打码量
        $ret = $this->cancelTrafficAmount($orderInfo);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_CANCEL_TRAFFIC_AMOUNT_ERROR;
            return false;
        }

        //修改订单状态
        $betInfo = json_decode($orderInfo['so_bet_info'], true);
        foreach($betInfo as &$item) {
            if ($masterGameId) {
                if ($masterGameId == $item['master_game_id']) {
                    $item['calculate_result'] = '';
                    break;
                }
            } else {
                $item['calculate_result'] = '';
            }
        }

        $update = [
            'so_bonus'              => 0,
            'so_bonus_no_principal' => 0,
            'so_bet_status'         => 0,
            'so_status'             => Config::get('status.order_status')[$cancelType],
            'so_modify_time'        => date('Y-m-d H:i:s'),
        ];
        !empty($remark) && $update['so_remark'] = $remark;
        $ret = Loader::model('SportsOrders')->where(['so_id' => $orderInfo['so_id']])->update($update);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_DISTRIBUTED_CANCEL_UPDATE_STATUS_ERROR;
            return false;
        }

        $this->db()->commit();

        return true;
    }

    /**
     * 撤销派奖，回到等待人工结算状态
     * @param $orderNo
     * @param string $masterGameId
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function handleDistributedReturn($orderNo, $masterGameId = '') {
        //事务处理
        $this->db()->startTrans();

        //获取订单信息
        $orderInfo = Loader::model('SportsOrders')
            ->lock(true) //记得加索引
            ->where(['so_no' => $orderNo])
            ->find();
        if (!$orderInfo) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_RETURN_NO_ORDER;
            return false;
        }
        $orderInfo = $orderInfo->toArray();

        //判断订单状态
        if ($orderInfo['so_status'] != Config::get('status.order_status')['distribute']) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_RETURN_STATUS_ERROR;
            return false;
        }

        //user_id有索引的情况下为行级排它锁
        $userExtendInfo = Loader::model('UserExtend')->field('ue_account_balance')->lock(true)->find($orderInfo['so_user_id']);

        //收回奖金；奖金+返水
        $returnBonus = bcadd($orderInfo['so_bonus'], $orderInfo['so_rebate_amount']);

        /**** 这段代码暂时不要；余额不足，直接扣成负数
        if ($userExtendInfo->ue_account_balance < $returnBonus) {
            //余额不足，修改订单状态
            $update = [
                'so_status' => Config::get('status.order_status')['funds_not_enough'],
                'so_modify_time' => date('Y-m-d H:i:s'),
                'so_remark' => '余额不足',
            ];
            $ret = Loader::model('SportsOrders')->where(['so_id' => $orderInfo['so_id']])->update($update);
            $this->db()->commit();
            $this->errorcode = EC_ORDER_BALANCE_NOT_ENOUGH;
            return false;
        }
         *****/

        //余额不足，直接扣成负数
        $ret = Loader::model('User', 'logic')->balanceDeduct($orderInfo['so_user_id'], $returnBonus);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_RETURN_BALANCE_DEDUCT_ERROR;
            return false;
        }

        //添加到账户明细表
        $afterBalance = bcsub($userExtendInfo->ue_account_balance, $returnBonus);
        $accountRecordData = [
            'user_id'              => $orderInfo['so_user_id'],
            'uar_source_id'        => $orderInfo['so_id'],
            'uar_source_type'      => Config::get('status.user_account_record_source_type')['sports_order'],
            'uar_transaction_type' => Config::get('status.account_record_transaction_type')['cancel_order'],
            'uar_action_type'      => Config::get('status.account_record_action_type')['fetch'],
            'uar_amount'           => $returnBonus,
            'uar_before_balance'   => $userExtendInfo->ue_account_balance,
            'uar_after_balance'    => $afterBalance,
            'uar_createtime'       => date('Y-m-d H:i:s'),
            'uar_finishtime'       => date('Y-m-d H:i:s'),
            'uar_status'           => Config::get('status.account_record_status')['yes'],
        ];

        $ret = Loader::model('UserAccountRecord')->insert($accountRecordData);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_RETURN_ACCOUNT_RECORD_ERROR;
            return false;
        }

        //撤销打码量
        $ret = $this->cancelTrafficAmount($orderInfo);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_CANCEL_TRAFFIC_AMOUNT_ERROR;
            return false;
        }

        //修改订单状态和算奖结果
        $betInfo = json_decode($orderInfo['so_bet_info'], true);
        foreach($betInfo as &$item) {
            if ($masterGameId) {
                if ($masterGameId == $item['master_game_id']) {
                    $item['calculate_result'] = '';
                    break;
                }
            } else {
                $item['calculate_result'] = '';
            }
        }
        $update = [
            'so_bet_info'           => json_encode($betInfo, JSON_UNESCAPED_UNICODE),
            'so_bet_status'         => 0,
            'so_status'             => Config::get('status.order_status')['wait_hand_clearing'],
            'so_bonus'              => 0,
            'so_bonus_no_principal' => 0,
            'so_modify_time'        => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('SportsOrders')->where(['so_id' => $orderInfo['so_id']])->update($update);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_RETURN_UPDATE_STATUS_ERROR;
            return false;
        }

        $this->db()->commit();
        return true;
    }

    /**
     * TODO 恢复撤单
     */
    public function handleRecoverCancel() {

    }

    /**
     * 派发奖金操作，一个订单一个事务
     * @param $orderNo
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function handleBonusDistribute($orderNo) {
        //事务处理
        $this->db()->startTrans();

        //获取订单信息
        $orderInfo = Loader::model('SportsOrders')
            ->lock(true) //TODO 记得加索引
            ->where(['so_no' => $orderNo])
            ->find();
        if (!$orderInfo) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_DISTRIBUTE_ERROR;
            return false;
        }
        $orderInfo = $orderInfo->toArray();

        //判断订单状态
        if ($orderInfo['so_status'] != Config::get('status.order_status')['clearing']) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_DISTRIBUTE_STATUS_ERROR;
            return false;
        }

        //user_id有索引的情况下为行级排它锁
        $userExtendInfo = Loader::model('UserExtend')->field('ue_account_balance')->lock(true)->find($orderInfo['so_user_id']);

        //派发奖金；奖金+返水
        $bonus = bcadd($orderInfo['so_bonus'], $orderInfo['so_rebate_amount']);
        $ret = Loader::model('User', 'logic')->balanceAdd($orderInfo['so_user_id'], $bonus);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_DISTRIBUTE_BALANCE_ADD_ERROR;
            return false;
        }

        //添加到账户明细表
        $afterBalance = bcadd($userExtendInfo->ue_account_balance, $orderInfo['so_bonus']);
        $accountRecordData = [];
        if ($orderInfo['so_bonus'] > 0) {
            $accountRecordData[] = [
                'user_id'              => $orderInfo['so_user_id'],
                'uar_source_id'        => $orderInfo['so_id'],
                'uar_source_type'      => Config::get('status.user_account_record_source_type')['sports_order'],
                'uar_transaction_type' => Config::get('status.account_record_transaction_type')['bonus'],
                'uar_action_type'      => Config::get('status.account_record_action_type')['deposit'],
                'uar_amount'           => $orderInfo['so_bonus'],
                'uar_before_balance'   => $userExtendInfo->ue_account_balance,
                'uar_after_balance'    => $afterBalance,
                'uar_createtime'       => date('Y-m-d H:i:s'),
                'uar_finishtime'       => date('Y-m-d H:i:s'),
                'uar_status'           => Config::get('status.account_record_status')['yes'],
            ];
        }
        if ($orderInfo['so_rebate_amount'] > 0) {
            $accountRecordData[] = [
                'user_id'              => $orderInfo['so_user_id'],
                'uar_source_id'        => $orderInfo['so_id'],
                'uar_source_type'      => Config::get('status.user_account_record_source_type')['sports_order'],
                'uar_transaction_type' => Config::get('status.account_record_transaction_type')['sports_rebate'],
                'uar_action_type'      => Config::get('status.account_record_action_type')['deposit'],
                'uar_amount'           => $orderInfo['so_rebate_amount'],
                'uar_before_balance'   => $afterBalance,
                'uar_after_balance'    => bcadd($afterBalance, $orderInfo['so_rebate_amount']),
                'uar_createtime'       => date('Y-m-d H:i:s'),
                'uar_finishtime'       => date('Y-m-d H:i:s'),
                'uar_status'           => Config::get('status.account_record_status')['yes'],
            ];
        }

        if ($accountRecordData) {
            //TP5的insertALL竟然是这种语法 INSERT INTO SELECT ...UNION ALL SELECT...
            $ret = Loader::model('UserAccountRecord')->insertAll($accountRecordData);
            if (!$ret) {
                $this->db()->rollback();
                $this->errorcode = EC_CM_ORDER_BONUS_DISTRIBUTE_ACCOUNT_RECORD_ERROR;
                return false;
            }
        }

        //修改订单状态
        $update = [
            'so_status' => Config::get('status.order_status')['distribute'],
            'so_distribute_time' => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('SportsOrders')->where(['so_id' => $orderInfo['so_id']])->update($update);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_DISTRIBUTE_UPDATE_STATUS_ERROR;
            return false;
        }

        //计算打码量
        $ret = $this->calculateTrafficAmount($orderInfo);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_CM_ORDER_BONUS_DISTRIBUTE_CALCULATE_TRAFFIC_ERROR;
            return false;
        }

        $this->db()->commit();
        return true;
    }

    /**
     * 计算某笔订单的打码量
     * @param $orderInfo
     * @return bool
     */
    public function calculateTrafficAmount($orderInfo) {
        //和局的情况不计算打码量
        if ($orderInfo['so_bet_status'] == Config::get('status.order_bet_status')['back']) {
            return true;
        }

        //读取配置
        $siteConfig = Loader::model('SiteConfig')->getConfig('sports', 'common', ['ignore_traffic_amount_odds', 'bonus_need_traffic_amount']);

        //赔率 <=设定值 不计打码量
        $actualOdds = bcdiv($orderInfo['so_to_win'], $orderInfo['so_bet_amount']);
        if (bccomp($actualOdds, $siteConfig['ignore_traffic_amount_odds']) <= 0) {

            //如果奖金需要打码就写入ds_sports_low_odds_bonus_record表
            if ($siteConfig['bonus_need_traffic_amount'] && $orderInfo['so_bonus_no_principal'] > 0) {
                $bonusRecordInsert = [
                    'slobr_user_id'        => $orderInfo['so_user_id'],
                    'slobr_order_no'       => $orderInfo['so_no'],
                    'slobr_amount'         => $orderInfo['so_bonus_no_principal'],
                    'slobr_traffic_amount' => $orderInfo['so_bonus_no_principal'],
                ];
                $ret = Loader::model('SportsLowOddsBonusRecord')->insert($bonusRecordInsert);
                if (!$ret) {
                    return false;
                }
            }

            return true;
        }

        //计算当前订单的有效打码量
        $betValidAmount = $this->calculateBetValidAmount($orderInfo);

        return $this->handleTrafficAmount($betValidAmount, $orderInfo);
    }

    /**
     * 执行打码量
     * @param $betValidAmount 有效打码量
     * @param $orderInfo 订单信息
     * @return bool
     */
    public function handleTrafficAmount($betValidAmount, $orderInfo) {
        if ($betValidAmount <= 0) {
            return true;
        }

        //优先打SportsLowOddsBonusRecord的记录
        //TODO 可以加个字段标记是否打满，就不要计算
        $where = [
            'slobr_user_id'            => $orderInfo['so_user_id'],
            'slobr_is_withdraw'        => Config::get('status.bonus_record_withdraw_status')['no'],
            'slobr_require_bet_amount' => ['exp', '<slobr_traffic_amount'],
        ];
        $bonusRecords = Loader::model('SportsLowOddsBonusRecord')
            ->where($where)
            ->field('slobr_id,slobr_traffic_amount,slobr_require_bet_amount,slobr_create_time')
            ->order('slobr_create_time', 'desc')  //先打最近的记录
            ->select();

        if ($bonusRecords) {
            $updateBonusRecords = [];
            foreach($bonusRecords as $key => $bonusRecord) {
                if($betValidAmount <= 0) break;
                if($bonusRecord['slobr_require_bet_amount'] == $bonusRecord['slobr_traffic_amount']) continue;

                $diff = bcsub($bonusRecord['slobr_traffic_amount'],  $bonusRecord['slobr_require_bet_amount']);
                if ($betValidAmount > $diff) {
                    $requiredBetAmount = $bonusRecord['slobr_traffic_amount'];
                    $betValidAmount = bcsub($betValidAmount, $diff);
                } else {
                    $requiredBetAmount = bcadd($betValidAmount,  $bonusRecord['slobr_require_bet_amount']);
                    $betValidAmount = 0;
                }

                $updateBonusRecords[] = [
                    'slobr_id' => $bonusRecord['slobr_id'],
                    'slobr_require_bet_amount' => $requiredBetAmount
                ];
            }

            //更新打码量
            if ($updateBonusRecords) {
                $ret = Loader::model('SportsLowOddsBonusRecord')->saveAll($updateBonusRecords);
                if (!$ret) {
                    return false;
                }
            }
        }

        //奖金打完还有剩，就打充值记录
        if ($betValidAmount > 0) {
            $where = [
                'user_id'                 => $orderInfo['so_user_id'],
                'urr_is_withdraw'         => Config::get('status.recharge_record_withdraw_status')['no'],
                'urr_status'              => Config::get('status.recharge_status')['success'],
                'urr_required_bet_amount' => ['exp', '<urr_traffic_amount'],
                'urr_createtime'          => ['exp', '<\'' . $orderInfo['so_create_time'] . '\'']
            ];
            $rechargeRecords = Loader::model('UserRechargeRecord')
                ->where($where)
                ->field('urr_id,urr_traffic_amount,urr_required_bet_amount,urr_createtime')
                ->order('urr_createtime', 'desc')  //先打最近的充值记录
                ->select();
            if (!$rechargeRecords) {
                return true;
            }

            $updateRechargeRecords = [];
            foreach($rechargeRecords as $key => $rechargeRecord) {
                if($betValidAmount <= 0) break;
                if($rechargeRecord['urr_required_bet_amount'] == $rechargeRecord['urr_traffic_amount']) continue;

                if($orderInfo['so_create_time'] > $rechargeRecord['urr_createtime']) {
                    $diff = bcsub($rechargeRecord['urr_traffic_amount'],  $rechargeRecord['urr_required_bet_amount']);
                    if ($betValidAmount > $diff) {
                        $requiredBetAmount = $rechargeRecord['urr_traffic_amount'];
                        $betValidAmount = bcsub($betValidAmount, $diff);
                    } else {
                        $requiredBetAmount = bcadd($betValidAmount,  $rechargeRecord['urr_required_bet_amount']);
                        $betValidAmount = 0;
                    }

                    $updateRechargeRecords[] = [
                        'urr_id' => $rechargeRecord['urr_id'],
                        'urr_required_bet_amount' => $requiredBetAmount
                    ];
                }
            }

            //更新打码量
            if (empty($updateRechargeRecords)) {
                return true;
            }
            $ret = Loader::model('UserRechargeRecord')->saveAll($updateRechargeRecords);
            if (!$ret) {
                return false;
            }
        }

        return true;
    }

    /**
     * 计算当前订单的有效打码量
     * @param $orderInfo
     * @return int|mixed|string
     */
    public function calculateBetValidAmount($orderInfo) {
        $betValidAmount = $orderInfo['so_bet_amount'];
        if (in_array($orderInfo['so_bet_status'], [
            Config::get('status.order_bet_status')['win_half'],
            Config::get('status.order_bet_status')['lose_half'],
        ])) {
            //赢一半，输一半，记一半打码量
            $betValidAmount = bcdiv($betValidAmount, 2);
        }

        return $betValidAmount;
    }

    /**
     * 撤销某笔订单的打码量
     * @param $orderInfo
     * @return bool
     */
    public function cancelTrafficAmount($orderInfo) {
        //和局的情况不计算打码量
        if ($orderInfo['so_bet_status'] == Config::get('status.order_bet_status')['back']) {
            return true;
        }

        //判断订单是否低赔率订单
        $where = [
            'slobr_order_no' => $orderInfo['so_no'],
            'slobr_type'     => Config::get('status.bonus_record_type')['bonus'],
        ];
        $bonusRecord = Loader::model('SportsLowOddsBonusRecord')->where($where)->find();

        //低赔率订单帮用户补码
        if ($bonusRecord) {
            return $this->handleTrafficAmount($bonusRecord['slobr_traffic_amount'], $orderInfo);
        }

        //计算当前订单的有效打码量
        $betValidAmount = $this->calculateBetValidAmount($orderInfo);

        //添加一条需要打码的记录
        $cancelRecordInsert = [
            'slobr_user_id'        => $orderInfo['so_user_id'],
            'slobr_order_no'       => $orderInfo['so_no'],
            'slobr_amount'         => 0,
            'slobr_traffic_amount' => $betValidAmount,
            'slobr_type'           => Config::get('status.bonus_record_type')['cancel'],
        ];
        $ret = Loader::model('SportsLowOddsBonusRecord')->insert($cancelRecordInsert);
        if (!$ret) {
            return false;
        }

        return true;
    }

    /**
     * 返回订单状态
     * @param $status
     * @param $betStatus
     * @return string
     */
    public function getOrderStatus($status, $betStatus) {
        if($status == Config::get('status.order_status')['wait']) {
            $status = 'wait'; //等待开奖
        } elseif(($betStatus == Config::get('status.order_bet_status')['win'] ||
                $betStatus == Config::get('status.order_bet_status')['win_half']) &&
            $status == Config::get('status.order_status')['distribute']) {
            $status = 'win'; //已中奖
        } elseif($status == Config::get('status.order_status')['wait_cancel'] ||
            $status == Config::get('status.order_status')['system_cancel'] ||
            $status == Config::get('status.order_status')['hand_cancel']) {
            $status = 'cancel'; //系统撤单
        } elseif(($betStatus == Config::get('status.order_bet_status')['lose'] ||
                $betStatus == Config::get('status.order_bet_status')['lose_half']) &&
            $status == Config::get('status.order_status')['distribute']) {
            $status = 'lose'; //未中奖
        } elseif($status == Config::get('status.so_bet_status')['back'] ||
            $status == Config::get('status.order_status')['distribute']) {
            $status = 'back'; //和局
        } elseif($status == Config::get('status.order_status')['cancel_fail'] ||
            $status == Config::get('status.order_status')['result_abnormal']) {
            $status = 'abnormal'; //订单异常
        } elseif($status == Config::get('status.order_status')['clearing']) {
            $status = 'undistributed'; //等待派奖
        }  else {
            $status = 'wait'; //等待开奖
        }
        return $status;
    }

    /**
     * 判断是否是危险球
     * @param $sportType 球类类型
     * @param $eventType 赛事类型
     * @param $checkStatus 审核状态
     * @return string
     */
    public function getDangerous($sportType, $eventType, $checkStatus) {
        //是不是危险球
        $dangerous = 'not';
        if ($eventType == Config::get('status.order_event_type')['in_play_now'] && $sportType == 'football') {
            if($checkStatus == Config::get('status.order_check_status')['wait']) {
                $dangerous = 'wait'; //危险球待确认
            } elseif($checkStatus == Config::get('status.order_check_status')['yes']) {
                $dangerous = 'yes'; //危险球已确认
            } elseif($checkStatus == Config::get('status.order_check_status')['system_no'] ||
                $checkStatus == Config::get('status.order_check_status')['hand_no']) {
                $dangerous = 'no'; //危险球审核不通过
            }
        } else {
            $dangerous = 'not'; //不是危险球
        }
        return $dangerous;
    }

    /**
     * 处理每种玩法的下注信息
     * @param $betInfo 下注信息
     * [
     *      'play_type',
     *      'odds_key',
     *      'home_name',
     *      'guest_name',
     *      'ratio'
     * ]
     * @return string
     */
    public function handleBetInfoStr($betInfo) {
        switch($betInfo['play_type']) {
            case '1x2':
            case 'ft1x2':
            case '1h1x2':
                $betInfoStr = $this->handleOddsKey1x2($betInfo);
                break;
            case 'handicap':
            case 'ft_handicap':
            case '1h_handicap':
                $betInfoStr = $this->handleOddsKeyHandicap($betInfo);
                break;
            case 'ou':
            case 'ft_ou':
            case '1h_ou':
            case 'ou_pg':
            case 'ou_team':
                $betInfoStr = $this->handleOddsKeyOu($betInfo);
                break;
            case 'oe':
            case 'ft_oe':
            case '1h_oe':
                $betInfoStr = $this->handleOddsKeyOe($betInfo);
                break;
            case 'ft_correct_score':
            case '1h_correct_score':
                $betInfoStr = $this->handleOddsKeyCorrectScore($betInfo['odds_key']);
                break;
            case 'ft_total_goals':
            case '1h_total_goals':
                $betInfoStr = $this->handleOddsKeyTotalGoals($betInfo['odds_key']);
                break;
            case 'ht_ft':
                $betInfoStr = $this->handleOddsKeyHtFt($betInfo);
                break;
            default:
                $betInfoStr = '';
        }

        return $betInfoStr;
    }

    /**
     * 获取独赢玩法的下注信息
     * @param $betInfo 下注信息
     * @return string
     */
    public function handleOddsKey1x2($betInfo) {
        switch($betInfo['odds_key']) {
            case CAPOT_HOME_WIN:
            case CAPOT_1H_HOME_WIN:
                $betStr = $betInfo['home_name'];
                break;
            case CAPOT_GUEST_WIN:
            case CAPOT_1H_GUEST_WIN:
                $betStr = $betInfo['guest_name'];
                break;
            case CAPOT_TIE:
            case CAPOT_1H_TIE:
                $betStr = '和局';
                break;
            default:
                return '';
        }
        if (isset($betInfo['home_score']) && isset($betInfo['guest_score'])) {
            $betStr = $betInfo['home_score'] . ':' . $betInfo['guest_score'] . ' ' . $betStr;
        }
        return $betStr;
    }

    /**
     * 获取让球玩法的下注信息
     * @param $betInfo 下注信息
     * @return string
     */
    public function handleOddsKeyHandicap($betInfo) {
        switch($betInfo['odds_key']) {
            case HANDICAP_HOME_WIN:
            case HANDICAP_1H_HOME_WIN:
                $betStr = $betInfo['home_name'];
                break;
            case HANDICAP_GUEST_WIN:
            case HANDICAP_1H_GUEST_WIN:
                $betStr = $betInfo['guest_name'];
                break;
            default:
                return '';
        }
        if (isset($betInfo['home_score']) && isset($betInfo['guest_score'])) {
            $betStr = $betInfo['home_score'] . ':' . $betInfo['guest_score'] . ' ' . $betStr;
        }
        return $betStr;
    }

    /**
     * 获取大小球玩法的下注信息
     * @param $betInfo 下注信息
     * @return string
     */
    public function handleOddsKeyOu($betInfo) {
        switch($betInfo['odds_key']) {
            case OU_OVER:
            case OU_1H_OVER:
                $betStr = '大 ' . $betInfo['ratio'];
                break;
            case OU_UNDER:
            case OU_1H_UNDER:
                $betStr = '小 ' . $betInfo['ratio'];
                break;
            case OUH_OVER:
                $betStr = $betInfo['home_name'] . ' 大 ' . $betInfo['ratio'];
                break;
            case OUC_OVER:
                $betStr = $betInfo['guest_name'] . ' 大 ' . $betInfo['ratio'];
                break;
            case OUH_UNDER:
                $betStr = $betInfo['home_name'] . ' 小 ' . $betInfo['ratio'];
                break;
            case OUC_UNDER:
                $betStr = $betInfo['guest_name'] . ' 小 ' . $betInfo['ratio'];
                break;
            default:
                return '';
        }
        if (isset($betInfo['home_score']) && isset($betInfo['guest_score'])) {
            $betStr = $betInfo['home_score'] . ':' . $betInfo['guest_score'] . ' ' . $betStr;
        }
        return $betStr;
    }

    /**
     * 获取单双玩法的下注信息
     * @param $betInfo 下注信息
     * @return string
     */
    public function handleOddsKeyOe($betInfo) {
        switch($betInfo['odds_key']) {
            case OE_ODD:
                $betStr = '单';
                break;
            case OE_EVEN:
                $betStr = '双';
                break;
            default:
                return '';
        }
        if (isset($betInfo['home_score']) && isset($betInfo['guest_score'])) {
            $betStr = $betInfo['home_score'] . ':' . $betInfo['guest_score'] . ' ' . $betStr;
        }
        return $betStr;
    }

    /**
     * 获取波胆玩法的下注信息
     * @param $oddsKey 玩法赔率key
     * @return string
     */
    public function handleOddsKeyCorrectScore($oddsKey) {
        if($oddsKey == CORRECT_SCORE_OVH || $oddsKey == CORRECT_SCORE_OVC) {
            $betStr = '其他';
        } else {
            if(preg_match_all('/ior_h([\d]{1})c([\d]{1})/i', $oddsKey, $matches)) {
                $betStr = $matches[1][0] . ':' . $matches[2][0];
            } else {
                return '';
            }
        }
        return $betStr;
    }

    /**
     * 获取总入球玩法的下注信息
     * @param $oddsKey 玩法赔率key
     * @return string
     */
    public function handleOddsKeyTotalGoals($oddsKey) {
        if($oddsKey == TOTAL_GOALS_OVER) {
            $betStr = '7球或以上';
        } elseif($oddsKey == TOTAL_GOALS_1H_OVER) {
            $betStr = '3球或以上';
        } elseif(preg_match('/ior_t([\d]{2})/i', $oddsKey, $matches)) {
            $betStr = $matches[1][0] . '~' . $matches[1][1];
        } elseif(preg_match('/ior_ht([\d]{1})/i', $oddsKey, $matches)) {
            $betStr = $matches[1] . '球';
        } else {
            $betStr = '';
        }
        return $betStr;
    }

    /**
     * 获取半场/全场玩法的下注信息
     * @param $betInfo 下注信息
     * @return string
     */
    public function handleOddsKeyHtFt($betInfo) {
        switch($betInfo['odds_key']) {
            case HT_FT_HOME_HOME:
                $betStr = $betInfo['home_name'] . '/' . $betInfo['home_name'];
                break;
            case HT_FT_HOME_TIE:
                $betStr = $betInfo['home_name'] . '/和';
                break;
            case HT_FT_HOME_GUEST:
                $betStr = $betInfo['home_name'] . '/' . $betInfo['guest_name'];
                break;
            case HT_FT_TIE_HOME:
                $betStr = '和/' . $betInfo['home_name'];
                break;
            case HT_FT_TIE_TIE:
                $betStr = '和/和';
                break;
            case HT_FT_TIE_GUEST:
                $betStr = '和/' . $betInfo['guest_name'];
                break;
            case HT_FT_GUEST_HOME:
                $betStr = $betInfo['guest_name'] . '/' . $betInfo['home_name'];
                break;
            case HT_FT_GUEST_TIE:
                $betStr = $betInfo['guest_name'] . '/和';
                break;
            case HT_FT_GUEST_GUEST:
                $betStr = $betInfo['guest_name'] . '/' . $betInfo['guest_name'];
                break;
            default:
                return '';
        }

        return $betStr;
    }
}