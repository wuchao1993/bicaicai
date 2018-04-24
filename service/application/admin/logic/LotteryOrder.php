<?php

/**
 * 数字彩注单相关业务逻辑
 * @author
 */

namespace app\admin\logic;

use think\Collection;
use think\Config;
use think\Loader;
use think\Model;
use think\Db;
use think\db\Query;

class LotteryOrder extends \app\common\logic\LotteryOrder {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 冷数据间隔天数
     */
    protected $intervalDay = COLD_DATA_INTERVAL_DAY;

    /**
     * 获取注单列表,支持查询冷数据（new）
     * 注：冷数据查询时间，只支持“创建时间”
     * @param $params
     * @return array
     * @author jesse.lin.989@gmail.com
     */
    public function getOrderListColdData($params) {

        $order_status = config("status.lottey_order_status");

        $returnArr = [
            'totalCount'      => 0,
            'subBetAmount'    => 0,
            'subRebateAmount' => 0,
            'subWinningBonus' => 0,
            'subRealAmount'   => 0,
            'allBetAmount'    => 0,
            'allRebateAmount' => 0,
            'allWinningBonus' => 0,
            'allRealAmount'   => 0,
            'list'            => [],
        ];

        $condition = [];

        $tableYm  =  [];

        $master = true;

        $forceIndex = "";

        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {

            //限制查询间隔天数
            $day = (strtotime($params ['end_date'])-strtotime($params ['start_date']))/86400;
            if($day>70){
                $this->errorcode = EC_AD_LOTTERY_ORDER_GT_LIMIT_TIME;
                return $returnArr;
            }

            //昨天的数据被分离出去
            $splitEnd = date("Y-m-d 23:59:59",strtotime("-{$this->intervalDay} day"));

            //等于$splitTime的数据在主表
            $this->intervalDay --;
            $splitTime = date("Y-m-d 00:00:00",strtotime("-{$this->intervalDay} day"));

            if($params ['end_date']<$splitTime){
                //全部在冷表里查 //冷表是一月一表

                $tableYm = get_cold_data_ym($params ['start_date'],$params ['end_date']);

                //用于判断主查询是否有数据，没数据直接用子查询1，替代主查询
                $master = false;

            }elseif($params ['start_date']<$splitTime){
                //$params ['end_date']>=$splitTime//冷表：s--sp，主表：sp--e （sp=e）

                //冷表年月、查询区间
                $tableYm = get_cold_data_ym($params ['start_date'],$splitEnd);

                //主表查询条件
                $condition ['order_createtime'] = [['EGT', $splitTime], ['ELT', $params ['end_date']]];

            }else{
                //主表：start_date>=$splitTime
                //主表查询条件
                $condition ['order_createtime'] = [['EGT', $params ['start_date']], ['ELT', $params ['end_date']]];
            }

        }else {
            $this->errorcode = EC_AD_LOTTERY_ORDER_DATE_TIME_EMPTY;
            return $returnArr;
        }

        if(isset ($params ['order_id'])) {
            $condition ['order_id'] = $params ['order_id'];
        }

        if(isset ($params ['order_no'])) {
            $condition ['order_no'] = $params ['order_no'];
        }

        if(isset ($params ['user_name'])) {
            $user_id = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            $condition['user_id'] = $user_id;
        }

        if(isset ($params ['lottery_id'])) {
            $condition ['lottery_id'] = $params ['lottery_id'];
        }

        if(isset ($params ['issue_no'])) {
            $condition ['issue_no'] = $params ['issue_no'];
        }

        if(isset ($params ['status'])) {
            $status_str                    = implode(',', $params ['status']);
            $condition ['order_status'] = [
                'IN',
                $status_str,
            ];

            //排除已撤单状态
            $_status = array_diff($params ['status'],[$order_status['cancel']]);
        }

        $order = 'order_createtime desc';
        if(isset($params['sortType'])) {
            switch($params['sortMode']) {
                case 1:
                    $sortMode = 'desc';
                    break;
                case 2:
                    $sortMode = 'asc';
                    break;
                default:
                    $sortMode = 'desc';
                    break;
            }
            switch($params['sortType']) {
                case 1:
                    $orderField = 'issue_no';
                    break;
                case 2:
                    $orderField = 'order_bet_amount';
                    break;
                case 3:
                    $orderField = 'order_winning_bonus';
                    break;
                default:
                    break;
            }
            $order = $orderField . ' ' . $sortMode;
        }else{
            //同一创建时间，翻页读取问题
            // $order .= ",order_id desc";
        }


        $originTableName = "ds_lottery_order";
        $multiTableCount = [];
        $union = [];
        $totalAmountList = [];
        $tableIsExist = false;
        $replaceMaster = [];

        $field = 'sum(order_bet_amount) as order_bet_amount, sum(order_rebate_amount) as order_rebate_amount, sum(order_winning_bonus) as order_winning_bonus, sum(order_bet_amount) as order_bet_amount';

        if($tableYm){
            $n      = count($tableYm);
            $first  = 0;
            foreach ($tableYm as $key=>$ym){

                $newTable = '';
                $newTable = $originTableName."_".$ym['ym'];

                if(!$tableIsExist){
                    if(table_is_exist($newTable)){
                        $tableIsExist = true;
                    }else{
                        $first++;
                        continue;
                    }
                }

                $lcondition = [];
                $lcondition = $condition;
                $lcondition['order_createtime'] = [['EGT', $ym['sdate']], ['ELT', $ym['edate']]];

                if($master==false && $key==$first){
                    $replaceMaster['table']      = $newTable;
                    $replaceMaster['condition']  = $lcondition;
                }else{
                    if($n == ($key+1)){
                        //tp5 bug ,联合查询最后一条加全局条件
                        $union[] = Db::table($newTable)->where($lcondition)->fetchSql(true)->order($order)->limit($params ['num'])->page($params ['page'])->select();
                    }else{
                        $union[] = Db::table($newTable)->where($lcondition)->fetchSql(true)->select();
                    }
                }

                $multiTableCount[] = Db::table($newTable)->where($lcondition)->count();

                $lcondition ['order_status'] = !empty($_status)?['IN',$_status]:['NOT IN',[$order_status['cancel']]];
                $totalAmountList[] = Db::table($newTable)->field($field)->where($lcondition)->find();

            }
        }


        $query = new Query();

        if(!$master){
            //无主查询，且无子查询替换
            if(empty($replaceMaster)){
                return $returnArr;
            }
            //子查询1，替换主查询
            $query->setTable($replaceMaster['table']);
            $query->where($replaceMaster['condition']);
        }else{
            //主表查询
            $query->setTable($originTableName);
            $query->where($condition);
            $multiTableCount[] = Db::table($originTableName)->where($condition)->count();

            $condition['order_status'] = !empty($_status)?['IN',$_status]:['NOT IN',[$order_status['cancel']]];
            $totalAmountList[] = Db::table($originTableName)->field($field)->where($condition)->find();
        }
        if($forceIndex){
            $query->force($forceIndex);
        }
        if(!empty($union)){
            $query->union($union);
        }else{
            $query->order($order)->limit($params ['num'])->page($params ['page']);
        }

        $list =  Db::select($query);

        $count = array_sum($multiTableCount);


        //批量获取用户名称
        $userIds = extract_array($list, 'user_id');
        $userIds  = array_unique($userIds);
        $userList = Loader::model('User')->where(['user_id'=>['IN', $userIds]])->column('user_name', 'user_id');

        //批量获取游戏名称
        $lotteryIds = extract_array($list, 'lottery_id');
        $lotteryIds = array_unique($lotteryIds);
        $lotteryList = Loader::model('Lottery')->where(['lottery_id'=>['IN', $lotteryIds]])->column('lottery_name', 'lottery_id');

        //批量获取是否中奖停止追号
        $followIds = extract_array($list, 'follow_id');
        $followIds = array_unique($followIds);
        $followList = Loader::model('LotteryFollow')->where(['follow_id'=>['IN', $followIds] ])->column('follow_win_stop', 'follow_id');

        //批量获取游戏类型
        $lotteryTypeIds = extract_array($list, 'lottery_type_id');

        //批量获取游戏玩法
        $playIds = extract_array($list, 'play_id');

        //对六合彩做特殊处理
        $lotteryTypeList = Loader::model('LhcType')->where([
            'lhc_type_id' => [
                'IN',
                $lotteryTypeIds
            ]
        ])->column('lhc_type_name', 'lhc_type_id');

        $playList = Loader::model('LotteryPlay')->where([
            'play_id' => [
                'IN',
                $playIds
            ]
        ])->column('play_group_name,play_name', 'play_id');


        // 小计
        $subBetAmount    = 0;
        $subRebateAmount = 0;
        $subWinningBonus = 0;
        $subRealAmount   = 0;

        if(!empty ($list)) {
            foreach($list as &$val) {
                //排除已撤注单统计
                if(!in_array($val['order_status'],[$order_status['cancel']])){
                    $subBetAmount    += $val ['order_bet_amount'];
                    $subRebateAmount += $val ['order_rebate_amount'];
                    $subWinningBonus += $val ['order_winning_bonus'];
                    $subRealAmount   += $val ['order_winning_bonus'] - $val ['order_bet_amount'];
                }
                $val['user_name'] = isset($userList[$val['user_id']])?$userList[$val['user_id']]:'';
                $val['lottery_name'] = isset($lotteryList[$val['lottery_id']])?$lotteryList[$val['lottery_id']]:'';

                //对六合彩做特殊处理
                if( in_array($val['lottery_id'], Config::get('six.LHC_LOTTERY_ID_ALL') ) ){
                    $val['lottery_type_name'] = isset($lotteryTypeList[$val['lottery_type_id']])?$lotteryTypeList[$val['lottery_type_id']] . '（' . $val['order_bet_position'] . '）':$val['order_bet_position'];
                }else {
                    $val['lottery_type_name'] = isset($playList[$val['play_id']])?$playList[$val['play_id']]['play_group_name'].'（'.$playList[$val['play_id']]['play_name'].'）':'';

                }

                $val['win_stop'] = isset($followList[$val['follow_id']])?intval( $followList[$val['follow_id']] ):'';
            }
        }

        // 总额
        $allBetAmount       = 0;
        $allRebateAmount    = 0;
        $allWinningBonus    = 0;
        if(!empty($totalAmountList)){
            foreach ($totalAmountList as $tal){
                $allBetAmount       += $tal['order_bet_amount'];
                $allRebateAmount    += $tal['order_rebate_amount'];
                $allWinningBonus    += $tal['order_winning_bonus'];
            }
        }
        $allRealAmount   = $allWinningBonus - $allBetAmount;

        $returnArr = [
            'totalCount'      => $count,
            'subBetAmount'    => round($subBetAmount,3),
            'subRebateAmount' => round($subRebateAmount,3),
            'subWinningBonus' => round($subWinningBonus,3),
            'subRealAmount'   => round($subRealAmount,3),
            'allBetAmount'    => round($allBetAmount,3),
            'allRebateAmount' => round($allRebateAmount,3),
            'allWinningBonus' => round($allWinningBonus,3),
            'allRealAmount'   => round($allRealAmount,3),
            'list'            => $list,
        ];

        return $returnArr;
    }

