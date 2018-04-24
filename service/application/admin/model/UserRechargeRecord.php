<?php
/**
 * 用户入款表
 * @author paulli
 */

namespace app\admin\model;

use think\Config;
use think\Model;

class UserRechargeRecord extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'urr_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'urr_id'                  => '主键',
        'urr_no'                  => '订单号',
        'user_id'                 => '用户ID',
        'urr_recharge_account_id' => '入款账号ID',
        'urr_type'                => '充值类型',
        'urr_transfer_type'       => '网银转账方式',
        'urr_operation_type'      => '人工操作类型',
        'urr_amount'              => '充值金额',
        'urr_recharge_discount'   => '充值优惠',
        'urr_total_amount'        => '实际到账总额',
        'urr_traffic_amount'      => '打码量',
        'urr_required_bet_amount' => '已达投注量',
        'urr_operator_id'         => '操作人',
        'urr_remark'              => '备注',
        'urr_recharge_bank_id'    => '充值银行ID',
        'urr_recharge_user_name'  => '充值帐号',
        'urr_trade_no'            => '第三方充值平台订单号',
        'urr_trade_time'          => '支付平台订单交易时间',
        'urr_recharge_time'       => '公司入款用户充值时间',
        'urr_confirm_time'        => '确认时间',
        'urr_createtime'          => '创建时间',
        'urr_client_ip'           => '客户',
        'urr_is_withdraw'         => '是否提现',
        'urr_status'              => '状态',
        'urr_is_first'            => '是否首次充值',
    ];

    public function isFirst($userId){

        $where = array();
        $where['urr_is_first']  = Config::get('status.recharge_is_first') ['yes'];
        $where['user_id']       = $userId;

        return $this->where($where)->find();
    }

    public function getFirstInfos($userIds){

        $where = array();
        $where['urr_is_first']  = Config::get('status.recharge_is_first')['yes'];
        $where['user_id']       = array('in',$userIds);

        $fields = "user_id,urr_is_first";

        return $this->where($where)->field($fields)->select();
    }

}