<?php
/**
 * 订单业务逻辑
 * @createTime 2017/4/17 14:29
 */

namespace app\api\logic;

use Filipac\Ip;
use think\Config;
use think\Loader;
use think\Model;
use think\Log;

class Orders extends \app\common\logic\Orders {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    public $message;

    /**
     * 每页显示数量
     * @var int
     */
    public $pageSize = 10;

    /**
     * 下注
     * @param $params
     * [
     *     'auto_odds' => 是否自动接受较佳赔率
     *     'bet_amount' => 下注金额
     *     'sport_id' => 球类id
     *     'event_type' => 赛事类型, in_play_now,today,early,parlay
     *     'bet_info' => [
     *         'game_id' => 盘口id,
     *         'odds' => 赔率,
     *         'odds_key' => 玩法赔率key值
     *         'ratio' => 让球数
     *         'ratio_key' => 让球数key值,
     *         'strong' => 让球玩法的谁让谁,
     *         'play_type' => 玩法,
     *     ]
     * ]
     * @return array|bool
     * @throws \think\exception\PDOException
     */
    public function bet($params) {
        Log::write('下注请求参数：' . print_r($params, true) . "\r\n user_id:" . USER_ID);

        //是否系统维护，停止下注
        if (!Loader::model('common/System', 'logic')->checkCollectStatus()) {
            $this->errorcode = COLLECT_SYSTEM_MAINTENANCE;
            return false;
        }

        //获取球类信息
        $sportInfo = Loader::model('SportsTypes')->find($params['sport_id']);

        //获取下注时详细的盘口等信息
        $orderLogic = Loader::model('Orders', $sportInfo['st_eng_name']);
        $betGameInfo = $orderLogic->getBetGameInfo($params);

        if (false === $betGameInfo) {
            $this->errorcode = $orderLogic->errorcode;
            return false;
        }
        if (isset($betGameInfo['odds']) || isset($betGameInfo['ratio'])) {
            //TODO 三个球类都要同时返回odds和ratio,年后改
            $this->errorcode = $orderLogic->errorcode;
            return $betGameInfo;
        }

        //生成订单号
        $orderNo = generate_order_number();
        $betAmount = number_format($params['bet_amount'], 3, '.', '');

        //计算可赢金额
        $toWin = $this->toWin($params['event_type'], $betAmount, $betGameInfo['bet_info']);

        //判断下注金额限制
        $playTypesLogic = Loader::model('PlayTypes', 'logic');
        $params['source_ids'] = $betGameInfo['source_ids'];
        $ret = $playTypesLogic->checkBetLimit($params, $toWin);
        if (false === $ret) {
            $this->errorcode = EC_ORDER_BET_AMOUNT_LIMIT;
            $this->message = $playTypesLogic->message;
            return false;
        }

        //滚球订单等待审核，目前只有足球有危险球
        if($params['event_type'] == 'in_play_now' && $sportInfo['st_eng_name'] == 'football') {
            $checkStatus = Config::get('status.order_check_status')['wait'];
        } else {
            $checkStatus = Config::get('status.order_check_status')['yes'];
        }
        $eventType = Config::get('status.order_event_type')[$params['event_type']];

        //开启事务
        $this->db()->startTrans();

        //判断余额是否够用；加lock(true), user_id有索引的情况下为行级排它锁
        $userExtendInfo = Loader::model('UserExtend')->field('ue_account_balance')->lock(true)->find(USER_ID);
        if (bccomp($betAmount, $userExtendInfo->ue_account_balance) == 1) {
            $this->db()->commit();
            $this->errorcode = EC_ORDER_BALANCE_NOT_ENOUGH;
            return false;
        }

        //插入订单表
        $orderData = [
            'so_user_id'         => USER_ID,
            'so_source_ids'      => $betGameInfo['source_ids'],
            'so_source_ids_from' => $betGameInfo['source_ids_from'],
            'so_no'              => $orderNo,
            'so_st_id'           => $params['sport_id'],
            'so_bet_amount'      => $betAmount,
            'so_bet_info'        => json_encode($betGameInfo['bet_info']),
            'so_event_type'      => $eventType,
            'so_check_status'    => $checkStatus,
            'so_to_win'          => $toWin,
            'so_ip'              => Ip::get(),
            'so_create_time'     => date('Y-m-d H:i:s'),
            'so_modify_time'     => date('Y-m-d H:i:s'),
        ];
        $sportsOrderModel = Loader::model('SportsOrders');
        $orderRet = $sportsOrderModel->save($orderData);
        if(false === $orderRet) {
            $this->db()->rollback();
            $this->errorcode = EC_ORDER_CREATE_ERROR;
            return false;
        }

        //扣除用户余额
        $ueRet = Loader::model('User', 'logic')->balanceDeduct(USER_ID, $params['bet_amount']);
        if(false === $ueRet) {
            $this->db()->rollback();
            $this->errorcode = EC_ORDER_DEL_ACCOUNT_BALANCE_ERROR;
            return false;
        }

        //插入用户流水表
        $recordData = [
            'user_id'              => USER_ID,
            'uar_source_id'        => $sportsOrderModel->so_id,
            'uar_source_type'      => Config::get('status.user_account_record_source_type')['sports_order'],
            'uar_transaction_type' => Config::get('status.account_record_transaction_type')['bet'],
            'uar_action_type'      => Config::get('status.account_record_action_type')['fetch'],
            'uar_amount'           => $betAmount,
            'uar_before_balance'   => $userExtendInfo->ue_account_balance,
            'uar_after_balance'    => bcsub($userExtendInfo->ue_account_balance, $betAmount),
            'uar_createtime'       => date('Y-m-d H:i:s'),
            'uar_finishtime'       => date('Y-m-d H:i:s'),
            'uar_status'           => Config::get('status.account_record_status')['yes'],
        ];
        $uarRet = Loader::model('UserAccountRecord')->save($recordData);
        if(false === $uarRet) {
            $this->db()->rollback();
            $this->errorcode = EC_ORDER_USER_ACCOUNT_RECORD_ERROR;
            return false;
        }
        $this->db()->commit();

        return ['order_no' => $orderNo];
    }

