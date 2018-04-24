<?php
/**
 * 订单业务逻辑
 * @createTime 2017/4/17 14:29
 */

namespace app\admin\logic;

use app\common\logic\Orders;
use think\Cache;
use think\Config;
use think\Loader;
use alioss\Oss;

class SportsOrders extends Orders {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 每页显示数量
     * @var int
     */
    public $pageSize = 20;

    /**
     * 订单列表
     * @param $params
     * @return array|bool
     */
    public function getList($params) {
        $where = $this->getListWhere($params);
        $sportsOrdersCount = Loader::model('SportsOrders')->where($where)->count();
        if($sportsOrdersCount) {
            $order = $this->getListOrder($params);
            $sportsOrders = Loader::model('SportsOrders')->where($where)->page($params['page'], $params['page_size'])->order($order)->select();
            if($sportsOrders) {
                return $this->dataFormat($sportsOrders, $sportsOrdersCount, $where);
            }
        }

        return ['total_count' => 0, 'list' => []];
    }

    /**
     * 格式化输出订单数据
     * @param $orders
     * @param $total
     * @param $where
     * @return array
     */
    private function dataFormat($orders, $total, $where) {
        //报表合计
        $orderStatistics = Loader::model('SportsOrders')->statisticsOrdersInfo($where);

        $userIds = array_unique(extract_array(collection($orders)->toArray(), 'so_user_id'));
        $condition['user_id'] = array('in', $userIds);
        $userNames = Loader::model('User')->where($condition)->column('user_id, user_name');

        $sportsTypes = Loader::model('SportsTypes')->column('st_id, st_name');
        $data = [];
        $subBetAmount = $subBonus = $subActualBonus = 0;
        foreach($orders as $key => $order) {
            $data[$key]['id']                 = $order->so_id;
            $data[$key]['ip']                 = $order->so_ip;
            $data[$key]['order_no']           = $order->so_no;
            $data[$key]['bet_amount']         = $order->so_bet_amount;
            $data[$key]['to_win']             = $order->so_to_win;
            $data[$key]['bonus']              = $order->so_bonus;
            $data[$key]['bonus_no_principal'] = $order->so_bonus_no_principal;
            $data[$key]['event_type']         = Config::get('status.order_event_type_name')[$order->so_event_type];
            $data[$key]['remark']             = $order->so_remark;
            $data[$key]['bet_time']           = $order->so_create_time;
            $data[$key]['schedule_id']        = $order->so_source_ids;
            if (isset($userNames[$order->so_user_id])) {
                $data[$key]['user_name'] = $userNames[$order->so_user_id];
            }

            //获取下单前的余额
            $sourceType = Config::get('status.user_account_record_source_type')['sports_order'];
            $transactionType = Config::get('status.account_record_transaction_type')['bet'];
            $accountRecord = Loader::model('UserAccountRecord')->getInfoBySource($order->so_id, $sourceType, $transactionType);
            if ($accountRecord) {
                $data[$key]['bet_before_balance'] = $accountRecord['uar_before_balance'];
            } else {
                $data[$key]['bet_before_balance'] = 0;
            }

            //计算小计
            $subBetAmount   = bcadd($subBetAmount, $order->so_bet_amount);
            $subBonus       = bcadd($subBonus, $order->so_bonus);
            $subActualBonus = bcadd($subActualBonus, $order->so_bonus_no_principal);

            //获取球类名称 
            if (isset($sportsTypes[$order->so_st_id])) {
                $data[$key]['sport_type_name'] = $sportsTypes[$order->so_st_id];
            }

            //订单状态
            $data[$key]['status'] = $this->getOrderStatus($order->so_status, $order->so_bet_status);
            $data[$key]['bet_status'] = Config::get('status.order_bet_status_id')[$order->so_bet_status];

            //处理注单信息
            $betInfo = json_decode($order->so_bet_info, true);
            $data[$key]['bet_num'] = count($betInfo);
            $sportInfo = Loader::model('SportsTypes')->find($order->so_st_id);
            if($betInfo[0]['master_game_id']) {
               $data[$key]['clearing_result'] = Loader::model('Results', $sportInfo['st_eng_name'])->getClearingResult($betInfo);
            }

            //串关
            if ($data[$key]['bet_num'] > 1) {
                $data[$key]['have_result'] = 0;
                foreach($betInfo as $info) {
                    if (isset($info['calculate_result'])) {
                        $data[$key]['have_result'] ++;
                    }
                }
            //单关
            } else {
                //获取球类信息
                $sportInfo = Loader::model('SportsTypes')->find($order->so_st_id);

                //冠军
                if ($order->so_source_ids_from == Config::get('status.order_source_ids_from')['outright']) {
                    $betInfo[0] = Loader::model('Orders', $sportInfo['st_eng_name'])->getOutrightOrderInfo($betInfo[0]);
                    $data[$key] = array_merge($data[$key], $betInfo[0]);

                //单关
                } elseif ($order->so_source_ids_from == Config::get('status.order_source_ids_from')['schedule']) {
                    $betInfo[0] = Loader::model('Orders', $sportInfo['st_eng_name'])->getScheduleOrderInfo($betInfo[0]);
                    $data[$key] = array_merge($data[$key], $betInfo[0]);

                    //是不是危险球
                    $data[$key]['dangerous'] = $this->getDangerous($sportInfo['st_eng_name'], $order->so_event_type, $order->so_check_status);
                }
            }
        }
        return [
            'total_count'        => $total,
            'total_bet_amount'   => $orderStatistics['so_bet_amount'],
            'total_bonus'        => $orderStatistics['so_bonus'],
            'total_actual_bonus' => $orderStatistics['so_bonus_no_principal'],
            'sub_bet_amount'     => $subBetAmount,
            'sub_bonus'          => $subBonus,
            'sub_actual_bonus'   => $subActualBonus,
            'list'               => $data
        ];        
    }

