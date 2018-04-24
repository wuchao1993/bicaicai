<?php

namespace app\admin\logic;


use think\Loader;
use think\Config;
use alioss\Oss;

class Report{

    public $errorcode = EC_SUCCESS;

    private $startDate;
    private $endDate;

    private function whereDate($params){

        $startDate  = $params['start_date'];
        $endDate    = $params['end_date'];

        if($startDate == $endDate){
            $this->startDate = $startDate ? date('Y-m-d 00:00:00', strtotime($startDate)) : date('Y-m-d 00:00:00');
            $this->endDate = $endDate ? date('Y-m-d 23:59:59', strtotime($endDate)) : date('Y-m-d 23:59:59');
        }else{
            $this->startDate = $startDate;
            $this->endDate   = $endDate;
        }

    }

    private function rechargeCondition(){
        $condition = array();

        $condition['urr_confirm_time']  = array('between', array($this->startDate, $this->endDate));
        $condition['urr_status']        = Config::get('status.recharge_status')['success'];
        $condition['urr_amount']        = array('gt',0);

        return $condition;
    }

    private function withdrawCondition($artificialOut=''){
        $condition = array();

        $condition['uwr_confirmtime'] = array('between',array($this->startDate, $this->endDate));
        $condition['uwr_status']      = Config::get('status.withdraw_status') ['confirm'];
        if ($artificialOut) {
            $condition['uwr_type']        = Config::get('status.user_withdraw_type')['system'];
            $condition['uwr_operation_type']    = Config::get('status.user_withdraw_system_type')['recharge_mistake'];
        }else{
            $condition['uwr_type']        = Config::get('status.user_withdraw_type')['online'];
        }

        return $condition;
    }

    private function accountCondition(){
        $condition = array();

        $condition['uar_finishtime'] = array('between', array($this->startDate, $this->endDate));
        $condition['uar_transaction_type'] = array('in', array(
            Config::get('status.account_record_transaction_type') ['artificial_out'],
            Config::get('status.account_record_transaction_type') ['discount'],
            Config::get('status.account_record_transaction_type') ['withdraw_deduct']
        ));
        $condition['uar_status'] = Config::get('status.account_record_status') ['yes'];

        return $condition;
    }

    private function accountRebateCondition(){
        $condition = array();

        $condition['uar_finishtime'] = array('between', array($this->startDate, $this->endDate));
        $condition['uar_transaction_type'] = array('in', array(
            Config::get('status.account_record_transaction_type') ['rebate'],
            Config::get('status.account_record_transaction_type') ['agent_rebate']
        ));
        $condition['uar_status'] = Config::get('status.account_record_status') ['yes'];

        return $condition;
    }

