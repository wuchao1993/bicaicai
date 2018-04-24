<?php
/**
 * 菜单表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Menu extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'id';

    protected $table = 'ds_menu_new';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'id'         => '主键',
        'title'      => '标题',
        'route_name' => '路由名称',
        'pid'        => '上级分类ID',
        'sort'       => '排序（同级有效）',
        'url'        => '链接地址',
        'hide'       => '是否隐藏',
        'tip'        => '提示',
        'group'      => '分组',
        'is_dev'     => '是否仅开发者模式可见',
        'status'     => '状态',
    ];

}