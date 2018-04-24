<?php
/**
 * 用户行为表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Action extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'id';

    protected $table = 'ds_action_new';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'id'          => '主键',
        'name'        => '行为名称',
        'title'       => '行为说明',
        'remark'      => '行为描述',
        'rule'        => '行为规则',
        'log'         => '日志规则',
        'type'        => '类型',
        'status'      => '状态',
        'update_time' => '修改时间',
    ];

}