<?php
/**
 * 数字彩分类表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class CategoryDisplay extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'category_display_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'category_display_id'           => '主键',
        'category_display_name'         => '名称',
        'category_display_image'        => '图片',
        'category_display_introduction' => '简介',
        'category_display_sort'         => '排序',
        'category_display_hot'          => '是否热门',
        'category_display_status'       => '状态',
    ];

}