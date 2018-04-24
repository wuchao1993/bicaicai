<?php
/**
 * 配置表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class SiteConfig extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'sc_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'sc_id'          => '主键',
        'sc_name'        => '配置名称',
        'sc_lottery_type'=> '彩票类型',
        'sc_type'        => '配置类型',
        'sc_title'       => '配置说明',
        'sc_group'       => '配置分组',
        'sc_extra'       => '配置项',
        'sc_remark'      => '备注',
        'sc_create_time' => '创建时间',
        'sc_update_time' => '更新时间',
        'sc_status'      => '状态',
        'sc_value'       => '配置值',
        'sc_sort'        => '排序',
    ];

}