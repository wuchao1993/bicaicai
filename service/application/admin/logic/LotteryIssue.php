<?php
/**
 * 数字彩开奖相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;

class LotteryIssue extends Model {

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_AD_SUCCESS;
    
    /**
     * 获取开奖列表
     * @param $params
     * @return array
     */
    public function getIssueList($params)
    {
        $condition= [];
        
        if(isset($params['issue_id'])) {
            $condition['li.lottery_issue_id'] = $params['issue_id'];
        }
        if(isset($params['issue_no'])) {
            $condition['li.lottery_issue_no'] = $params['issue_no'];
        }
        if(isset($params['lottery_id'])) {
            $condition['li.lottery_id'] = $params['lottery_id'];
        }
        if(isset($params['start_date']) && isset($params['end_date'])) {
            $condition['li.lottery_issue_end_time'] = [['EGT',$params['start_date']],['ELT',$params['end_date']]];
        }
        if(isset($params['issue_prize_status'])) {
            $condition['li.lottery_issue_prize_status'] = $params['issue_prize_status'];
        }

        $order = 'li.lottery_issue_end_time desc';
        if(isset($params['sortType'])){
            $sortMode = $params['sortMode'];
            switch ($params['sortType']){
                case 1:
                    $orderField = 'li.lottery_issue_no';
                    break;
                case 2:
                    $orderField = 'li.lottery_issue_bet_amount';
                    break;
                case 3:
                    $orderField = 'li.lottery_issue_bonus';
                    break;
                case 4:
                    $orderField = 'li.lottery_issue_end_time';
                    break;
                default:
                    break;
            }
            $order = $orderField.' '.($sortMode == 1 ? 'desc' : 'asc');
        }

        $lotteryIssueModel = Loader::model('LotteryIssue');
        
        //获取总条数
        $count = $lotteryIssueModel
                ->alias('li')
                ->join('Lottery l','li.lottery_id=l.lottery_id', 'LEFT')
                ->where($condition)
                ->count();
        
        $list = $lotteryIssueModel
                ->alias('li')
                ->join('Lottery l','li.lottery_id=l.lottery_id', 'LEFT')
                ->field('li.*,l.lottery_name')
                ->where($condition)
                ->order($order)
                ->limit($params['num'])
                ->page($params['page'])
                ->select();
        
        $returnArr = array('totalCount'=>$count, 'list'=>$list);
        
        return $returnArr;
    }
         
    /**
     * 获取开奖详情
     * @param $lotteryId
     * @param $issueNo
     * @return array
     */
    public function getIssueInfo($infoOrder, $issueNo)
    {
        $condition['lottery_id'] = $infoOrder['lottery_id'];
        $condition['lottery_issue_no'] = $issueNo;

        $info = Loader::model('LotteryIssue')->where($condition)->find();
        $lottery_issue_prize_num = !empty($info['lottery_issue_prize_num']) ? explode(',', $info['lottery_issue_prize_num']) : '';
        $info['lottery_issue_prize_num'] =  Loader::model('LotteryIssue')->formatLotteryIssuePrizeNum($infoOrder, $lottery_issue_prize_num);
        return $info;
    }    

    /**
     * 获取彩期开奖号码详情
     * @param $lotteryId
     * @param $issueNo
     * @return array
     */
    public function getIssueDetail($lotterId, $issueNo)
    {
        $condition['lottery_id'] = $lotterId;
        $condition['lottery_issue_no'] = $issueNo;

        $info = Loader::model('LotteryIssue')->where($condition)->find();
        return $info;
    }
    
    /**
     * 编辑开奖
     *
     * @param $params
     * @return array
     */
    public function editIssue($params)
    {
        $lotteryModel = Loader::model('LotteryIssue');
        
        //入库
        $data['lottery_issue_prize_num']    = $params['lottery_issue_prize_num'];
        
        $lotteryModel->save($data, ['lottery_issue_id'=> $params['lottery_issue_id']]);
        
        return true;
    }
    
    /**
     * 添加六合彩开奖
     *
     * @param $params
     * @return array
     */
    public function addLhcIssue($params)
    {
        $lotteryModel = Loader::model('LotteryIssue');
        
        //判断期号是否已经存在
        $ret = $lotteryModel->where(['lottery_issue_no' => $params['lottery_issue_no']])->count();
        if ($ret > 0) {
            $this->errorcode = EC_AD_REG_LOTTERY_ISSUE_EXISTING;
            return false;
        }

        //判断开奖时间是否正确
        if(strtotime($params['lottery_issue_start_time']) < time() || strtotime($params['lottery_issue_end_time']) < time()) {
            $this->errorcode = EC_AD_PRIZE_TIME_ERROR;
            return false;
        }

        //判断开奖号码是否有重复或者超过数字
        if($this->_checkLhcPrizeNum($params['lottery_issue_prize_num']) != true){
            return false;
        }
        
        //入库
        $data['lottery_issue_no']  	        = $params['lottery_issue_no'];
        $data['lottery_id']                 = $params['lottery_id'];
        $data['lottery_issue_start_time']   = $params['lottery_issue_start_time'];
        $data['lottery_issue_end_time']     = $params['lottery_issue_end_time'];
        $data['lottery_issue_prize_num']    = $params['lottery_issue_prize_num'];
        $data['lottery_issue_prize_status'] = $params['lottery_issue_prize_status'];
        $data['lottery_issue_status']       = $params['lottery_issue_status'];
        $data['lottery_createtime']         = date('Y-m-d H:i:s');
        
        $data= array_filter($data);
        
        $ret = $lotteryModel->save($data);
        if ($ret) {
            $gameInfo = [
                    'id' => $lotteryModel->lottery_issue_id
            ];
            return $gameInfo;
        }
        $this->errorcode = EC_AD_REG_LOTTERY_ISSUE_ERROR;
        return false;
    }
    
    /**
     * 编辑六合彩开奖
     *
     * @param $params
     * @return array
     */
    public function editLhcIssue($params)
    {
        $lotteryModel = Loader::model('LotteryIssue');

        if(empty($params['lottery_issue_id'])){
            return false;
        }

        //判断开奖号码是否有重复或者超过数字
        if($this->_checkLhcPrizeNum($params['lottery_issue_prize_num']) != true){
            return false;
        }
        
        //入库
        $data['lottery_issue_no']  	        = $params['lottery_issue_no'];
        $data['lottery_id']                 = $params['lottery_id'];
        $data['lottery_issue_start_time']   = $params['lottery_issue_start_time'];
        $data['lottery_issue_end_time']     = $params['lottery_issue_end_time'];
        $data['lottery_issue_prize_num']    = $params['lottery_issue_prize_num'];
        $data['lottery_issue_prize_status'] = $params['lottery_issue_prize_status'];
        $data['lottery_issue_status']       = $params['lottery_issue_status'];

        $lotteryModel->save($data, ['lottery_issue_id'=> $params['lottery_issue_id']]);

        return true;
    }

    private function _checkLhcPrizeNum($prizeNum){

        $issuePrizeNum = explode(',',$prizeNum);
        $issuePrizeNum = array_unique($issuePrizeNum);
        if(count($issuePrizeNum) <7) {
            $this->errorcode = EC_AD_PRIZE_NUMBER_FORMAT_ERROR;
            return false;
        }else {
            foreach($issuePrizeNum as $val) {
                if($val <1 || $val >49) {
                    $this->errorcode = EC_AD_PRIZE_NUMBER_FORMAT_ERROR;
                    return false;
                }
            }
        }
        return true;
    }


    public function cancelNoPrizeOrders($lotteryId,$issueNo,$currentCount,$totalCount,$count){
            $currentCount   = !empty($currentCount)?$currentCount:0;
            $totalCount     = !empty($totalCount)?$totalCount:0;
            $count          = !empty($count)?$count:20;
            $isFirst        = false;

            $lotteryOrderModel = Loader::model('LotteryOrder');
            $lotteryOrderLogic = Loader::model('LotteryOrder','logic');

            if($totalCount == 0){
                $isFirst = true;
                $totalCount = $lotteryOrderModel->getNoPrizeOrdersCount($lotteryId, $issueNo);
                if($totalCount<1){
                    $this->errorcode = EC_AD_CANCEL_NO_PRIZE_ORDERS_EMPTY;
                    return fasle;
                }
            }

            $noPirzeOrders = $lotteryOrderModel->getNoPrizeOrders($lotteryId, $issueNo, $count);

            $orderList = $noPirzeOrders ? collection($noPirzeOrders)->toArray() : [];

            foreach ($orderList as $orderInfo){
                if($orderInfo['order_status'] == Config::get('status.lottey_order_status')['wait']){
                    $result = $lotteryOrderLogic->cancelOrder($orderInfo['order_id'],$orderInfo);
                    if($result == false){
                        if($lotteryOrderLogic->errorcode == EC_AD_SUCCESS){
                            $this->errorcode = EC_AD_CANCEL_NO_PRIZE_ORDERS_ERROR;
                        }else{
                            $this->errorcode = $lotteryOrderLogic->errorcode;
                        }
                        return fasle;
                    }
                }
            }
            $currentCount = $currentCount+$count;
            $currentCount = $currentCount > $totalCount ? 0 : $currentCount;
            if($currentCount == 0){
                Loader::model('LotteryIssue')->updateIssuePrizeStatus($lotteryId, $issueNo, Config::get('status.issue_prize_status')['cancel']);
            }
            //行为日志
            if($isFirst){
                $lotteryIssueInfo = $this->getIssueDetail($lotteryId,$issueNo);
                Loader::model('General', 'logic')->actionLog('cancel_no_prize_orders', 'LotteryIssue', $lotteryIssueInfo['lottery_issue_id'], MEMBER_ID, json_encode($lotteryIssueInfo));
            }

            return ['currentCount'=>$currentCount,'totalCount'=>$totalCount];
    }

}