    /**
     * 订单列表查询条件
     * @param $params
     * @return array
     */
    private function getListWhere($params) {
        $where = [];
        empty($params['page']) && $params['page'] = 1;
        !$params['page_size']  && $params['page_size'] = $this->pageSize;
        !empty($params['sport_id'])   && $where['so_st_id'] = $params['sport_id'];
        !empty($params['order_no'])   && $where['so_no'] = $params['order_no'];
        if (!empty($params['event_type'])) {
            if ($params['event_type'] == 'outright') {
                $where['so_source_ids_from'] = Config::get('status.order_source_ids_from')['outright'];
            } else {
                $where['so_event_type'] = Config::get('status.order_event_type')[$params['event_type']];
            }
        }
        if ($params['schedule_id']) {
            $where[] = ['exp', 'FIND_IN_SET(' . $params['schedule_id'] . ',so_source_ids)'];
        }

        if ($params['start_time'] && $params['end_time']) {
            $where['so_create_time'] = ['between', [$params['start_time'] . ' 00:00:00', $params['end_time'] . ' 23:59:59']];
        } else {
            if($params['start_time']) {
                $where['so_create_time'] = ['egt', $params['start_time'] . ' 00:00:00'];
            }
            if($params['end_time']) {
                $where['so_create_time'] = ['elt', $params['end_time'] . ' 23:59:59'];
            }
        }
        switch($params['status']) {
            case 'win':
                $where['so_status'] = Config::get('status.order_status')['distribute'];
                $where['so_bet_status'] = ['IN', [
                    Config::get('status.order_bet_status')['win'],
                    Config::get('status.order_bet_status')['win_half'],
                ]];
                break;
            case 'lose':
                $where['so_status'] = Config::get('status.order_status')['distribute'];
                $where['so_bet_status'] = ['IN', [
                    Config::get('status.order_bet_status')['lose'],
                    Config::get('status.order_bet_status')['lose_half'],
                ]];
                break;
            case 'back':
                $where['so_status'] = Config::get('status.order_status')['distribute'];
                $where['so_bet_status'] = Config::get('status.order_bet_status')['back'];
                break;
            case 'wait':
                $where['so_status'] = ['IN', [
                    Config::get('status.order_status')['wait'],
                    Config::get('status.order_status')['wait_hand_clearing']
                ]];
                break;
            case 'clear':
                $where['so_status'] = Config::get('status.order_status')['distribute'];
                break;
            case 'cancel':
                $where['so_status'] = ['IN', [
                    Config::get('status.order_status')['wait_cancel'],
                    Config::get('status.order_status')['system_cancel'],
                    Config::get('status.order_status')['hand_cancel'],
                ]];
                break;
            case 'abnormal':
                $where['so_status'] = ['IN', [
                    Config::get('status.order_status')['wait_hand_clearing'],
                    Config::get('status.order_status')['wait_cancel'],
                    Config::get('status.order_status')['result_abnormal'],
                    Config::get('status.order_status')['cancel_fail'],
                    Config::get('status.order_status')['funds_not_enough'],
                ]];
                break;
        }
        if ($params['user_name']) {
            $userInfo = Loader::model('User', 'logic')->getInfoByUserName($params['user_name'], true);
            $where['so_user_id'] = $userInfo['user_id'];
        }

        !empty($params['check_status']) && $where['so_check_status'] = Config::get('status.order_check_status')[$params['check_status']];
        return $where;
    }