    /**
     * 获取注单列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getOrderList($params) {

        $order_status = config("status.lottey_order_status");

        $returnArr = [
            'totalCount'      => 0,
            'subBetAmount'    => 0,
            'subRebateAmount' => 0,
            'subWinningBonus' => 0,
            'subRealAmount'   => 0,
            'allBetAmount'    => 0,
            'allRebateAmount' => 0,
            'allWinningBonus' => 0,
            'allRealAmount'   => 0,
            'list'            => [],
        ];

        $condition = [];

        if(isset ($params ['order_id'])) {
            $condition ['order_id'] = $params ['order_id'];
        }

        if(isset ($params ['order_no'])) {
            $condition ['order_no'] = $params ['order_no'];
        }

        if(isset ($params ['user_name'])) {
            $user_id = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            $condition['user_id'] = $user_id;
        }

        if(isset ($params ['lottery_id'])) {
            $condition ['lottery_id'] = $params ['lottery_id'];
        }

        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition ['order_createtime'] = [
                [
                    'EGT',
                    $params ['start_date'],
                ],
                [
                    'ELT',
                    $params ['end_date'],
                ],
            ];
        }else {
            return $returnArr;
        }

        if(isset ($params ['issue_no'])) {
            $condition ['issue_no'] = $params ['issue_no'];
        }

        if(isset ($params ['status'])) {
            $status_str                    = implode(',', $params ['status']);
            $condition ['order_status'] = [
                'IN',
                $status_str,
            ];

        }

        $order = 'order_createtime desc';
        if(isset($params['sortType'])) {
            switch($params['sortMode']) {
                case 1:
                    $sortMode = 'desc';
                    break;
                case 2:
                    $sortMode = 'asc';
                    break;
                default:
                    $sortMode = 'desc';
                    break;
            }
            switch($params['sortType']) {
                case 1:
                    $orderField = 'issue_no';
                    break;
                case 2:
                    $orderField = 'order_bet_amount';
                    break;
                case 3:
                    $orderField = 'order_winning_bonus';
                    break;
                default:
                    break;
            }
            $order = $orderField . ' ' . $sortMode;
        }


        $lotteryOrderModel = Loader::model('LotteryOrder');

        // 获取总条数

        $list = $lotteryOrderModel->where($condition)->order($order)->limit($params ['num'])->page($params ['page'])->select();

        //批量获取用户名称
        $userIds = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where(['user_id'=>['IN', $userIds]])->column('user_name', 'user_id');

        //批量获取游戏名称
        $lotteryIds = extract_array($list, 'lottery_id');
        $lotteryList = Loader::model('Lottery')->where(['lottery_id'=>['IN', $lotteryIds]])->column('lottery_name', 'lottery_id');

         //批量获取是否中奖停止追号
        $followIds = extract_array($list, 'follow_id');
        $followList = Loader::model('LotteryFollow')->where(['follow_id'=>['IN', $followIds] ])->column('follow_win_stop', 'follow_id');

        //批量获取游戏类型
        $lotteryTypeIds = extract_array($list, 'lottery_type_id');

        //批量获取游戏玩法
        $playIds = extract_array($list, 'play_id');

        //对六合彩做特殊处理
        $lotteryTypeList = Loader::model('LhcType')->where([
            'lhc_type_id' => [
                'IN',
                $lotteryTypeIds
            ]
        ])->column('lhc_type_name', 'lhc_type_id');
        
        $playList = Loader::model('LotteryPlay')->where([
            'play_id' => [
                'IN',
                $playIds
            ]
        ])->column('play_group_name,play_name', 'play_id');

        // 小计
        $subBetAmount    = 0;
        $subRebateAmount = 0;
        $subWinningBonus = 0;
        $subRealAmount   = 0;

        if(!empty ($list)) {
            foreach($list as $val) {
                //排除已撤注单统计
                if(!in_array($val['order_status'],[$order_status['cancel']])){
                    $subBetAmount    += $val ['order_bet_amount'];
                    $subRebateAmount += $val ['order_rebate_amount'];
                    $subWinningBonus += $val ['order_winning_bonus'];
                    $subRealAmount   += $val ['order_winning_bonus'] - $val ['order_bet_amount'];
                }
                $val['user_name'] = $userList[$val['user_id']];
                $val['lottery_name'] = $lotteryList[$val['lottery_id']];

                //对六合彩做特殊处理
                if( in_array($val['lottery_id'], Config::get('six.LHC_LOTTERY_ID_ALL') ) ){
                    $val['lottery_type_name'] = $lotteryTypeList[$val['lottery_type_id']] . '（' . $val['order_bet_position'] . '）';
                }else {
                    $val['lottery_type_name'] = $playList[$val['play_id']]['play_group_name'].'（'.$playList[$val['play_id']]['play_name'].'）';

                }
                if(!empty($followList) && !empty($val['follow_id'])){
                    $val['win_stop'] = intval( $followList[$val['follow_id']] );
                }else{
                    $val['win_stop'] = 0;
                }
            }
        }

        // 总额
        $field = 'count(*) as order_count ,sum(CASE order_status WHEN '.$order_status['cancel'].' THEN 0 ELSE order_bet_amount END) as order_bet_amount, sum(CASE order_status WHEN '.$order_status['cancel'].' THEN 0 ELSE order_rebate_amount END) as order_rebate_amount, sum( CASE order_status WHEN '.$order_status['cancel'].' THEN 0 ELSE order_winning_bonus END) as order_winning_bonus';

        $totalAmountList = $lotteryOrderModel->field($field)->where($condition)->find();

        $count           = $totalAmountList['order_count'];
        $allBetAmount    = $totalAmountList['order_bet_amount'];
        $allRebateAmount = $totalAmountList['order_rebate_amount'];
        $allWinningBonus = $totalAmountList['order_winning_bonus'];
        $allRealAmount   = $totalAmountList['order_winning_bonus'] - $totalAmountList['order_bet_amount'];

        $returnArr = [
            'totalCount'      => $count,
            'subBetAmount'    => round($subBetAmount,3),
            'subRebateAmount' => round($subRebateAmount,3),
            'subWinningBonus' => round($subWinningBonus,3),
            'subRealAmount'   => round($subRealAmount,3),
            'allBetAmount'    => round($allBetAmount,3),
            'allRebateAmount' => round($allRebateAmount,3),
            'allWinningBonus' => round($allWinningBonus,3),
            'allRealAmount'   => round($allRealAmount,3),
            'list'            => $list,
        ];

        return $returnArr;
    }

    /**
     * 获取注单详情
     *
     * @param
     *            $params
     * @return array
     */
    public function getOrderInfo($id) {
            $condition ['lo.order_id'] = $id;

            $info = Loader::model('LotteryOrder')->alias('lo')->join('Lottery l', 'lo.lottery_id=l.lottery_id', 'LEFT')->where($condition)->field('lo.*,l.lottery_name')->find()->toArray();

            //对六合彩做特殊处理
            if(!empty($info['lottery_id'])){
                if(in_array($info['lottery_id'], Config::get('six.LHC_LOTTERY_ID_ALL') ) ){
                    $lotteryTypeList = Loader::model('LhcType')->where([
                        'lhc_type_id' => [
                            'eq',
                            $info['lottery_type_id']
                        ]
                    ])->column('lhc_type_name');

                    $info ['lottery_type_name'] = $lotteryTypeList[0] . '（' . $info['order_bet_position'] . '）';

                }else {
                    $playList = Loader::model('LotteryPlay')->where([
                        'play_id' => [
                            'eq',
                            $info ['play_id']
                        ]
                    ])->field('play_group_name,play_name')->find();
                    $info ['lottery_type_name'] = $playList['play_group_name'] . '（' . $playList['play_name'] . '）';

                }
            }
            //如果彩种为时时彩 下注位置不为空
            if(!empty($info['lottery_category_id'])){
                if($info['lottery_category_id'] == SSC_CATEGORY_ID && !empty($info['order_bet_position'])){
                    $newLotteryPosition = '[';
                    $lotteryPositions = Config::get('status.order_bet_position');
                    foreach ($lotteryPositions as $val){
                        if(strpos($info['order_bet_position'],$val) !== false){
                            $newLotteryPosition .= $val.",";
                        }
                    }
                    rtrim($newLotteryPosition, ",");
                    $newLotteryPosition .= ']';
                    $info['order_bet_content'] = $newLotteryPosition.$info['order_bet_content'];
                }
            }

            $issueInfo = Loader::model('LotteryIssue', 'logic')->getIssueInfo($info, $info ['issue_no']);

            $info ['issueInfo'] = $issueInfo;

            return $info;
        }

