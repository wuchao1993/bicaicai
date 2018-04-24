<?php

namespace app\admin\model;


use think\Model;

class PayAccount extends Model {

    protected $pk = 'pa_id';

    protected $readonly = [
        'bank_id',
        'pa_bank_addr',
        'pa_collection_user_name',
        'pa_collection_account'
    ];

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pa_id'                   => '主键',
        'bank_id'                 => '彩票类型',
        'pay_type_id'             => '公告类型',
        'pa_bank_addr'            => '开户行',
        'pa_collection_user_name' => '收款人',
        'pa_collection_account'   => '收款账号',
        'pa_code_url'             => '二维码图片',
        'pa_limit_amount'         => '停用金额',
        'pa_recharge_amount'      => '已充值额度',
        'pa_sort'                 => '排序',
        'pa_remark'               => '备注',
        'pa_status'               => '状态',
        'pa_createtime'           => '创建时间',
        'pa_modifytime'           => '修改时间',
    ];

    /**
     * 定义映射字段
     * @var array
     */
    public function getInfosByPaId($paId, $paUsername = '')
    {
        $condition = [
            'pa_id' => [
                'EQ',
                $paId
            ]
        ];

        if($paUsername) {
            $condition['pa_collection_user_name'] = [
                'like',
                '%'.$paUsername.'%'
            ];
        }

        $fields    = 'bank_id, pa_collection_user_name';
        
        $res = $this->where($condition)->field('bank_id, pa_collection_user_name')->find();
        
        return $res;
    }

    public function addRechargeAmount($paId, $amount){
        $condition = [];
        $condition['pa_id'] = $paId;
        return $this->where($condition)->setInc('pa_recharge_amount', $amount);
    }


}