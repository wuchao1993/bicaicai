<?php
/**
 * 帮助表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Help extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'help_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'help_id'           => '主键',
        'help_type'         => '类型',
        'help_title'        => '标题',
        'help_content'      => '内容',
        'help_createtime'   => '创建时间',
        'help_publish_time' => '发布时间',
        'help_sort'         => '排序',
        'help_status'       => '状态',
    ];

}