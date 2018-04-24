<?php
/**
 * 用户出款表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;
use think\Loader;
use think\Config;

class UserWithdrawRecord extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'uwr_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'uwr_id'              => '主键',
        'uwr_no'              => '提现订单号',
        'user_id'             => '用户ID',
        'uwr_type'            => '提款类型',
        'ub_id'               => '用户提现关联银行ID',
        'uwr_operation_type'  => '人工出款类型',
        'uwr_apply_amount'    => '申请金额',
        'uwr_handling_charge' => '手续费',
        'uwr_discount_amount' => '优惠金额',
        'uwr_manage_cost'     => '行政费',
        'uwr_real_amount'     => '出款金额',
        'uwr_account_balance' => '账户余额',
        'uwr_traffic_amount'  => '人工提出扣除投注量',
        'uwr_operator_id'     => '操作人',
        'uwr_remark'          => '备注',
        'uwr_touser_remark'   => '提醒用户备注',
        'uwr_createtime'      => '创建时间',
        'uwr_modifytime'      => '修改时间',
        'uwr_status'          => '状态',
        'uwr_confirmtime'     => '确认时间',
        'uwr_is_first'        => '是否首次提现',
    ];

    public function saveStatus($id , $oldStatus, $newStatus,$touserRemark,$operatorId){
        $condition = array();
        $condition['uwr_id'] = $id;
        $condition['uwr_status'] = $oldStatus;

        $data = array();
        $data['uwr_status'] = $newStatus;
        $data['uwr_confirmtime'] = date("Y-m-d H:i:s"); //确定时间

        if($touserRemark) $data['uwr_touser_remark']  = $touserRemark;

        if($operatorId) $data['uwr_operator_id'] = $operatorId;

        return Loader::model('UserWithdrawRecord')->where($condition)->update($data);
    }

    public function savePaymentStatus($id , $oldStatus, $newStatus,$touserRemark,$operatorId,$isfirst,$paymentCancel){
        $condition = array();
        $condition['uwr_id'] = $id;
        $condition['uwr_status'] = $oldStatus;

        $data = array();
        $data['uwr_status'] = $newStatus;
        $data['uwr_confirmtime'] = date("Y-m-d H:i:s"); //确定时间

        if($touserRemark) $data['uwr_touser_remark']  = $touserRemark;

        if($operatorId) $data['uwr_operator_id'] = $operatorId;

        $data['uwr_is_first'] = $isfirst;
        $data['uwr_is_payment'] = $paymentCancel;

        return Loader::model('UserWithdrawRecord')->where($condition)->update($data);
    }

    public function findWithdrawStatus($userId,$confirmtime){
        $condition = [];
        $condition ['uwr_confirmtime'] = [
            [
                'GT',
                $confirmtime,
            ],
        ];
        $withdrawStatus = Config::get('status.withdraw_status') ['confirm'];
        $condition ['uwr_status'] = [
            'IN',
            $withdrawStatus,
        ];
        $condition['user_id'] = $userId;

        return $this->where($condition)->order('uwr_confirmtime','asc')->find();
    }

    public function isFirst($userId){

        $where = array();
        $where['uwr_is_first']  = Config::get('status.withdraw_is_first') ['yes'];
        $where['user_id']       = $userId;

        return $this->where($where)->find();
    }

    public function setFirst($id){
        $condition = array();
        $condition['uwr_id'] = $id;

        $data['uwr_is_first'] = Config::get('status.withdraw_is_first') ['yes'];

        return $this->save($data,$condition);
    }

    public function isBeforeFirst($userId){

        $where = array();
        $where['uwr_is_first']  = Config::get('status.withdraw_is_first') ['before'];
        $where['user_id']       = $userId;

        return $this->where($where)->find();
    }

    public function delBeforeFirst($id){
        $condition = array();
        $condition['uwr_id'] = $id;

        $data = array();
        $data['uwr_is_first'] = Config::get('status.withdraw_is_first') ['no'];

        return $this->save($data,$condition);
    }

    public function setBeforeFirst($id){
        $condition = array();
        $condition['uwr_id'] = $id;

        $data = array();
        $data['uwr_is_first'] = Config::get('status.withdraw_is_first') ['before'];

        return $this->save($data,$condition);
    }


    public function getBeforeFirstInfo($id){
        $condition = array();
        $condition['uwr_id'] = $id;
        $condition['uwr_is_first'] = Config::get('status.withdraw_is_first') ['before'];

        return $this->where($condition)->find();
    }
    public function findBeforeFirst($userId,$createtime){
        $condition = [];
        $condition['uwr_createtime'] = array('GT',$createtime);
        $condition['uwr_status'] = array('in',array(Config::get('status.withdraw_status') ['submit'],Config::get('status.withdraw_status') ['lock']));
        $condition['user_id'] = $userId;

        return $this->where($condition)->order('uwr_createtime','asc')->find();
    }
}