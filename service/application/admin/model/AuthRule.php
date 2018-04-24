<?php
/**
 * 权限表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class AuthRule extends Model {

    protected $pk = "id";

    protected $table = 'ds_auth_rule_new';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'id'         => '主键',
        'module'     => '规则所属module',
        'type'       => '类型',
        'name'       => '规则唯一英文标识',
        'title'      => '规则中文描述',
        'status'     => '是否有效',
        'condition'  => '规则附加条件',
        'route_name' => '路由名称',
    ];

}