    /**
     * 出入账目汇总
     */
    public function getOutinReport($params) {

        $this->whereDate($params);

        $recharge_where = $this->rechargeCondition();
        $urr_fields = "urr_type type,IFNULL(SUM(urr_amount), 0) amount, count(distinct user_id) people";
        $in_list = Loader::model("UserRechargeRecord")->where($recharge_where)->field($urr_fields)->group("urr_type")->select();

        $withdraw_where = $this->withdrawCondition();
        $uwr_fields = "IFNULL(SUM(uwr_apply_amount), 0) amount, count(distinct user_id) people";
        $out = Loader::model('UserWithdrawRecord')->where($withdraw_where)->field($uwr_fields)->find();  

        $account_where = $this->accountCondition();
        $fields = 'uar_transaction_type type, IFNULL(SUM(uar_amount), 0) amount, count(distinct user_id) people';
        $list = Loader::model('UserAccountRecord')->where($account_where)->field($fields)->group('uar_transaction_type')->select();

        $account_rebate_where = $this->accountRebateCondition();
        $rebate =  Loader::model('UserAccountRecord')->where($account_rebate_where)->field($fields)->find();

        $sum = array(
            'company_in'             => 0,
            'company_people'         => 0,
            'online_in'              => 0,
            'online_in_people'       => 0,
            'artificial_in'          => 0,
            'artificial_in_people'   => 0,
            'withdraw_deduct'        => 0,
            'withdraw_deduct_people' => 0,
            'artificial_out'         => 0,
            'artificial_out_system'  => 0,
            'artificial_out_people'  => 0,
            'member_out'             => 0,
            'member_out_people'      => 0,
            'give_discount'          => 0,
            'give_discount_people'   => 0,
            'rebate'                 => 0,
            'rebate_people'          => 0,
            'first_recharge'         => 0,
            'first_recharge_people'  => 0,
            'first_withdraw'         => 0,
            'first_withdraw_people'  => 0,
            'total'                  => 0,
            'total_in'               => 0,
            'total_in_people'        => 0,
            'total_out'              => 0,
            'total_out_people'       => 0,
        );

        if(!empty($in_list)){
            foreach ($in_list as $in){
                switch($in['type']){
                    case Config::get('status.user_recharge_type') ['company']:
                        $sum['company_in']          = $in['amount'];
                        $sum['company_people']   = $in['people'];
                        break;
                    case Config::get('status.user_recharge_type') ['online']:
                        $sum['online_in']           = $in['amount'];
                        $sum['online_in_people']    = $in['people'];
                        break;
                    case Config::get('status.user_recharge_type') ['system']:
                        $sum['artificial_in']           = $in['amount'];
                        $sum['artificial_in_people']    = $in['people'];
                        break;
                }
            }
        }

        if(!empty($out)){
            $sum['member_out']          = $out['amount'];
            $sum['member_out_people']   = $out['people'];
        }

        if (!empty($list)) {
            foreach ($list as $v) {
                switch ($v['type']) {
                    case Config::get('status.account_record_transaction_type') ['artificial_out']:
                        $sum['artificial_out'] = $v['amount'];
                        $sum['artificial_out_people'] = $v['people'];
                        break;
                    case Config::get('status.account_record_transaction_type') ['discount']:
                        $sum['give_discount'] = $v['amount'];
                        $sum['give_discount_people'] = $v['people'];
                        break;
                    case Config::get('status.account_record_transaction_type') ['withdraw_deduct']:
                        $sum['withdraw_deduct'] = $v['amount'];
                        $sum['withdraw_deduct_people'] = $v['people'];
                        break;
                    default:
                        break;
                }
            }
        }

        if(!empty($rebate)){
            $sum['rebate']          = $rebate['amount'];
            $sum['rebate_people']   = $rebate['people'];
        }

        list($sum['first_recharge'],$sum['first_recharge_people']) = $this->firstRecharge();
        list($sum['first_withdraw'],$sum['first_withdraw_people']) = $this->firstWithdraw();

        $sum['total']                   = $sum['company_in'] + $sum['online_in'] + $sum['artificial_in'] + $sum['withdraw_deduct'] - $sum['artificial_out'] - $sum['member_out'] - $sum['give_discount'] - $sum['rebate'];

        $sum['total_in']               = $sum['company_in'] + $sum['online_in'] + $sum['artificial_in'] + $sum['withdraw_deduct']; 

        $sum['total_in_people'] = $sum['company_people'] + $sum['online_in_people'] + $sum['artificial_in_people'] + $sum['withdraw_deduct_people'] ;

        $sum['total_out']              =  $sum['artificial_out'] + $sum['member_out'] + $sum['give_discount'] + $sum['rebate'];
        
        $sum['total_out_people']       =     $sum['member_out_people'] + $sum['give_discount_people'] + $sum['artificial_out_people'] + $sum['rebate_people'];

        //人工扣除误存
        $artificialWithdrawWhere = $this->withdrawCondition(true);
        $artificialOutAmount = Loader::model('UserWithdrawRecord')->where($artificialWithdrawWhere)->sum('uwr_apply_amount'); 
        if (!empty($artificialOutAmount) ) {
            $sum['artificial_out_system'] = $artificialOutAmount;
        }

        $sum['platform_actual_profit']  = $sum['company_in'] + $sum['online_in'] + $sum['artificial_in'] - $sum['member_out'] - $sum['artificial_out_system'];

        //用户可用余额
        $account = Loader::model('UserExtend')->field('sum(ue_account_balance) user_account_balance')->where('ue_account_balance>0')->find();
        $user_account_balance = empty($account['user_account_balance']) ? 0 : $account['user_account_balance'];

        //用户未结算金额
        $account = Loader::model('LotteryOrder')->where(array('order_status' => 1))->field('sum(order_bet_amount) no_bet_amount')->find();
        $no_bet_amount = empty($account['no_bet_amount']) ? 0 : $account['no_bet_amount'];
        //统计精确至小数点后三位
        $sum['total']  = round($sum['total'] ,3);
        $sum['platform_actual_profit']  = round($sum['platform_actual_profit'] ,3);
        return array(
            'sum'                  => $sum,
            'user_account_balance' => round($user_account_balance,3),
            'no_bet_amount'        => round($no_bet_amount,3),
        );
    }

