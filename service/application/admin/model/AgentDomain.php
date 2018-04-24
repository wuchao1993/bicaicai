<?php
/**
 * 代理域名表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class AgentDomain extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'agd_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'agd_id'         => '主键',
        'user_id'        => '用户ID',
        'agd_domain'     => '代理域名',
        'agd_use_count'  => '使用次数',
        'agd_status'     => '状态',
        'agd_createtime' => '创建时间',
        'agd_remark'     => '备注',
    ];

}