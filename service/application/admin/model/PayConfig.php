<?php

namespace app\admin\model;


use think\Model;

class PayConfig extends Model {

    protected $pk = 'pc_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pc_id'                               => '主键',
        'ul_id'                               => '用户层级ID',
        'pc_everyday_withdraw_count'          => '每日提款次数',
        'pc_repeat_withdraw_time'             => '重复出款时数',
        'pc_everyday_withdraw_free_count'     => '免手续费次数',
        'pc_everyday_withdraw_max_amount'     => '每日出款上限',
        'pc_everytime_withdraw_max_amount'    => '每次出款上限',
        'pc_everytime_withdraw_min_amount'    => '每次出款下限',
        'pc_withdraw_fee'                     => '手续费扣点（百分比）',
        'pc_online_discount_start_amount'     => '线上存款优惠标准（优惠起始额度）',
        'pc_company_discount_start_amount'    => '公司存款优惠标准',
        'pc_artificial_discount_start_amount' => '人工存款优惠标准',
        'pc_online_discount_percentage'       => '线上入款优惠百分比',
        'pc_company_discount_percentage'      => '公司入款优惠百分比',
        'pc_artificial_discount_percentage'   => '人工存款优惠百分比',
        'pc_online_recharge_max_amount'       => '线上入款最大额度',
        'pc_company_recharge_max_amount'      => '公司入款最大额度',
        'pc_artificial_recharge_max_amount'   => '人工存款最大额度',
        'pc_online_recharge_min_amount'       => '线上入款最小额度',
        'pc_company_recharge_min_amount'      => '公司入款最小额度',
        'pc_artificial_recharge_min_amount'   => '人工入款最小额度',
        'pc_online_discount_max_amount'       => '线上入款优惠最大额度',
        'pc_company_discount_max_amount'      => '公司入款优惠最大额度',
        'pc_artificial_discount_max_amount'   => '人工入款优惠最大额度',
        'pc_recharge_traffic_mutiple'         => '入款打码倍数',
        'pc_discount_traffic_mutiple'         => '返水打码倍数',
        'pc_relax_amount'                     => '放宽额度',
        'pc_check_service_charge'             => '未达打码量提现行政费',
        'pc_status'                           => '状态',
        'pc_company_everyday_large_amount'    => '公司入款每日大额提醒额度',
        'pc_online_everyday_large_amount'     => '线上存款每日大额提醒额度',
    ];

}