    private function first_recharge_where(){
        $where = array();

        $where['urr_confirm_time']  = array('between', array($this->startDate, $this->endDate));
        $where['urr_is_first']      = Config::get('status.recharge_is_first') ['yes'];
        $where['urr_status']        = Config::get('status.recharge_status') ['success'];

        return $where;
    }

    private function first_withdraw_where(){
        $where = array();

        $where['uwr_confirmtime'] = array('between',array($this->startDate, $this->endDate));
        $where['uwr_is_first']    = Config::get('status.withdraw_is_first') ['yes'];
        $where['uwr_type']        = Config::get('status.user_withdraw_type') ['online'];

        return $where;
    }

    /**
     * 首次充值统计
     * @author jesse
     */
    private function firstRecharge(){
        $first_recharge_where = $this->first_recharge_where();
        $first_urr_fields = "IFNULL(SUM(urr_amount), 0) amount, count(distinct user_id) people";
        $data = Loader::model('UserRechargeRecord')->where($first_recharge_where)->field($first_urr_fields)->find();

        return [
            $data['amount']?$data['amount']:0 ,
            $data['people']?$data['people']:0
        ];
    }

    /**
     * 首次提现统计
     * @author jesse
     */
    private function firstWithdraw(){
        $first_withdraw_where = $this->first_withdraw_where();
        $first_uwr_fields = "IFNULL(SUM(uwr_apply_amount), 0) amount, count(distinct user_id) people";
        $data = Loader::model('UserWithdrawRecord')->where($first_withdraw_where)->field($first_uwr_fields)->find();

        return [
            $data['amount']?$data['amount']:0,
            $data['people']?$data['people']:0
        ];
    }


    /**
     * 会员出款报表
     * @param $params
     * @return bool
     */
    public function generateWithdrawReport($params) {
        $condition['uwr_confirmtime'] = ['between',[$params['startDate'],$params['endDate']]];
        $condition['uwr_status']      = Config::get('status.withdraw_status')['confirm'];
        $condition['uwr_type']        = Config::get('status.user_withdraw_type')['online'];
        $fields = "ul_name, user_name, user_realname, uwr_apply_amount as amount,ifnull(uwr_account_balance,0)+ifnull(uwr_apply_amount,0) as before_balance,uwr_account_balance as after_balance, uwr_confirmtime as complete_time,uwr_remark as remark";
        $list   = Loader::model('UserWithdrawRecord')->alias('uwr')->join('__USER__ u', 'u.user_id = uwr.user_id', 'left')->join('__USER_LEVEL__ ul', 'u.ul_id = ul.ul_id', 'left')->where($condition)->field($fields)->order("uwr_confirmtime asc")->select();

        $list =  $list ? collection($list)->toArray() : [];
        $fileName = 'report_withdraw_'.$params['startDate'].'-'.$params['endDate'];
        $title = ['用户层级','账号','真实姓名','操作金额','操作前余额','操作后余额','时间','备注'];

        return $this->_exportExcel($list, $title, $fileName);

    }


    /**
     * 人工存入报表
     * @param $params
     * @return bool
     */
    public function generateRechargeReport($params) {
        $condition['urr_confirm_time'] = ['between',[$params['startDate'],$params['endDate']]];
        $condition['urr_type']         = Config::get('status.user_recharge_type')['system'];
        $condition['urr_status']       = Config::get('status.recharge_status')['success'];

        $fields = 'ul_name, user_name, user_realname, urr_amount as amount,urr_recharge_discount,urr_total_amount,urr_confirm_time as complete_time, urr_remark as remark';
        $list   = Loader::model('UserRechargeRecord')->alias('uwr')
            ->join('__USER__ user', 'uwr.user_id=user.user_id', 'left')
            ->join('__USER_LEVEL__ ul', 'user.ul_id=ul.ul_id', 'left')
            ->where($condition)->field($fields)
            ->order('urr_confirm_time asc')
            ->select();

        $list = $list ? collection($list)->toArray() : [];

        $fileName = 'report_recharge_'.$params['startDate'].'-'.$params['endDate'];
        $title = ['用户层级','账号','真实姓名','操作金额','存入优惠','实际到账金额','时间','备注'];

        return $this->_exportExcel($list, $title, $fileName);
    }