    /**
     * 获取投注详情
     *
     * @param
     *            $params
     * @return array
     */
    public function reportByType($params) {

        if(empty($params['issue_no'])) {
            return [];
        }

        $condition['lottery_issue_no'] = $params ['issue_no'];

        if(isset ($params ['lottery_id'])) {
            $condition['lottery_id'] = $params ['lottery_id'];
        }else {
            $condition['lottery_id'] = LOTTERY_ID_HK6;
        }

        $issueInfo = Loader::model('LotteryIssue')->where($condition)->find();

        if(empty($issueInfo)) {
            return [];
        }

        $condition = [];

        if(isset ($params ['issue_no'])) {
            $condition ['lo.issue_no'] = $params ['issue_no'];
        }

        $lotteryOrderModel = Loader::model('LotteryOrder');
        $condition['lo.order_status'] = array('NEQ',Config::get('status.lottey_order_status')['cancel']);
        // 获取总条数
        $count = $lotteryOrderModel->alias('lo')->where($condition)->count();

        if($issueInfo['lottery_id'] != LOTTERY_ID_HK6) {
            $condition['lo.lottery_id'] = ['NEQ', LOTTERY_ID_HK6];
            $list = $lotteryOrderModel->alias('lo')->join('Lottery l', 'lo.lottery_id=l.lottery_id', 'LEFT')->join('LotteryType lt', 'lo.lottery_type_id=lt.lottery_type_id', 'LEFT')->field('lo.*,l.lottery_name,lt.lottery_type_name as type_name')->where($condition)->limit($params ['num'])->page($params ['page'])->select();
        } else {
            $condition['lo.lottery_id'] = LOTTERY_ID_HK6;
            $list = $lotteryOrderModel
                ->alias('lo')
                ->join('Lottery l', 'lo.lottery_id=l.lottery_id', 'LEFT')
                ->join('LhcType lt', 'lo.lottery_type_id=lt.lhc_type_id', 'LEFT')
                ->field('lo.*,l.lottery_name,lt.lhc_type_name as type_name,sum(lo.order_bet_amount) as order_bet_amount,sum(order_rebate_amount) as order_rebate_amount,sum(order_winning_bonus) as order_winning_bonus')
                ->where($condition)
                ->group('lo.lottery_type_id')
                ->select();
        }

        $returnArr = [
            'totalCount'      => $count,
            'list'            => $list,
        ];

        return $returnArr;
    }

