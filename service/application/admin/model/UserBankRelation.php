<?php
/**
 * 用户层级表
 */

namespace app\admin\model;

use think\Model;
use think\Config;

class UserBankRelation extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'ub_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'ub_id'             => '主键',
        'user_id'           => '用户ID',
        'bank_id'           => '银行ID',
        'ub_bank_account'   => '银行帐号',
        'ub_bank_user_name' => '银行用户名',
        'ub_address'        => '地址',
        'ub_is_default'     => '是否默认',
        'ub_createtime'     => '创建时间',
        'ub_status'         => '状态',
    ];

    public function getUidByAccount($account){

        $condition = [];
        $condition['ub_bank_account'] = $account;
        $condition['ub_status']       = Config::get("status.user_bank_status")['enable'];

        $result = $this->where($condition)->field('user_id')->find();

        if(!empty($result)) {
            return $result['user_id'];
        }else {
            return '';
        }
    }
}