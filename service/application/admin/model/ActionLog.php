<?php
/**
 * 行为日志表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class ActionLog extends Model {

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
        'id'            => '主键',
        'action_id'     => '行为id',
        'user_id'       => '执行用户id',
        'action_ip'     => '执行行为者ip',
        'model'         => '触发行为的表',
        'record_id'     => '触发行为的数据id',
        'remark'        => '日志备注',
        'record_detail' => '记录详细数据',
        'status'        => '状态',
    ];

}