    /**
     * 取消注单
     * @param $orderId
     */
    public function cancelOrder($orderId,$orderInfo = array()) {

        if(empty($orderInfo)){
            $orderInfo = Loader::model('LotteryOrder')->getInfo($orderId);
        }

        $userId         = $orderInfo['user_id'];
        $orderStatus    = $orderInfo['order_status'];
        $betAmount      = $orderInfo['order_bet_amount'];

        if($orderId) {
            if($orderStatus == Config::get('status.lottey_order_status')['wait']) {

                $this->startTrans();
                $orderResult = Loader::model('LotteryOrder')->cancel($orderId);
                if($orderResult == false) {
                    $this->rollback();
                    $this->errorcode = EC_AD_CANCEL_ORDER_FAIL;

                    return false;
                }

                //设置订单缓存列表
                $orderInfo['order_status'] = Config::get('status.lottey_order_status')['cancel'];
                $this->setOrderToListCache($orderInfo);

                $userBeforeBalance = Loader::model('UserExtend')->getBalance($userId);
                $extendResult      = Loader::model('UserExtend')->addMoney($userId, $betAmount);
                if($extendResult == false) {
                    $this->rollback();
                    $this->errorcode = EC_AD_CANCEL_ORDER_FAIL;

                    return false;
                }
                $userAfterBalance = Loader::model('UserExtend')->getBalance($userId);

                $sourceType      = Config::get('status.user_account_record_source_type')['order'];
                $transactionType = Config::get('status.account_record_transaction_type')['cancel_order'];
                $accountTransfer = Config::get('status.account_transfer')['in'];

                $accountRecord                         = [];
                $accountRecord['user_id']              = $userId;
                $accountRecord['uar_source_id']        = $orderId;
                $accountRecord['uar_source_type']      = $sourceType;
                $accountRecord['uar_transaction_type'] = $transactionType;
                $accountRecord['uar_action_type']      = $accountTransfer;
                $accountRecord['uar_amount']           = $betAmount;
                $accountRecord['uar_before_balance']   = $userBeforeBalance;
                $accountRecord['uar_after_balance']    = $userAfterBalance;
                $accountRecord['uar_remark']           = '后台撤单';

                $uarModel = Loader::model('UserAccountRecord');
                // 类型是投注
                $statusResult = $uarModel->setStatusEnd($orderId, $sourceType, Config::get('status.account_record_transaction_type')['bet']);

                // 类型是取消订单
                $accountResult = $uarModel->insert($accountRecord);
                if($statusResult == false || $accountResult == false) {
                    $this->rollback();
                    $this->errorcode = EC_AD_CANCEL_ORDER_FAIL;

                    return false;
                } else {
                    $this->commit();
                    return true;
                }
            } else {
                $this->errorcode = EC_AD_CANCEL_ORDER_FAIL;
                return false;
            }
        } else {
            $this->errorcode = EC_AD_ORDER_NOT_FIND;
            return false;
        }
    }


    /**
     * 刷新订单
     * @param $lotteryId
     * @param $issueNo
     */
    public function refreshOrder($orderId){

        $orderInfo = Loader::model('LotteryOrder')->getInfo($orderId);

        return $this->setOrderToListCache($orderInfo);

    }



}