    /**
     * 计算可赢金额
     * @param $eventType 赛事类型
     * @param $betAmount 下注金额
     * @param $betInfo   注单信息
     * @return int
     */
    public function toWin($eventType, $betAmount, $betInfo) {
        //综合过关玩法是 下注金额 * (所有赔率相乘 - 1)
        if($eventType == 'parlay') {
            $odds = 1;
            foreach($betInfo as $info) {
                if(bccomp($info['odds'], '0') == 0) continue;
                $odds = bcmul($odds, $info['odds'], 10); //先不保留三位相乘
            }
            $toWin = bcmul($betAmount, (bcsub($odds, 1, 10)));
        } else {
            //单关以下这几种玩法的赔率都没有加上本金
            $noPrincipal = Config::get('common.no_principal_play_type');

            if(in_array($betInfo[0]['play_type'], $noPrincipal)) {
                $toWin = bcmul($betAmount, $betInfo[0]['odds']);
            } else {
                $toWin = bcmul($betAmount, (bcsub($betInfo[0]['odds'], 1)));
            }
        }

        return $toWin;
    }

    /**
     * 我的注单
     * @param $params
     * @return array|bool
     */
    public function mineBet($params) {
        empty($params['page']) && $params['page'] = 1;
        $where = [];
        if($params['sport_id']) {
            $where['so_st_id'] = $params['sport_id'];
        }
        $where['so_user_id'] = USER_ID;

        if (!empty($params['event_type'])) {
            if ($params['event_type'] == 'outright') {
                $where['so_source_ids_from'] = Config::get('status.order_source_ids_from')['outright'];
            } else {
                $where['so_event_type'] = Config::get('status.order_event_type')[$params['event_type']];
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
                    Config::get('status.order_status')['wait_hand_clearing'],
                    Config::get('status.order_status')['result_abnormal'],
                ]];
                break;
            case 'cancel':
                $where['so_status'] = ['IN', [
                    Config::get('status.order_status')['wait_cancel'],
                    Config::get('status.order_status')['hand_cancel'],
                    Config::get('status.order_status')['system_cancel'],
                ]];
                break;
            case 'cleared':
                $where['so_status'] = ['NOT IN', [
                    Config::get('status.order_status')['wait'],
                    Config::get('status.order_status')['wait_hand_clearing'],
                ]];
                break;
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

        //计算总数
        $total = Loader::model('SportsOrders')->where($where)->count();
        if(!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $orders = Loader::model('SportsOrders')
            ->where($where)
            ->page($params['page'], $this->pageSize)
            ->order('so_id', 'desc')
            ->select();
        if(!$orders) {
            return ['total_page' => 0, 'result' => []];
        }

        $data = [];
        $orderStatistics = Loader::model('SportsOrders')->statisticsOrdersInfo($where);
        foreach($orders as $key => $order) {
            if (Config::get('status.order_event_type_id')[$order->so_event_type] == 'in_play_now') {
                $inPlayNow = 'yes';
            } else {
                $inPlayNow = 'no';
            }
            //获取球类信息
            $sportInfo = Loader::model('SportsTypes')->find($order->so_st_id);

            $data[$key]['id']                 = $order->so_id;
            $data[$key]['bet_amount']         = $order->so_bet_amount;
            $data[$key]['sport_id']           = $order->so_st_id;
            $data[$key]['sport_name']         = $sportInfo['st_name'];
            $data[$key]['to_win']             = $order->so_to_win;
            $data[$key]['bonus']              = $order->so_bonus;
            $data[$key]['bonus_no_principal'] = $order->so_bonus_no_principal;
            $data[$key]['bet_status']         = Config::get('status.order_bet_status_id')[$order->so_bet_status];
            $data[$key]['in_play_now']        = $inPlayNow;
            $data[$key]['remark']             = $order->so_remark;
            $data[$key]['bet_time']           = $order->so_create_time;
            $data[$key]['order_no']           = $order->so_no;
            $data[$key]['sport_eng_name']     = $sportInfo['st_eng_name'];

            //获取下单前的余额
            $sourceType = Config::get('status.user_account_record_source_type')['sports_order'];
            $transactionType = Config::get('status.account_record_transaction_type')['bet'];
            $accountRecord = Loader::model('UserAccountRecord')->getInfoBySource($order->so_id, $sourceType, $transactionType);
            if ($accountRecord) {
                $data[$key]['bet_before_balance'] = $accountRecord['uar_before_balance'];
            } else {
                $data[$key]['bet_before_balance'] = 0;
            }

            //订单状态
            $data[$key]['status'] = $this->getOrderStatus($order->so_status, $order->so_bet_status);

            //处理注单信息
            $betInfo = json_decode($order->so_bet_info, true);
            $data[$key]['bet_num'] = count($betInfo);

            //返水
            $data[$key]['rebate_amount'] = $order->so_rebate_amount;
            $data[$key]['rebate_ratio']  = $order->so_rebate_ratio * 100;

            //串关
            if ($data[$key]['bet_num'] > 1) {
                $data[$key]['have_result'] = 0;
                foreach($betInfo as $info) {
                    if (isset($info['calculate_result'])) {
                        $data[$key]['have_result'] ++;
                    }

                    $info = Loader::model('Orders', $sportInfo['st_eng_name'])->getScheduleOrderInfo($info);

                    //还没算奖
                    if (!isset($info['calculate_result']) || !$info['calculate_result']) {
                        $info['calculate_result'] = 'wait';
                    }

                    //赛果
                    if (isset($info['master_game_id']) && $info['master_game_id']) {
                        $result = Loader::model('Results', $sportInfo['st_eng_name'])->getStringInfoByGameId($info['master_game_id']);
                        $info = array_merge($info, $result);
                    }
                    $data[$key]['bet_info'][] = $info;
                }
            //单关
            } else {
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

                    //赛果
                    if (isset($betInfo[0]['master_game_id'])) {
                        $result = Loader::model('Results', $sportInfo['st_eng_name'])->getStringInfoByGameId($betInfo[0]['master_game_id']);
                        $data[$key] = array_merge($data[$key], $result);
                    }
                }
            }
        }

        return [
            'total_page'         => ceil($total / $this->pageSize),
            'result'             => $data,
            'total_bet_amount'   => $orderStatistics['so_bet_amount'],
            'total_bonus'        => $orderStatistics['so_bonus'],
            'total_actual_bonus' => $orderStatistics['so_bonus_no_principal'],
            'total_to_win'       => $orderStatistics['so_to_win'],
        ];
    }

