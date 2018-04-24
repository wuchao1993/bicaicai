<?php

namespace app\admin\model;

use think\Model;

class AgentLink extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'agl_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'agl_id'         => '主键',
        'user_id'        => '用户ID',
        'agl_code'       => '代理链接',
        'agl_user_type'  => '用户类型',
        'agl_use_count'  => '使用次数',
        'agl_endtime'    => '结束时间',
        'agl_createtime' => '创建时间',
        'agl_status'     => '状态',
    ];

}