    /**
     * 生成首次充值报表
     * @param $params
     */
    public function generateFirstRechargeReport($startDate,$endDate){

        $logic = Loader::model('UserAccountRecord', 'logic');
        $list  = $logic->_getFirstRecharge(['start_date'=>$startDate,'end_date'=>$endDate]);

        $fileName = 'report_first_recharge_'.$startDate.'-'.$endDate;
        $title = ['用户层级','账号','真实姓名','操作金额','存入优惠','实际到账金额','时间','备注','充值类型'];

        return $this->_exportExcel($list, $title, $fileName);
    }

    /**
     * 生成首次提现报表
     * @param $params
     */
    public function generateFirstWithdrawReport($startDate,$endDate){

        $logic = Loader::model('UserAccountRecord', 'logic');
        $list  = $logic->_getFirstWithdraw(['start_date'=>$startDate,'end_date'=>$endDate]);

        $fileName = 'report_first_withdraw_'.$startDate.'-'.$endDate;
        $title = ['用户层级','账号','真实姓名','操作金额','操作前余额','操作后余额','时间','备注'];

        return $this->_exportExcel($list, $title, $fileName);

    }


    /**
     * 支付平台入款汇总
     */
    public function getPayPlatformRechargeReport($params) {

        $userRechargeRecordModel = Loader::model('UserRechargeRecord');

        $condition ['urr_type'] = Config::get('status.user_recharge_type') ['online'];
        $condition ['urr_status'] = Config::get('status.recharge_status')['success'];

        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition ['urr_confirm_time'] = [
                [
                    'EGT',
                    $params ['start_date'],
                ],
                [
                    'ELT',
                    $params ['end_date'],
                ],
            ];
        }

        $fields = 'sum(urr_amount) as total_amount, count(urr_id) as total_count, urr_recharge_account_id';
        $list = $userRechargeRecordModel->where($condition)->field($fields)->order('urr_id desc')->group('urr_recharge_account_id')->select();

        //批量获取支付平台名称
        $rechargeAccountIds = extract_array($list, 'urr_recharge_account_id');
        $rechargeAccountList = Loader::model('PayPlatform')->where(['pp_id'=>['IN', $rechargeAccountIds]])->column('pay_type_id,pp_category_id', 'pp_id');

        //批量获取支付类型名称
        $payTypeIds = extract_array($rechargeAccountList, 'pay_type_id');
        $payTypeList = Loader::model('PayType')->where(['pay_type_id'=>['IN', $payTypeIds]])->column('pay_type_name', 'pay_type_id');


        if(!empty ($list)) {
            foreach($list as &$val) {
                $categoryId = $rechargeAccountList[ $val['urr_recharge_account_id'] ]['pp_category_id'];
                $categoryName= Config::get('status.pay_category_type_name') [$categoryId];
                $rechargePlatform = $payTypeList[ $rechargeAccountList[$val['urr_recharge_account_id']]['pay_type_id']];
                $val['payPlatform'] = $rechargePlatform.'_'.$categoryName;
            }
            //相同的第三方黏在一起
            $list = array_sort_bykey($list, 'payPlatform');

        }

        return $list;
    }

    /**
     * 导出详情execl
     * @param $params
     * @return bool
     */
    public function exportDetailExcel($params) {

        $result = Loader::model('UserAccountRecord', 'logic')->detailList($params);
        $list = $result['list'] ? collection($result['list'])->toArray() : [];

        foreach($list as $key=>$info){
            unset($list[$key]['uar_transaction_type']);
        }

        $fileName = 'report_detail_'.$params['start_date'].'-'.$params['start_date'];
        $title = ['用户层级','账号','真实姓名','操作金额','操作前余额','操作后余额','时间','备注'];

        return $this->_exportExcel($list, $title, $fileName);

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