<?php
/**
 * 配置表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Config extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'id'          => '主键',
        'name'        => '配置名称',
        'type'        => '配置类型',
        'title'       => '配置说明',
        'group'       => '配置分组',
        'extra'       => '配置项',
        'remark'      => '备注',
        'create_time' => '创建时间',
        'update_time' => '更新时间',
        'status'      => '状态',
        'value'       => '配置值',
        'sort'        => '排序',
    ];

}