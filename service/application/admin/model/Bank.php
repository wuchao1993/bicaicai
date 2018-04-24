<?php

namespace app\admin\model;

use think\Model;

class Bank extends Model {

    protected $pk = "bank_id";

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'bank_id'           => '主键',
        'bank_name'         => '名称',
        'bank_image_mobile' => '手机图片',
        'bank_image_pc'     => 'PC图片',
        'bank_code'         => '银行code',
        'bank_createtime'   => '创建时间',
        'bank_status'       => '状态',
    ];

}