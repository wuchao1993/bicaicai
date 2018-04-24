<?php

/**
 * 用户出款相关业务逻辑
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Model;

class UserWithdrawRecord extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取人工出款列表
     * @param  $params
     * @return array
     */
    public function getSystemList($params) {
        $userWithdrawRecordModel = Loader::model('UserWithdrawRecord');

        $condition ['uwr_type'] = Config::get('status.user_withdraw_type') ['system'];
        if(isset ($params ['user_name'])) {
            $userId = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            if(!empty($userId)) {
                $condition['user_id'] = $userId;
            }
        }
        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition ['uwr_createtime'] = [
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
        if(isset ($params ['uwr_operation_type'])) {
            $condition ['uwr_operation_type'] = $params ['uwr_operation_type'];
        }

        // 获取总条数
        $count = $userWithdrawRecordModel->where($condition)->count();

        $list = $userWithdrawRecordModel->where($condition)->order('uwr_id desc')->limit($params ['num'])->page($params ['page'])->select();

        //批量获取用户名称
        $userIds = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where(['user_id'=>['IN', $userIds]])->column('user_name,ul_id,user_pid', 'user_id');

        //批量获取操作人名称
        $operatorIds = array_unique(extract_array($list, 'uwr_operator_id'));
        $operatorList = Loader::model('Member')->where(['uid'=>['IN', $operatorIds]])->column('nickname', 'uid');

        if(!empty ($list)) {
            foreach($list as $val) {
                $val['user_name'] = $userList[$val['user_id']]['user_name'];
                $val['operator_name'] = $operatorList[$val['uwr_operator_id']];
            }
        }

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }


    /**
     * 新增人工出款
     * @param  $params
     * @return bool
     */
    public function addSystem($params) {
        // 获取用户信息
        $info = Loader::model('User')->where(['user_name' => $params ['user_name']])->find();
        if(!$info) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        $user_id         = $info ['user_id'];
        $withdraw_amount = $params ['uwr_apply_amount'];
        $traffic_amount  = intval($params ['uwr_traffic_amount']);
        

        // 判断用户余额
        $userExtendLogic     = Loader::model('UserExtend', 'logic');
        $user_before_balance = $userExtendLogic->getBalance($user_id);

        $this->startTrans();

        if($withdraw_amount>0){
            $userExtendLogic->NewAddWithdrawAmount($user_id, $withdraw_amount);
            $user_after_balance = $userExtendLogic->getBalance($user_id);
        }else{
            //纯扣除投注量
            $user_after_balance = $user_before_balance;
        }

        if($user_after_balance < 0) {
            $this->errorcode = EC_AD_WITHDRAW_SYSTEM_NOT_ENOUGH;
            $this->rollback();
            return false;
        }

        // 入库
        $data ['user_id']             = $user_id;
        $data ['uwr_no']              = generate_order_number();
        $data ['uwr_type']            = Config::get('status.user_withdraw_type') ['system'];
        $data ['uwr_apply_amount']    = $withdraw_amount;
        $data ['uwr_real_amount']     = $withdraw_amount;
        $data ['uwr_traffic_amount']  = $traffic_amount;
        $data ['uwr_operation_type']  = $params ['uwr_operation_type'];
        $data ['uwr_account_balance'] = $user_after_balance;
        $data ['uwr_operator_id']     = MEMBER_ID;
        $data ['uwr_remark']          = $params ['uwr_remark'];
        $data ['uwr_touser_remark']   = Config::get('status.user_withdraw_system_name') [$params ['uwr_operation_type']];
        $data ['uwr_createtime']      = date('Y-m-d H:i:s');
        $data ['uwr_confirmtime']     = date('Y-m-d H:i:s');
        $data ['uwr_status']          = Config::get('status.withdraw_status')['confirm'];

        $userWithdrawRecordModel = Loader::model('UserWithdrawRecord');
        $ret                     = $userWithdrawRecordModel->save($data);

        //纯扣除投注量,不记录金流
        if($ret&&$withdraw_amount>0) {

            // 添加accountRecord
            $account_record                          = [];
            $account_record ['user_id']              = $user_id ;
            $account_record ['uar_source_id']        = $userWithdrawRecordModel->uwr_id;
            $account_record ['uar_source_type']      = Config::get('status.user_account_record_source_type') ['withdraw'];
            $account_record ['uar_transaction_type'] = Config::get('status.account_record_transaction_type') ['artificial_out'];
            $account_record ['uar_action_type']      = Config::get('status.account_record_action_type') ['fetch'];
            $account_record ['uar_amount']           = $withdraw_amount;
            $account_record ['uar_before_balance']   = $user_before_balance;
            $account_record ['uar_after_balance']    = $user_after_balance;

            $account_record ['uar_remark'] = $params ['uwr_remark'] ? $params ['uwr_remark'] : '人工提现';
            Loader::model('UserAccountRecord')->save($account_record);
        }

        if($traffic_amount > 0){
            Loader::model("UserRechargeRecord",'logic')->modifyRequiredBetAmount($user_id, current_datetime(), $traffic_amount);
        }

        if($ret&&empty($this->getError())){
            $this->commit();

            // 记录行为
            Loader::model('General', 'logic')->actionLog('deduction_money', 'UserWithdrawRecord', $user_id, MEMBER_ID, json_encode($data));

            $systemInfo = [
                'id' => $userWithdrawRecordModel->uwr_id,
            ];

            return $systemInfo;

        }else{
            $this->rollback();

            $this->errorcode = EC_AD_ADD_RECHARGE_SYSTEM_ERROR;
            return false;
        }

    }


    /**
     * 获取在线出款列表
     * @param  $params
     * @return array
     */
    public function getOnlineList($params) {

        $returnArr = [
            'eachPageCount'     => 0,
            'totalCount'        => 0,
            'subApplyAmount'    => 0,
            'subDiscountAmount' => 0,
            'subHandingAmount'  => 0,
            'subRealAmount'     => 0,
            'allApplyAmount'    => 0,
            'allDiscountAmount' => 0,
            'allHandingAmount'  => 0,
            'allRealAmount'     => 0,
            'list'              => [],
        ];

        $userWithdrawRecordModel = Loader::model('UserWithdrawRecord');

        $condition ['uwr_type'] = Config::get('status.user_withdraw_type') ['online'];

        if(isset($params ['date_type']) && $params ['date_type'] == 1) {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['uwr_createtime'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
                $forceIndex = "uwr_createtime";
            }
        } elseif(isset($params ['date_type']) && $params ['date_type'] == 2) {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['uwr_modifytime'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
                $forceIndex = "uwr_modifytime";
            }
        } else {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['uwr_confirmtime'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
                $forceIndex = "uwr_confirmtime";
            }
        }
        if(isset ($params ['min_amount']) && isset ($params ['max_amount'])) {
            $condition ['uwr_apply_amount'] = [
                [
                    'EGT',
                    $params ['min_amount'],
                ],
                [
                    'ELT',
                    $params ['max_amount'],
                ],
            ];
        }else if(isset ($params ['min_amount'])) {
            $condition ['uwr_apply_amount'] = [
                'EGT',
                $params ['min_amount'],
            ];
        } else if(isset ($params ['max_amount'])) {
            $condition ['uwr_apply_amount'] = [
                'ELT',
                $params ['max_amount'],
            ];
        }
        if(isset ($params ['min_out_amount']) && isset ($params ['max_out_amount'])) {
            $condition ['uwr_real_amount'] = [
                [
                    'EGT',
                    $params ['min_out_amount'],
                ],
                [
                    'ELT',
                    $params ['max_out_amount'],
                ],
            ];
        }else if(isset ($params ['min_out_amount'])) {
            $condition ['uwr_real_amount'] = [
                'EGT',
                $params ['min_out_amount'],
            ];
        } else if(isset ($params ['max_out_amount'])) {
            $condition ['uwr_real_amount'] = [
                'ELT',
                $params ['max_out_amount'],
            ];
        }

        if(isset ($params ['user_name'])) {
            $userId = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            $condition['user_id'] = $userId;
        }

        if(isset ($params ['uwr_status'])) {
            $condition ['uwr_status'] = array('IN', $params ['uwr_status']);
        }
        if(isset($params ['isFirst'])){
            if($params ['isFirst'] === Config::get('status.withdraw_is_first') ['yes']) {
                $condition ['uwr_is_first'] = array('IN',array(Config::get('status.withdraw_is_first') ['before'],Config::get('status.withdraw_is_first') ['yes']));
            }elseif ($params ['isFirst'] === Config::get('status.withdraw_is_first') ['no']){
                $condition ['uwr_is_first'] = $params ['isFirst'];
            }
        }
        if(isset ($params ['operator_name'])) {
            $operatorId = Loader::model('Member')->getUserIdByUsername($params ['operator_name']);
            if(!empty($operatorId)) {
                $condition['uwr_operator_id'] = $operatorId;
            }else {
                return $returnArr;
            }
        }

        // 获取总条数
        if($params['user_level']){
            $ul_ids = $params['user_level'];

            if(empty($params ['num'])&&empty($params ['page'])){
                //下载30天内的excel
                $list = $userWithdrawRecordModel->force($forceIndex)->where($condition)->where('user_id','IN',function($query) use($ul_ids){
                    $query->table('ds_user')->where(['ul_id'=>['IN', $ul_ids]])->field('user_id');
                })->order('uwr_id desc')->select();
            }else{
                $list = $userWithdrawRecordModel->force($forceIndex)->where($condition)->where('user_id','IN',function($query) use($ul_ids){
                    $query->table('ds_user')->where(['ul_id'=>['IN', $ul_ids]])->field('user_id');
                })->order('uwr_id desc')->limit($params ['num'])->page($params ['page'])->select();
            }
        }else{

            if(empty($params ['num'])&&empty($params ['page'])){
                //下载30天内的excel
                $list = $userWithdrawRecordModel->force($forceIndex)->where($condition)->order('uwr_id desc')->select();
            }else{
                $list = $userWithdrawRecordModel->force($forceIndex)->where($condition)->order('uwr_id desc')->limit($params ['num'])->page($params ['page'])->select();
            }
        }

        //批量获取用户名称
        $userIds = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where(['user_id'=>['IN', $userIds]])->column('user_name,ul_id,user_pid,user_realname', 'user_id');

        //批量获取用户上级名称
        $userPIds = extract_array($userList, 'user_pid');
        $userPList = Loader::model('User')->where(['user_id'=>['IN', $userPIds]])->column('user_name', 'user_id');

        //批量获取用户层级名称
        $userULIds = extract_array($userList, 'ul_id');
        $userULList = Loader::model('UserLevel')->where(['ul_id'=>['IN', $userULIds]])->column('ul_name', 'ul_id');

        //批量获取操作人名称
        $operatorIds = array_unique(extract_array($list, 'uwr_operator_id'));
        $operatorList = Loader::model('Member')->where(['uid'=>['IN', $operatorIds]])->column('nickname', 'uid');

        // 小计
        $subApplyAmount    = 0;
        $subDiscountAmount = 0;
        $subHandingAmount  = 0;
        $subRealAmount     = 0;

        if(!empty ($list)) {
            foreach($list as &$val) {
                $subApplyAmount    += $val ['uwr_apply_amount'];
                $subDiscountAmount += $val ['uwr_discount_amount'];
                $subHandingAmount  += $val ['uwr_handling_charge'];
                $subRealAmount     += $val ['uwr_real_amount'];

                $val['user_name'] = isset($userList[$val['user_id']])?$userList[$val['user_id']]['user_name']:'';
                $val['user_realname'] = isset($userList[$val['user_id']])?$userList[$val['user_id']]['user_realname']:'';

                if(isset($userList[ $val['user_id'] ]) && isset($userPList[ $userList[ $val['user_id'] ]['user_pid'] ])){
                    $val['parent_user_name'] = $userPList[ $userList[ $val['user_id'] ]['user_pid'] ];
                }else{
                    $val['parent_user_name'] = '';
                }

                if(isset($userList[ $val['user_id'] ]) && isset($userULList[$userList[ $val['user_id'] ]['ul_id']]) ){
                    $val['ul_name'] = $userULList[$userList[ $val['user_id'] ]['ul_id']];
                }else{
                    $val['ul_name'] = '';
                }

                $val['operator_name'] = isset($operatorList[$val['uwr_operator_id']])?$operatorList[$val['uwr_operator_id']]:'';
            }
        }

        // 总额
        $fields = 'count(*) as order_count, sum(uwr_apply_amount) as uwr_apply_amount, sum(uwr_discount_amount) as uwr_discount_amount, sum(uwr_handling_charge) as uwr_handling_charge, sum(uwr_real_amount) as uwr_real_amount';
        if($params['user_level']){
            $totalAmountList   = $userWithdrawRecordModel->field($fields)->where($condition)->where('user_id','IN',function($query) use($ul_ids){
                $query->table('ds_user')->where(['ul_id'=>['IN', $ul_ids]])->field('user_id');})->find();
        }else{
            $totalAmountList   = $userWithdrawRecordModel->field($fields)->where($condition)->find();
        }
        $count             = $totalAmountList['order_count'];
        $allApplyAmount    = $totalAmountList['uwr_apply_amount'];
        $allDiscountAmount = $totalAmountList['uwr_discount_amount'];
        $allHandingAmount  = $totalAmountList['uwr_handling_charge'];
        $allRealAmount     = $totalAmountList['uwr_real_amount'];

        $returnArr = [
            'eachPageCount'     => count($list),
            'totalCount'        => $count,
            'subApplyAmount'    => $subApplyAmount,
            'subDiscountAmount' => $subDiscountAmount,
            'subHandingAmount'  => $subHandingAmount,
            'subRealAmount'     => $subRealAmount,
            'allApplyAmount'    => $allApplyAmount,
            'allDiscountAmount' => $allDiscountAmount,
            'allHandingAmount'  => $allHandingAmount,
            'allRealAmount'     => $allRealAmount,
            'list'              => $list,
        ];

        return $returnArr;
    }

    /**
     * 新增线上出款备注
     * @param  $params
     * @return bool
     */
    public function addOnlineRemark($params) {
        // 入库
        $data                = [];
        $data ['uwr_remark'] = $params ['uwr_remark'] . '【' . MEMBER_NAME .'】';

        Loader::model('UserWithdrawRecord')->save($data, [
            'uwr_id' => $params ['uwr_id'],
        ]);

        return true;
    }


    /**
     * 锁定线上出款
     * @param  $params
     * @return bool
     */
    public function lockOnline($params) {

        $info = Loader::model('UserWithdrawRecord')->where(['uwr_id'=>$params ['uwr_id']])->find();
        if(empty($info) || $info['uwr_status'] != Config::get('status.withdraw_status') ['submit']){
            $this->errorcode = EC_AD_LOCK_ONLINE_ERROR;
            return false;
        }

        $updateData ['uwr_status'] = Config::get('status.withdraw_status') ['lock'];
        $updateData ['uwr_operator_id'] = MEMBER_ID;
        $ret                       = Loader::model('UserWithdrawRecord')->save($updateData, [
            'uwr_id'        => $params ['uwr_id'],
            'uwr_status'    => Config::get('status.withdraw_status') ['submit']
        ]);

        if(!$ret){
            $this->errorcode = EC_AD_LOCK_ONLINE_ERROR;
            return false;
        }

        return $ret;
    }


    /**
     * 解锁线上出款
     * @param  $params
     * @return bool
     */
    public function unlockOnline($params) {
        $model = Loader::model('UserWithdrawRecord');

        $operator_id = $model->where('uwr_id',$params ['uwr_id'])->value("uwr_operator_id");

        if($operator_id == MEMBER_ID){
            $updateData ['uwr_status'] = Config::get('status.withdraw_status') ['submit'];
            $updateData ['uwr_operator_id'] = MEMBER_ID;
            $ret                       = $model->save($updateData, [
                'uwr_id'        => $params ['uwr_id'],
                'uwr_status'    => Config::get('status.withdraw_status') ['lock']
            ]);
            return $ret;
        }

        $this->errorcode = BC_AD_WITHDRAW_UNLOCKONLINE_NO_OPERATOR;

        return false;
    }


    /**
     * 确认线上出款
     * @param  $params
     * @return bool
     */
    public function confirmOnline($params) {
        $withdrawInfo = $this->checkLock($params['uwr_id']);
        if(empty($withdrawInfo)) {
            $this->errorcode = EC_AD_CONFIRM_ONLINE_OTHERER_LOCK_ERROR;
            return false;
        }

        $updateData ['uwr_status']      = Config::get('status.withdraw_status') ['confirm'];
        $updateData ['uwr_confirmtime'] = current_datetime();
        $userWithdrawRecordModel = Loader::model('UserWithdrawRecord');
        $ret                            = $userWithdrawRecordModel->save($updateData, [
            'uwr_id' => $params ['uwr_id'],
        ]);

        if($ret) {
            //添加充值表确认是否已出款
            $result = Loader::model('UserRechargeRecord', 'logic')->withdrawCheck($withdrawInfo['user_id'],$withdrawInfo['uwr_createtime']);

            $userAccountRecordLogic = Loader::model('UserAccountRecord', 'logic');
            $userAccountRecordLogic->setStatusEnd($params ['uwr_id'], Config::get('status.user_account_record_source_type') ['withdraw'], Config::get('status.account_record_transaction_type') ['withdraw']);
            if(bccomp($withdrawInfo['uwr_handling_charge'], '0', 2) !== 0) {
                $userAccountRecordLogic->setStatusEnd($params ['uwr_id'], Config::get('status.user_account_record_source_type') ['withdraw'], Config::get('status.account_record_transaction_type') ['withdraw_deduct']);
            }
            //首次提现
            $isFirst = $userWithdrawRecordModel->isFirst($withdrawInfo['user_id']);
            if(empty($isFirst)){
                $userWithdrawRecordModel->setFirst($params ['uwr_id']);
            }
            //确认首出取消预首出
            $isBeforeFirst = $userWithdrawRecordModel->isBeforeFirst($withdrawInfo['user_id']);
            if(!empty($isBeforeFirst)){
                $userWithdrawRecordModel->delBeforeFirst($isBeforeFirst['uwr_id']);
            }
        }

        return $ret;
    }

    /**
     * 代付线上出款
     * @param  $params
     * @return bool
     */
    public function goToPayOnline($params) {
        $withdrawInfo = $this->checkPay($params['uwr_id']);
        if(empty($withdrawInfo)) {
            $this->errorcode = BC_AD_GO_TO_PAY_ERROR;
            return '请勿重复出款！';
        }

        $user_id = $withdrawInfo['user_id'];
        $userBankRelationLogic = Loader::model('UserBankRelation','logic');
        $bank_info  = $userBankRelationLogic -> getList($user_id);
        if(empty($bank_info)){
            $this->errorcode = BC_AD_GO_TO_PAY_ERROR;
            return '用户银行卡信息错误！';
        }
        $record_detail = array(
            'user_id'           => $user_id,
            'bank_account'      => $bank_info['ub_bank_account'],
            'bank_user_name'    => $bank_info['ub_bank_user_name'],
            'bank_id'           => $bank_info['bank_id'],
            'total_amount'      => $withdrawInfo['uwr_apply_amount'],
            'admin_id'          => MEMBER_ID,
            'uwr_id'            => $params['uwr_id'],
            'create_time'       => date("Y-m-d H:i:s")
        );
        Loader::model('General', 'logic')->actionLog('start_pay', 'UserWithdrawRecord', $params['uwr_id'], MEMBER_ID, json_encode($record_detail));


        if($withdrawInfo) {
            $param = [
                'act'      => DIGITAL_GO_TO_PAY,
                'order_id' => $params['uwr_id']
            ];
            $result = call_to_digital($param);
            if($result['status'] == '0' && $result['gotopay_status'] == 'SUCCESS'){

                $updateData ['uwr_status']      = Config::get('status.withdraw_status') ['confirm'];
                $updateData ['uwr_confirmtime'] = current_datetime();
                $updateData ['uwr_pp_id'] = $result['pp_id'];
                $updateData ['uwr_is_payment'] = Config::get('status.withdraw_is_payment') ['confirm'];

                $userWithdrawRecordModel = Loader::model('UserWithdrawRecord');
                $ret                            = $userWithdrawRecordModel->save($updateData, [
                    'uwr_id' => $params ['uwr_id'],
                ]);

                $record_detail = array(
                    'user_id'           => $user_id,
                    'pp_id'             => $result['pp_id'],
                    'bank_account'      => $bank_info['ub_bank_account'],
                    'bank_id'           => $bank_info['bank_id'],
                    'total_amount'      => $withdrawInfo['uwr_apply_amount'],
                    'admin_id'          => MEMBER_ID,
                    'uwr_id'            => $params['uwr_id'],
                    'create_time'       => date("Y-m-d H:i:s")
                );
                Loader::model('General', 'logic')->actionLog('return_pay', 'UserWithdrawRecord', $withdrawInfo['uwr_id'], MEMBER_ID, json_encode($record_detail));

                if($ret) {
                    $userAccountRecordLogic = Loader::model('UserAccountRecord', 'logic');
                    $userAccountRecordLogic->setStatusEnd($params ['uwr_id'], Config::get('status.user_account_record_source_type') ['withdraw'], Config::get('status.account_record_transaction_type') ['withdraw']);
                    if(bccomp($withdrawInfo['uwr_handling_charge'], '0', 2) !== 0) {
                        $userAccountRecordLogic->setStatusEnd($params ['uwr_id'], Config::get('status.user_account_record_source_type') ['withdraw'], Config::get('status.account_record_transaction_type') ['withdraw_deduct']);
                    }
                    Loader::model('PayPlatform')->addStatistics($result['pp_id'],$withdrawInfo['uwr_apply_amount']);
                    //首次提现
                    $isFirst = $userWithdrawRecordModel->isFirst($withdrawInfo['user_id']);
                    if (empty($isFirst)) {
                        $userWithdrawRecordModel->setFirst($params ['uwr_id']);
                    }
                }
                return 'SUCCESS';
            }else{
                $this->errorcode = BC_AD_GO_TO_PAY_ERROR;
                return $result['message'];
            }
        }else{
            $this->errorcode = BC_AD_GO_TO_PAY_ERROR;
            return '订单信息错误！';
        }

    }

    /**
     * 取消代付出款:返回用户金额
     */

    public function cancelPayment($params)
    {
        $uwInfo = Loader::model('UserWithdrawRecord')->where(array('uwr_id'=>$params['id'], 'uwr_status' => Config::get('status.withdraw_status') ['confirm'],'uwr_is_payment'=>Config::get('status.withdraw_is_payment') ['confirm']))->find();
        if(empty($uwInfo) && $uwInfo['uwr_pp_id'] =='0') {
            return false;
        }

        $user_id            = $uwInfo['user_id'];
        $apply_amount       = $uwInfo['uwr_apply_amount'];
        $handling_charge    = $uwInfo['uwr_handling_charge'];
        $total_amount = bcadd($apply_amount, $handling_charge, 2);
        $ueModel = Loader::model('UserExtend');
        $ueInfo = $ueModel->where(array('user_id' => $user_id))->find();
        $balance = $ueInfo['ue_account_balance'];

        $this->startTrans();
        $ueArray = [];
        $ueArray['user_id'] = $user_id;
        $ueArray['ue_account_balance'] = array('exp', 'ue_account_balance+' . $total_amount);
        $ueArray['ue_withdraw_amount'] = array('exp', 'ue_withdraw_amount-' . $total_amount);
        $ueArray['ue_withdraw_count'] = array('exp', 'ue_withdraw_count-1');
        $ueModel->save($ueArray, ['user_id'=>$user_id]);

        $model = Loader::model('UserAccountRecord');
        $uaArray = [];
        $uaArray['user_id'] = $user_id;
        $uaArray['uar_source_id'] = $params['id'];
        $uaArray['uar_source_type'] = Config::get('status.user_account_record_source_type') ['withdraw'];
        $uaArray['uar_transaction_type'] = Config::get('status.account_record_transaction_type') ['withdraw_cancel'];
        $uaArray['uar_action_type'] = Config::get('status.account_record_action_type') ['deposit'];
        $uaArray['uar_amount'] = $total_amount;
        $uaArray['uar_before_balance'] = $balance;
        $uaArray['uar_after_balance'] = bcadd($balance, $total_amount, 2);
        $uaArray['uar_status'] = Config::get('status.account_record_status') ['yes'];
        $uaArray['uar_remark'] = '代付出款取消';
        $model->insert($uaArray);

        //提现申请记录状态
        $userAccountRecordLogic = Loader::model('UserAccountRecord', 'logic');
        $set_status_result = $userAccountRecordLogic->setStatusEnd($params ['id'], Config::get('status.user_account_record_source_type') ['withdraw'], Config::get('status.account_record_transaction_type') ['withdraw']);
        if($set_status_result === false){
            $this->rollback();
            return false;
        }

        Loader::model('PayPlatform')->lessStatistics($uwInfo['uwr_pp_id'],$apply_amount);
        $finduw = Loader::model("UserWithdrawRecord")->findWithdrawStatus($user_id,$uwInfo['uwr_confirmtime']);
        $result = Loader::model("UserWithdrawRecord")->savePaymentStatus($params['id'],Config::get('status.withdraw_status') ['confirm'],Config::get('status.withdraw_status') ['cancel'],$params['remark'],MEMBER_ID,Config::get('status.withdraw_is_first') ['no'],Config::get('status.withdraw_is_payment') ['cancel']);
        if(!empty($result))
        {
            if(!empty($finduw)){
                Loader::model("UserWithdrawRecord")->setFirst($finduw['uwr_id']);
            }
            $this->commit();

            //行为日志
            $record_detail = array(
                'user_id'           => $user_id,
                'apply_amount'      => $apply_amount,
                'handling_charge'   => $handling_charge,
                'total_amount'      => $total_amount,
                'remark'            => $params['remark'],
                'admin_id'          => MEMBER_ID,
                'record_id'         => $params['id'],
                'create_time'       => date("Y-m-d H:i:s")
            );
            Loader::model('General', 'logic')->actionLog('cancel_payment', 'UserWithdrawRecord', $params['id'], MEMBER_ID, json_encode($record_detail));
            return true;
        }
        else
        {
            $this->rollback();
            return false;
        }

    }
    /**
     * 取消线上出款
     * @param  $params
     * @return bool
     */
    public function refuseAndCancelOnline($params) {

        if($params['type'] == 'cancel') {
            $result = $this->cancel($params);
        }elseif($params['type'] == 'refuse' ) {
            $result = $this->refuse($params);
        }

        return $result;
    }

    /**
     * 取消:返回用户金额
     */
    public function cancel($params)
    {
        $uwInfo = Loader::model('UserWithdrawRecord')->where(array('uwr_id'=>$params['id'], 'uwr_status' => Config::get('status.withdraw_status') ['lock']))->find();

        if(empty($uwInfo)) {
            return false;
        }

        $user_id            = $uwInfo['user_id'];
        $apply_amount       = $uwInfo['uwr_apply_amount'];
        $handling_charge    = $uwInfo['uwr_handling_charge'];
        $total_amount = bcadd($apply_amount, $handling_charge, 2);
        $ueModel = Loader::model('UserExtend');
        $ueInfo = $ueModel->where(array('user_id' => $user_id))->find();
        $balance = $ueInfo['ue_account_balance'];

        $this->startTrans();
        $ueArray = [];
        $ueArray['user_id'] = $user_id;
        $ueArray['ue_account_balance'] = array('exp', 'ue_account_balance+' . $total_amount);
        $ueArray['ue_withdraw_amount'] = array('exp', 'ue_withdraw_amount-' . $total_amount);
        $ueArray['ue_withdraw_count'] = array('exp', 'ue_withdraw_count-1');
        $ueModel->save($ueArray, ['user_id'=>$user_id]);

        $model = Loader::model('UserAccountRecord');
        $uaArray = [];
        $uaArray['user_id'] = $user_id;
        $uaArray['uar_source_id'] = $params['id'];
        $uaArray['uar_source_type'] = Config::get('status.user_account_record_source_type') ['withdraw'];
        $uaArray['uar_transaction_type'] = Config::get('status.account_record_transaction_type') ['withdraw_cancel'];
        $uaArray['uar_action_type'] = Config::get('status.account_record_action_type') ['deposit'];
        $uaArray['uar_amount'] = $total_amount;
        $uaArray['uar_before_balance'] = $balance;
        $uaArray['uar_after_balance'] = bcadd($balance, $total_amount, 2);
        $uaArray['uar_status'] = Config::get('status.account_record_status') ['yes'];
        $uaArray['uar_remark'] = '提现取消';
        $model->insert($uaArray);

        //提现申请记录状态
        $userAccountRecordLogic = Loader::model('UserAccountRecord', 'logic');
        $set_status_result = $userAccountRecordLogic->setStatusEnd($params ['id'], Config::get('status.user_account_record_source_type') ['withdraw'], Config::get('status.account_record_transaction_type') ['withdraw']);
        if($set_status_result === false){
            $this->rollback();
            return false;
        }
        //修改预首出
        $this->saveBeforeFirst($user_id,$uwInfo['uwr_id'],$uwInfo['uwr_createtime']);

        $result = Loader::model("UserWithdrawRecord")->saveStatus($params['id'],Config::get('status.withdraw_status') ['lock'],Config::get('status.withdraw_status') ['cancel'],$params['remark'],MEMBER_ID);
        if(!empty($result))
        {
            $this->commit();

            //行为日志
            $record_detail = array(
                'user_id'           => $user_id,
                'apply_amount'      => $apply_amount,
                'handling_charge'   => $handling_charge,
                'total_amount'      => $total_amount,
                'remark'            => $params['remark'],
                'admin_id'          => MEMBER_ID,
                'record_id'         => $params['id'],
                'create_time'       => date("Y-m-d H:i:s")
            );
            Loader::model('General', 'logic')->actionLog('cancel_withdraw', 'UserWithdrawRecord', $params['id'], MEMBER_ID, json_encode($record_detail));
            return true;
        }
        else
        {
            $this->rollback();
            return false;
        }

    }

    /**
     * 拒绝：不返回用户金额
     */
    public function refuse($params)
    {
        $uwInfo = $this->checkLock($params['id']);

        if(empty($uwInfo)) {
            return false;
        }

        $user_id            = $uwInfo['user_id'];
        $apply_amount       = $uwInfo['uwr_apply_amount'];
        $handling_charge    = $uwInfo['uwr_handling_charge'];
        $total_amount = bcadd($apply_amount, $handling_charge, 2);

        $ueModel   = Loader::model('UserExtend');
        $uarModel  = Loader::model('UserAccountRecord');

        $ueInfo = $ueModel->where(array('user_id' => $user_id))->find();
        $balance = $ueInfo['ue_account_balance'];

        $this->startTrans();

        //扣除次数//拒绝提款
        $ue_data['ue_withdraw_count'] = array('exp', 'ue_withdraw_count-1');
        $ue_result = $ueModel->where(array('user_id'=>$user_id))->update($ue_data);
        if($ue_result === false){
            $this->rollback();
            return false;
        }

        //提现申请记录状态
        $userAccountRecordLogic = Loader::model('UserAccountRecord', 'logic');
        $set_status_result = $userAccountRecordLogic->setStatusEnd($params ['id'], Config::get('status.user_account_record_source_type') ['withdraw'], Config::get('status.account_record_transaction_type') ['withdraw']);
        if($set_status_result === false){
            $this->rollback();
            return false;
        }

        $uar['user_id']         = $user_id;
        $uar['uar_source_id']   = $params['id'];
        $uar['uar_source_type'] = Config::get('status.user_account_record_source_type') ['withdraw'];;
        $uar['uar_amount']      = $total_amount;
        $uar['uar_status']      = Config::get('status.account_record_status') ['yes'];

        $uar_cancel = $uar_refuse = $uar;

        //取消
        $uar_cancel['uar_transaction_type'] = Config::get('status.account_record_transaction_type') ['withdraw_cancel'];
        $uar_cancel['uar_action_type']      = Config::get('status.account_record_action_type') ['deposit'];
        $uar_cancel['uar_before_balance']   = $balance;
        $uar_cancel['uar_after_balance']    = bcadd($balance, $total_amount, 2);
        $uar_cancel['uar_remark']           = '提现取消';

        //拒绝
        $uar_refuse['uar_transaction_type'] = Config::get('status.account_record_transaction_type') ['artificial_out']; //拒绝类型=>人工扣除
        $uar_refuse['uar_action_type']      = Config::get('status.account_record_action_type') ['fetch'];
        $uar_refuse['uar_before_balance']   = bcadd($balance, $total_amount, 2);
        $uar_refuse['uar_after_balance']    = $balance;
        $uar_refuse['uar_remark']           = '提现拒绝';

        $result =  array($uar_cancel,$uar_refuse);
        $refuse_result =  $uarModel->saveAll($result);

        if($refuse_result === false){
            $this->rollback();
            return false;
        }
        //修改预首出
        $this->saveBeforeFirst($user_id,$uwInfo['uwr_id'],$uwInfo['uwr_createtime']);

        //行为日志
        $changeData = array(
            'uwr_confirmtime'   => date("Y-m-d H:i:s"),
            'uwr_status'        => Config::get("status.withdraw_status")['refuse'],
            'uwr_touser_remark' => $params['remark'],
            'uwr_operator_id'   => MEMBER_ID
        );
        $actionData = Loader::model('General','logic')->getActionData($params['id'],$changeData,'UserWithdrawRecord');

        //拒绝理由
        $result = Loader::model("UserWithdrawRecord")->saveStatus($params['id'],Config::get('status.withdraw_status') ['lock'],Config::get('status.withdraw_status') ['refuse'],$params['remark'],MEMBER_ID);
        if($result === false){
            $this->rollback();
            return false;
        }else{
            $this->commit();
            Loader::model('General', 'logic')->actionLog('refuse_withdraw', 'UserWithdrawRecord', $params['id'], MEMBER_ID, json_encode($actionData));
            return true;
        }

    }

    private function checkLock($id)
    {
        $info = Loader::model('UserWithdrawRecord')->where(array('uwr_id'=>$id))->find();

        if(!empty($info)){
            if($info['uwr_status'] != Config::get('status.withdraw_status') ['lock']){
                return [];
            }elseif($info['uwr_operator_id'] != MEMBER_ID){
                return [];
            }
            return $info;
        }
        else{
            return [];
        }


    }

    private function saveBeforeFirst($user_id,$uwr_id,$uwr_createtime){
        $before_first = Loader::model('UserWithdrawRecord')->getBeforeFirstInfo($uwr_id);
        if(!empty($before_first)){
            Loader::model('UserWithdrawRecord')->delBeforeFirst($uwr_id);
            $text_first = Loader::model('UserWithdrawRecord')->findBeforeFirst($user_id,$uwr_createtime);
            if(!empty($text_first)){
                Loader::model('UserWithdrawRecord')->setBeforeFirst($text_first['uwr_id']);
            }
        }

    }

    private function checkPay($id)
    {
        $info = Loader::model('UserWithdrawRecord')->where(array('uwr_id'=>$id))->find();
        if(!empty($info)){
            if($info['uwr_status'] != Config::get('status.withdraw_status') ['lock']){
                return [];
            }elseif($info['uwr_operator_id'] != MEMBER_ID){
                return [];
            }
            return $info;
        }
        else{
            return [];
        }


    }

    /**
     * 即时检查
     * @param  $params
     * @return bool
     */
    public function currentCheck($params) {
        // 获取用户信息
        $userInfo = Loader::model('User')->where(['user_name' => $params ['user_name']])->find();
        if(!$userInfo) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        $userId    = $userInfo ['user_id'];

        //打码量单笔放宽界限值
        $userInfo = Loader::model('User')->where(['user_id' => $userId ])->find();
        $user_level_id = $userInfo['ul_id'];
        $pay_config = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($user_level_id);

        if(empty($pay_config)) {
            $this->errorcode = EC_AD_PAY_CONFIG_EMPTY;

            return false;
        }

        $pc_relax_amount = $pay_config['pc_relax_amount'];
        $checkList = Loader::model('UserRechargeRecord', 'logic')->getNeedCheck($userId);

        $end_date                  = date('Y-m-d H:i:s');
        $list                      = [];
        $user_balance              = Loader::model('UserExtend', 'logic')->getBalance($userId);
        $not_allow_withdraw_amount = 0;
        foreach($checkList as $info) {
            $traffic_amount           = $info ['urr_traffic_amount'];
            $real_bet_amount          = $info ['urr_required_bet_amount'];
            $recharge_amount          = $info ['urr_amount'];
            $recharge_disocunt        = $info ['urr_recharge_discount'];
            $temp                     = [];
            $temp ['no']              = $info ['urr_no'];
            $temp ['recharge_amount'] = $recharge_amount;
            $temp ['discount_amount'] = $recharge_disocunt;
            $temp ['need_bet_amount'] = $traffic_amount;
            $temp ['real_bet_amount'] = $real_bet_amount;
            $temp ['start_date']      = $info ['urr_createtime'];
            $temp ['end_date']        = $end_date;
            if($real_bet_amount < $traffic_amount - $pc_relax_amount) {
                $check_status              = Config::get('status.withdraw_check_status_name') [0];
                $not_allow_withdraw_amount += $recharge_amount;
                $not_allow_withdraw_amount += $recharge_disocunt;
            } else {
                $check_status = Config::get('status.withdraw_check_status_name') [1];
            }
            $temp ['check_status'] = $check_status;
            $end_date              = $info ['urr_createtime'];
            $list []               = $temp;
        }

        $returnArr ['list']                = $list;
        $returnArr ['relaxAmount']     = $pc_relax_amount;
        $returnArr ['userBalance']         = $user_balance;
        $returnArr ['allowWithdrawAmount'] = Loader::model('UserRechargeRecord', 'logic')->getUserMaxWithdrawAmount($userId, $pc_relax_amount);

        return $returnArr;
    }


    /**
     * 即时检查弹出框
     * @param  $params
     * @return bool
     */
    public function currentCheckBox($params) {

        // 获取用户信息
        $userInfo = Loader::model('User')->where(['user_id' => $params ['user_id']])->find();
        if(!$userInfo) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        $user_id   = $params ['user_id'];
        //打码量单笔放宽界限值
        $user_level_id = $userInfo['ul_id'];
        $pay_config = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($user_level_id);
        $pc_relax_amount = isset($pay_config['pc_relax_amount'])?$pay_config['pc_relax_amount']:0;

        $checkList = Loader::model('UserRechargeRecord', 'logic')->getNeedCheck($user_id);

        $end_date                  = date('Y-m-d H:i:s');
        $list                      = [];
        $user_balance              = Loader::model('UserExtend', 'logic')->getBalance($user_id);
        $not_allow_withdraw_amount = 0;
        foreach($checkList as $info) {
            $traffic_amount           = $info ['urr_traffic_amount'];
            $real_bet_amount          = $info ['urr_required_bet_amount'];
            $recharge_amount          = $info ['urr_amount'];
            $recharge_disocunt        = $info ['urr_recharge_discount'];
            $temp                     = [];
            $temp ['no']              = $info ['urr_no'];
            $temp ['recharge_amount'] = $recharge_amount;
            $temp ['discount_amount'] = $recharge_disocunt;
            $temp ['need_bet_amount'] = $traffic_amount;
            $temp ['real_bet_amount'] = $real_bet_amount;
            $temp ['start_date']      = $info ['urr_createtime'];
            $temp ['end_date']        = $end_date;
            if($real_bet_amount < $traffic_amount - $pc_relax_amount) {
                $check_status              = Config::get('status.withdraw_check_status_name') [0];
                $not_allow_withdraw_amount += $recharge_amount;
                $not_allow_withdraw_amount += $recharge_disocunt;
            } else {
                $check_status = Config::get('status.withdraw_check_status_name') [1];
            }
            $temp ['check_status'] = $check_status;
            $end_date              = $info ['urr_createtime'];
            $list []               = $temp;
        }

        $returnArr ['list']                = $list;
        $returnArr ['userBalance']         = $user_balance;
        $returnArr ['relaxAmount']         = $pc_relax_amount;
        $returnArr ['allowWithdrawAmount'] = Loader::model('UserRechargeRecord', 'logic')->getUserMaxWithdrawAmount($user_id,$pc_relax_amount);

        return $returnArr;
    }

    /**
     * 即时检查（小额）
     * @param  $params
     * @return bool
     */
    public function currentCheckLow($params) {
        // 获取用户信息
        $userInfo = Loader::model('User')->where(['user_name' => $params ['user_name']])->find();
        if(!$userInfo) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        $userId    = $userInfo ['user_id'];

        //打码量单笔放宽界限值
        $userInfo = Loader::model('User')->where(['user_id' => $userId ])->find();
        $user_level_id = $userInfo['ul_id'];
        $pay_config = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($user_level_id);

        if(empty($pay_config)) {
            $this->errorcode = EC_AD_PAY_CONFIG_EMPTY;

            return false;
        }

        $pc_relax_amount = $pay_config['pc_relax_amount'];
        $checkList = Loader::model('UserRechargeRecord', 'logic')->getNeedCheckLow($userId);

        $end_date                  = date('Y-m-d H:i:s');
        $list                      = [];

        foreach($checkList as $info) {
            $traffic_amount           = $info ['slobr_traffic_amount'];
            $real_bet_amount          = $info ['slobr_require_bet_amount'];
            $temp                     = [];
            $temp ['no']              = $info ['slobr_order_no'];
            $temp ['type']            = $info ['slobr_type'];
            $temp ['need_bet_amount'] = $traffic_amount;
            $temp ['real_bet_amount'] = $real_bet_amount;
            $temp ['start_date']      = $info ['slobr_create_time'];
            $temp ['end_date']        = $end_date;
            if($real_bet_amount < $traffic_amount - $pc_relax_amount) {
                $check_status              = Config::get('status.withdraw_check_status_name') [0];
            } else {
                $check_status = Config::get('status.withdraw_check_status_name') [1];
            }
            $temp ['check_status'] = $check_status;
            $list []               = $temp;
        }

        $returnArr ['list']                = $list;

        return $returnArr;
    }

    public function getUserWithdrawCountMap($userIds) {
        $condition               = [];
        $condition['user_id']    = [
            'in',
            $userIds,
        ];
        $condition['uwr_status'] = Config::get('status.withdraw_status')['confirm'];

        $res = Loader::model('UserWithdrawRecord')->where($condition)->group('user_id')->column('user_id, count(*) as count', 'user_id');
        return  $res;
    }

    public function getStatistics($userIds, $startTime, $endTime){
        if(empty($userIds)) return [];

        $condition = array();
        $condition['user_id']        = array('in', $userIds);
        $condition['uwr_status']     = Config::get('status.withdraw_status')['confirm'];
        $condition['uwr_createtime'] = array('between', array($startTime, $endTime));
        $fields = array('user_id', 'count(*)'=>'withdraw_count', 'SUM(uwr_apply_amount)'=>'withdraw_total');
        return  Loader::model('UserWithdrawRecord')->where($condition)->field($fields)->group('user_id')->select();
    }
}