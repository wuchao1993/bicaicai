<?php
/**
 * 用户注册优惠表
 */

namespace app\admin\model;

use think\Model;

class UserRegConfig extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'urc_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'urc_id'                   => '主键',
        'urc_is_discount'          => '会员注册是否赠送彩金',
        'urc_discount_amount'      => '赠送额度',
        'urc_check_amount'         => '添加打码量',
        'urc_remark'               => '备注',
        'urc_type'                 => '注册优惠类型',
        'urc_ip_day_limit'         => '同一个ip最大每日限制',
        'urc_isonly_general_agent' => '是否仅总代理用户赠彩金',
    ];

}