    /**
     * 订单列表排序
     * @param $params
     * @return array
     */
    private function getListOrder($params) {
        switch($params['order_by']) {
            case 'bet_amount_asc' :
                $orderBy = ['so_bet_amount' => 'asc', 'so_id' => 'desc'];
                break;
            case 'bet_amount_desc' :
                $orderBy = ['so_bet_amount' => 'desc', 'so_id' => 'desc'];
                break;
            case 'bonus_asc' :
                $orderBy = ['so_bonus_no_principal' => 'asc', 'so_id' => 'desc'];
                break;
            case 'bonus_desc' :
                $orderBy = ['so_bonus_no_principal' => 'desc', 'so_id' => 'desc'];
                break;
            case 'bet_time_asc' :
                $orderBy = ['so_create_time' => 'asc', 'so_id' => 'desc'];
                break;
            case 'bet_time_desc' :
                $orderBy = ['so_create_time' => 'desc', 'so_id' => 'desc'];
                break;
            default :
                $orderBy = ['so_id' => 'desc'];
                break;
        }
        return $orderBy;
    }

    /**
     * 获取订单详情，冠军，单关，串关，返回的字段不一样
     * @param $orderNo
     * @return array|bool
     */
    public function getInfoByOrderNo($orderNo) {
        $order = Loader::model('SportsOrders')->where(['so_no' => $orderNo])->find();
        if (!$order) {
            $this->errorcode = EC_ORDER_INFO_EMPTY;
            return false;
        }

        //获取球类信息
        $sportInfo = Loader::model('SportsTypes')->find($order->so_st_id);

        if (Config::get('status.order_event_type_id')[$order->so_event_type] == 'in_play_now') {
            $inPlayNow = 'yes';
        } else {
            $inPlayNow = 'no';
        }
        $data['id']                 = $order->so_id;
        $data['ip']                 = $order->so_ip;
        $data['schedule_id']        = $order->so_source_ids;
        $data['order_no']           = $order->so_no;
        $data['bet_amount']         = $order->so_bet_amount;
        $data['to_win']             = $order->so_to_win;
        $data['bonus']              = $order->so_bonus;
        $data['bonus_no_principal'] = $order->so_bonus_no_principal;
        $data['bet_time']           = $order->so_create_time;
        $data['distribute_time']    = $order->so_distribute_time;
        $data['in_play_now']        = $inPlayNow;
        $data['remark']             = $order->so_remark;
        $data['schedule_id']        = $order->so_source_ids;

        //获取下单前的余额
        $sourceType = Config::get('status.user_account_record_source_type')['sports_order'];
        $transactionType = Config::get('status.account_record_transaction_type')['bet'];
        $accountRecord = Loader::model('UserAccountRecord')->getInfoBySource($order->so_id, $sourceType, $transactionType);
        if ($accountRecord) {
            $data['bet_before_balance'] = $accountRecord['uar_before_balance'];
        } else {
            $data['bet_before_balance'] = 0;
        }

        //获取用户可用余额
        $data['balance'] = Loader::model('UserExtend','logic')->getBalance($order->so_user_id);

        //获取球类名称
        $sportsTypesInfo = Loader::model('SportsTypes', 'logic')->getInfoById($order->so_st_id);
        $data['sport_type_name'] = $sportsTypesInfo['st_name'];

        //订单状态
        $data['status'] = $this->getOrderStatus($order->so_status, $order->so_bet_status);
        $data['status_name'] = Config::get('common.order_status_name')[$data['status']];

        //处理注单信息
        $betInfo = json_decode($order->so_bet_info, true);
        $data['bet_num'] = count($betInfo);

        //返水
        $data['rebate_amount'] = $order->so_rebate_amount;
        $data['rebate_ratio']  = $order->so_rebate_ratio * 100;

        //串关
        if ($data['bet_num'] > 1) {
            $data['bet_info'] = [];
            foreach($betInfo as $info) {
                $info = Loader::model('Orders', $sportInfo['st_eng_name'])->getScheduleOrderInfo($info);

                //还没算奖
                if (!$info['calculate_result']) {
                    $info['calculate_result'] = 'wait';
                }

                //赛果
                if ($info['master_game_id']) {
                    $result = Loader::model('Results', $sportInfo['st_eng_name'])->getStringInfoByGameId($info['master_game_id']);
                    $info = array_merge($info, $result);
                }
                $data['bet_info'][] = $info;
            }
        } else {
            //冠军
            if ($order->so_source_ids_from == Config::get('status.order_source_ids_from')['outright']) {
                $betInfo[0] = Loader::model('Orders', $sportInfo['st_eng_name'])->getOutrightOrderInfo($betInfo[0]);
                $data = array_merge($data, $betInfo[0]);

            //单关
            } elseif ($order->so_source_ids_from == Config::get('status.order_source_ids_from')['schedule']) {
                $betInfo[0] = Loader::model('Orders', $sportInfo['st_eng_name'])->getScheduleOrderInfo($betInfo[0]);
                $data = array_merge($data, $betInfo[0]);

                //是不是危险球
                $data['dangerous'] = $this->getDangerous($sportInfo['st_eng_name'], $order->so_event_type, $order->so_check_status);

                //赛果
                if (isset($betInfo[0]['master_game_id'])) {
                    $result = Loader::model('Results', $sportInfo['st_eng_name'])->getStringInfoByGameId($betInfo[0]['master_game_id']);
                    $data = array_merge($data, $result);
                }
            }
        }
        return $data;
    }