    /**
     * 根据订单ID获取订单详情
     * @param $id
     * @return array|bool
     */
    public function getInfoById($id) {
        $where = [
            'so_id' => $id,
//            'so_user_id' => USER_ID,
        ];
        $order = Loader::model('SportsOrders')->where($where)->find();
        if (!$order) {
            $this->errorcode = EC_ORDER_INFO_EMPTY;
            return false;
        }

        return $this->getRealInfo($order);
    }

    /**
     * 根据订单号获取订单详情
     * @param $orderNo
     * @return array|bool
     */
    public function getInfoByOrderNo($orderNo) {
        $where = [
            'so_no' => $orderNo,
//            'so_user_id' => USER_ID,
        ];
        $order = Loader::model('SportsOrders')->where($where)->find();
        if (!$order) {
            $this->errorcode = EC_ORDER_INFO_EMPTY;
            return false;
        }

        return $this->getRealInfo($order);
    }

    /**
     * 获取多个订单详情
     * @param $orderNos
     * @return array|bool
     */
    public function getMultiInfoByOrderNo($orderNos) {
        $orderNoArr = explode(',', $orderNos);
        $data = [];
        foreach($orderNoArr as $orderNo) {
            $where = [
                'so_no' => $orderNo,
                //'so_user_id' => USER_ID,
            ];
            $order = Loader::model('SportsOrders')->where($where)->find();
            if (!$order) {
                $this->errorcode = EC_ORDER_INFO_EMPTY;
                return false;
            }

            $data[$orderNo] = $this->getRealInfo($order);
        }

        return $data;
    }

