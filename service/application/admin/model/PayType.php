<?php

namespace app\admin\model;

use think\Model;

class PayType extends Model {

    protected $pk = 'pay_type_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pay_type_id'              => '主键',
        'pay_type_name'            => '名称',
        'pay_type_category'        => '弃用',
        'pay_class_name'           => 'class名',
        'pay_type_createtime'      => '创建时间',
        'pay_type_image'           => '图片路径',
        'pay_type_url'             => 'api地址',
        'pay_type_redirect_domain' => '弃用',
        'pay_type_status'          => '状态',
        'pay_config_display'       => '配置显示',
        'pay_gotopay_status'       => '是否支持代付',
    ];

    public function getInfosByPayTypeIds($payTypeIds) {
        $condition = [
            'pay_type_id' => [
                'in',
                $payTypeIds
            ]
        ];

        $fields = 'pay_type_id, pay_type_name, pay_type_status';

        return $this->where($condition)->column($fields, 'pay_type_id');
    }

}