    /**
     * 滚球订单审核
     * @param $orderNo 订单号
     * @param $status 状态
     * @param $remark 备注
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function check($orderNo, $status, $remark) {
        //判断订单是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_order_lock'] . $orderNo;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_ORDER_LOCKED;
            return false;
        }

        //事务处理
        $this->db()->startTrans();

        //获取订单信息
        $orderInfo = Loader::model('SportsOrders')->field('so_check_status')
            ->lock(true)
            ->where(['so_no' => $orderNo])
            ->find();
        if (!$orderInfo) {
            $this->db()->rollback();
            $this->errorcode = EC_AD_CHECK_ERROR;
            return false;
        }
        $orderInfo = $orderInfo->toArray();

        if ($orderInfo['so_check_status'] != Config::get('status.order_check_status')['wait']) {
            $this->db()->rollback();
            $this->errorcode = EC_AD_CHECK_ERROR_NOT_WAIT;
            return false;
        }

        if ($status == 'yes') {
            $checkStatus = Config::get('status.order_check_status')['yes'];
            $status = Config::get('status.order_status')['wait'];
        } elseif ($status == 'hand_no') {
            $checkStatus = Config::get('status.order_check_status')['hand_no'];
            $status = Config::get('status.order_status')['wait_cancel'];
        } else {
            $this->errorcode = EC_AD_CHECK_STATUS_ERROR;
            return false;
        }

        //修改状态
        $ret = Loader::model('Orders', 'logic')->updateCheckStatusByOrderNo($orderNo, $checkStatus, $status, $remark);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_AD_CHECK_ERROR;
            return false;
        }

        $this->db()->commit();
        return true;
    }

    /**
     * 未结算订单撤单
     * @param $orderNo
     * @param $remark
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function cancel($orderNo, $remark) {
        //判断订单是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_order_lock'] . $orderNo;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_ORDER_LOCKED;
            return false;
        }

        return $this->handleCancel($orderNo, $remark, 'hand_cancel');
    }

    /**
     * 人工订单结算，派奖还是程序自动派
     * @param $orderNo
     * @return bool
     */
    public function clearing($orderNo) {
        //判断订单是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_order_lock'] . $orderNo;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_ORDER_LOCKED;
            return false;
        }