    /**
     * 获取订单详情，冠军，单关，串关，返回的字段不一样
     * @param $order
     * @return mixed
     */
    private function getRealInfo($order) {
        //获取球类信息
        $sportInfo = Loader::model('SportsTypes')->find($order->so_st_id);

        if (Config::get('status.order_event_type_id')[$order->so_event_type] == 'in_play_now') {
            $inPlayNow = 'yes';
        } else {
            $inPlayNow = 'no';
        }
        $data['id']                 = $order->so_id;
        $data['order_no']           = $order->so_no;
        $data['bet_amount']         = $order->so_bet_amount;
        $data['to_win']             = $order->so_to_win;
        $data['bonus']              = $order->so_bonus;
        $data['bonus_no_principal'] = $order->so_bonus_no_principal;
        $data['bet_status']         = Config::get('status.order_bet_status_id')[$order->so_bet_status];
        $data['bet_time']           = $order->so_create_time;
        $data['in_play_now']        = $inPlayNow;
        $data['remark']             = $order->so_remark;
        $data['sport_id']           = $order->so_st_id;
        $data['sport_eng_name']     = $sportInfo['st_eng_name'];
        $data['sport_name']         = $sportInfo['st_name'];
        $data['event_type']         = Config::get('status.order_event_type_id')[$order->so_event_type];
        $data['event_type']         = Config::get('status.order_event_type_id')[$order->so_event_type];
        $data['event_type_name']    = Config::get('status.order_event_type_name')[$order->so_event_type];

        //获取下单前的余额
        $sourceType = Config::get('status.user_account_record_source_type')['sports_order'];
        $transactionType = Config::get('status.account_record_transaction_type')['bet'];
        $accountRecord = Loader::model('UserAccountRecord')->getInfoBySource($order->so_id, $sourceType, $transactionType);
        if ($accountRecord) {
            $data['bet_before_balance'] = $accountRecord['uar_before_balance'];
        } else {
            $data['bet_before_balance'] = 0;
        }

        //订单状态
        $data['status'] = $this->getOrderStatus($order->so_status, $order->so_bet_status);

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
                if (!isset($info['calculate_result']) || !$info['calculate_result']) {
                    $info['calculate_result'] = 'wait';
                }

                //赛果
                if (isset($info['master_game_id']) && $info['master_game_id']) {
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
}