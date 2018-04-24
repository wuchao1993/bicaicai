<?php

namespace app\admin\Model;

use think\Model;

class RechargeType extends Model {

    protected $pk = 'recharge_type_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'recharge_type_id'           => '主键',
        'rtg_id'                     => '分组ID',
        'recharge_type_name'         => '名称',
        'recharge_type_short_name'   => '短名称',
        'recharge_type_image'        => '图片路径',
        'recharge_type_introduction' => '介绍',
        'recharge_type_scheme'       => '跳转协议',
        'recharge_type_action_type'  => '行为类型',
        'recharge_type_sort'         => '排序',
        'recharge_type_status'       => '状态',
    ];

}