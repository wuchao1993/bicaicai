<?php
/**
 * 权限组表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class AuthGroup extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'id';

    protected $table = 'ds_auth_group_new';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'id'          => '主键',
        'module'      => '用户组所属模块',
        'type'        => '组类型',
        'title'       => '用户组中文名称',
        'description' => '描述信息',
        'status'      => '用户组状态',
        'rules'       => '用户组拥有的规则id',
    ];

}