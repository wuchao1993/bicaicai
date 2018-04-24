<?php
/**
 * 资讯表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Information extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'information_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'information_id'           => '主键',
        'information_type'         => '类型',
        'information_title'        => '标题',
        'information_content'      => '内容',
        'information_createtime'   => '创建时间',
        'information_publish_time' => '发布时间',
        'information_sort'         => '排序',
        'information_status'       => '状态',
        'information_source'       => '来源',
    ];

}