        $bcLogic = Loader::model('common/BonusCalculate', 'logic');
        $ret = $bcLogic->calculateOrder($orderNo);
        if (!$ret) {
            $this->errorcode = $bcLogic->errorcode;
        }
        return $ret;
    }

    /**
     * 撤销结算，后续操作需要人工操作
     * @param $orderNo
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function cancelClearing($orderNo) {
        //判断订单是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_order_lock'] . $orderNo;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_ORDER_LOCKED;
            return false;
        }

        return $this->handleDistributedReturn($orderNo);
    }

    /**
     * 修改异常订单状态
     * @param
     * @return bool
     */
    public function editAbnormalOrder($orderNo) {
        //获取订单状态
        $orderStatus = Loader::model('Orders', 'logic')->getInfoByNo($orderNo, 'so_status'); 
        switch ($orderStatus['so_status']) {
           case Config::get('status.order_status')['funds_not_enough']:
               $checkStatus = Config::get('status.order_check_status')['yes'];
               $status = Config::get('status.order_status')['distribute'];
               return Loader::model('Orders', 'logic')->updateCheckStatusByOrderNo($orderNo, $checkStatus, $status);
               break;
           case Config::get('status.order_status')['cancel_fail']:
               $checkStatus = Config::get('status.order_check_status')['yes'];
               $status = Config::get('status.order_status')['distribute'];
               return Loader::model('Orders', 'logic')->updateCheckStatusByOrderNo($orderNo, $checkStatus, $status);
               break;                           
           case Config::get('status.order_status')['wait_hand_clearing']:
               $checkStatus = Config::get('status.order_check_status')['yes'];
               $status = Config::get('status.order_status')['wait'];
               return Loader::model('Orders', 'logic')->updateCheckStatusByOrderNo($orderNo, $checkStatus, $status);
               break;  
            default:
            return false;
               break;  
       }       
    }

    /**
     * 综合过关设置其中一个无效
     * @param $orderNo
     * @param $gameId
     * @return bool
     */
    public function parlayCancel($orderNo, $gameId) {
        //判断订单是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_order_lock'] . $orderNo;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_ORDER_LOCKED;
            return false;
        }

        $orderInfo = Loader::model('Orders', 'logic')->getInfoByNo($orderNo, 'so_id,so_bet_info,so_status');
        if (!$orderInfo) {
            $this->errorcode = EC_AD_PARLAY_HANDLE_ERROR_NO_ORDER;
            return false;
        }

        //判断订单状态
        if (!in_array($orderInfo['so_status'], [
            Config::get('status.order_status')['wait'],
            Config::get('status.order_status')['wait_hand_clearing'],
        ])) {
            $this->errorcode = EC_AD_PARLAY_ORDER_STATUS_NOT_AVAILABLE;
            return false;
        }
        $betInfo = json_decode($orderInfo['so_bet_info'], true);
        foreach($betInfo as $key => $item) {
            if ($item['game_id'] == $gameId) {
                $betInfo[$key]['calculate_result'] = RESULT_ABNORMAL;
                $betInfoJson = json_encode($betInfo, JSON_UNESCAPED_UNICODE);
                $ret = Loader::model('SportsOrders')->where(['so_id' => $orderInfo['so_id']])->update(['so_bet_info' => $betInfoJson]);
                if ($ret === false) {
                    $this->errorcode = EC_AD_PARLAY_CANCEL_ERROR;
                    return false;
                }
                break;
            }
        }
        return true;
    }

    /**
     * 综合过关其中一个算奖
     * @param $orderNo
     * @param $gameId
     * @return bool
     */
    public function parlayClearing($orderNo, $gameId) {
        //判断订单是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_order_lock'] . $orderNo;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_ORDER_LOCKED;
            return false;
        }

        $orderInfo = Loader::model('Orders', 'logic')->getInfoByNo($orderNo, 'so_id,so_st_id,so_status,so_bet_info,so_event_type');
        if (!$orderInfo) {
            $this->errorcode = EC_AD_PARLAY_HANDLE_ERROR_NO_ORDER;
            return false;
        }

        //判断订单状态
        if (!in_array($orderInfo['so_status'], [
            Config::get('status.order_status')['wait'],
            Config::get('status.order_status')['wait_hand_clearing'],
        ])) {
            $this->errorcode = EC_AD_PARLAY_ORDER_STATUS_NOT_AVAILABLE;
            return false;
        }

        //获取球类信息
        $sportInfo = Loader::model('SportsTypes')->find($orderInfo['so_st_id']);

        $betInfo = json_decode($orderInfo['so_bet_info'], true);
        foreach($betInfo as $key => $item) {
            if ($item['game_id'] == $gameId) {
                $betInfo[$key] = Loader::model('BonusCalculate', $sportInfo['st_eng_name'])->getCalculateResult($item, $orderInfo['so_event_type']);
                if (!$betInfo[$key]) {
                    $this->errorcode = EC_AD_PARLAY_CLEARING_ERROR_NO_RESULT;
                    return false;
                }
                $betInfoJson = json_encode($betInfo, JSON_UNESCAPED_UNICODE);
                $ret = Loader::model('SportsOrders')->where(['so_id' => $orderInfo['so_id']])->update(['so_bet_info' => $betInfoJson]);
                if ($ret === false) {
                    $this->errorcode = EC_AD_PARLAY_CLEARING_ERROR;
                    return false;
                }
                break;
            }
        }
        return true;
    }

    /**
     * 综合过关其中一个撤销结算
     * @param $orderNo
     * @param $gameId
     * @return bool
     */
    public function parlayCancelClearing($orderNo, $gameId) {
        //判断订单是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_order_lock'] . $orderNo;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_ORDER_LOCKED;
            return false;
        }

        //如果订单已经结算，那不能撤销结算其中一个
        $orderInfo = Loader::model('Orders', 'logic')->getInfoByNo($orderNo, 'so_id,so_status,so_bet_info');
        if (!$orderInfo) {
            $this->errorcode = EC_AD_PARLAY_HANDLE_ERROR_NO_ORDER;
            return false;
        }

        //判断订单状态
        if (!in_array($orderInfo['so_status'], [
            Config::get('status.order_status')['wait'],
            Config::get('status.order_status')['wait_hand_clearing'],
        ])) {
            $this->errorcode = EC_AD_PARLAY_ORDER_STATUS_NOT_AVAILABLE;
            return false;
        }

        $betInfo = json_decode($orderInfo['so_bet_info'], true);
        foreach($betInfo as $key => $item) {
            if ($item['game_id'] == $gameId) {
                unset($betInfo[$key]['calculate_result']);
                $betInfoJson = json_encode($betInfo, JSON_UNESCAPED_UNICODE);
                $ret = Loader::model('SportsOrders')->where(['so_id' => $orderInfo['so_id']])->update(['so_bet_info' => $betInfoJson]);
                if ($ret === false) {
                    $this->errorcode = EC_AD_PARLAY_CANCEL_ERROR;
                    return false;
                }
                break;
            }
        }
        return true;
    }


    /**
     * 获取导出订单列表
     */
    public function getExportOrderList($params)
    {
        $startTime = $params['start_time'] ? strtotime($params['start_time']) : time() - 6*24*60*60;
        $endTime = $params['end_time'] ? strtotime($params['end_time']) : time();
        if ($endTime >= $startTime + 3600 * 24 * 7) {
            $this->errorcode = EC_AD_REPORT_DAY_LIMIT_SEVEN_ERROR;
            return false;
        }

        $params['start_time'] = date('Y-m-d 00:00:00', $startTime);
        $params['end_time'] = date('Y-m-d 23:59:59', $endTime);

        if (!empty($params['event_type'])) {
            $where['so_event_type'] = Config::get('status.order_event_type')[$params['event_type']];
            $evenType = Config::get('status.order_event_type_name')[$where['so_event_type']];
        }
        $where['so_distribute_time'] = ['between', [$params['start_time'], $params['end_time']]];
        if (!empty($params['sport_id'])) {
            $where['so_st_id'] = $params['sport_id'];
            $sportsTypeInfo = Loader::model('common/SportsTypes', 'logic')->getInfoById($params['sport_id']);
            $sportName = $sportsTypeInfo['st_name'];
        }
        if ($params['user_name']) {
            $userInfo = Loader::model('User', 'logic')->getInfoByUserName($params['user_name'], true);
            $where['so_user_id'] = $userInfo['user_id'];
        }
        $where['so_status'] = Config::get('status.order_status')['distribute'];

        $sportsOrders = Loader::model('SportsOrders')->where($where)->order('so_distribute_time desc')->select();

        if ($sportsOrders) {
            $orderList = $this->exportDataFormat($sportsOrders);
        } else {
            $this->errorcode = EC_AD_EMPTY_DATA;
            return false;
        }
        $fileName = $params['user_name'] . '-' . $sportName . $evenType . '注单列表' . '-' . date('Ymd', $startTime). '-' . date('Ymd', $endTime);
        $title = ['用户名', '有效投注额', '结算日期', '注单状态'];

        return $this->_exportExcel($orderList, $title, $fileName);
    }

    public function exportDataFormat($data){
        $siteConfig = Loader::model('SiteConfig')->getConfig('sports', 'common', ['ignore_traffic_amount_odds']);

        $userIds = array_unique(extract_array(collection($data)->toArray(), 'so_user_id'));
        $condition['user_id'] = array('in', $userIds);
        $userNames = Loader::model('User')->where($condition)->column('user_id, user_name');
        foreach($data as  $key => $val) {
            //是否和局
            if($val['so_bet_status'] == Config::get('status.order_bet_status')['back']) {
                continue;
            }
            $winHalf = Config::get('status.order_bet_status')['win_half'];
            $loseHalf = Config::get('status.order_bet_status')['lose_half'];
            if($val['so_bet_status'] == $winHalf || $val['so_bet_status'] == $loseHalf) {
                $exportData[$key]['bet_amount'] = $val['so_bet_amount'] / 2;
            }else{
                $exportData[$key]['bet_amount'] = $val['so_bet_amount'];                
            }
            //是否小赔率
            $actualOdds = bcdiv($val['so_to_win'], $val['so_bet_amount']);
            if(bccomp($actualOdds, $siteConfig['ignore_traffic_amount_odds']) <= 0) {
                continue;
            }
            if (isset($userNames[$val->so_user_id])) {
                $exportData[$key]['user_name'] = $userNames[$val->so_user_id];
            }
            $exportData[$key]['distribute_time'] = $val['so_distribute_time'];
            $exportData[$key]['order_status'] = '已派奖';
        } 
        return $exportData;
    }


    private function _exportExcel($list, $title, $fileName) {
        $localFilePath  = 'uploads' . DS . $fileName;
        Loader::model('ReportExcel', 'logic')->ExportList($list, $title, $localFilePath);
        $ossFileName = $localFilePath.'.xls';
        return $ossFileName;

        $ossClient = Oss::getInstance();
        $bucket    = Oss::getBucketName();
        $data      = $ossClient->uploadFile($bucket, $fileName.'.xls', ROOT_PATH . 'public' . DS .$ossFileName);
        if($data){
            $ossFileUrl = $data['info']['url'];
            unlink(ROOT_PATH . 'public' . DS .$ossFileName);
            return $ossFileUrl;
        }else{
            $this->errorcode = EC_AD_REPORT_EXCEL_FAIL;
            return false;
        }
    }


}