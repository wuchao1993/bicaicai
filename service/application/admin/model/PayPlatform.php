<?php

namespace app\admin\model;

use think\Model;

class PayPlatform extends Model {

    protected $pk = 'pp_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pp_id'              => '主键',
        'pay_type_id'        => '支付平台类别',
        'pp_category_id'     => '支付分类',
        'pp_url'             => '弃用',
        'pp_redict_domain'   => '域名',
        'pp_account_key'     => '帐号KEY',
        'pp_terminal_id'     => '终端ID',
        'pp_limit_amount'    => '停用额度',
        'pp_recharge_amount' => '充值额度',
        'pp_recharge_count'  => '充值次数',
        'pp_rsa_pub_key'     => 'rsa公钥',
        'pp_rsa_pri_key'     => 'rsa私钥',
        'pp_sort'            => '排序',
        'pp_status'          => '状态',
        'pp_createtime'      => '创建时间',
        'pp_modifytime'      => '修改时间',
        'pp_min_pay_money'   => '最少支付',
        'pp_notify_key'      => 'notify钥匙',
    ];

    /**
     * 更新统计信息
     * @param $pp_id
     * @param $rechargeAmount
     * @return $this
     */
    public function addStatistics($ppId, $rechargeAmount) {
        $condition = [
            'pp_id' => $ppId,
        ];

        $data = [
            'pp_recharge_amount' => [
                'exp',
                'pp_recharge_amount+' . $rechargeAmount,
            ],
            'pp_recharge_count'  => [
                'exp',
                'pp_recharge_count+1',
            ],
        ];

        return $this->where($condition)->update($data);
    }
    /**
     * 更新统计信息
     * @param $pp_id
     * @param $rechargeAmount
     * @return $this
     */
    public function lessStatistics($ppId, $rechargeAmount) {
        $condition = [
            'pp_id' => $ppId,
        ];

        $data = [
            'pp_recharge_amount' => [
                'exp',
                'pp_recharge_amount-' . $rechargeAmount,
            ],
            'pp_recharge_count'  => [
                'exp',
                'pp_recharge_count-1',
            ],
        ];

        return $this->where($condition)->update($data);
    }


    public function getInfosByIds($ppIds, $ppAccountnNo = '') {
        $condition = [
            'pp_id' => [
                'in',
                $ppIds
            ]
        ];

        if($ppAccountnNo) {
            $condition['pp_account_no'] = [
                'like',
                '%'.$ppAccountnNo.'%'
            ];
        }

        $fields    = 'pp_id, pay_type_id, pp_category_id, pp_account_no';
        
        $res = $this->where($condition)->column($fields, 'pp_id');
        return $res;
    }

    public function getIdsByAccount($ppAccountnNo = ''){
        $condition = [];
        if($ppAccountnNo) {
            $condition['pp_account_no'] = [
                'like',
                '%'.$ppAccountnNo.'%'
            ];
        }
        return $this->where($condition)->column('pp_id');
    }
}