<?php
/**
 * 活动表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Activity extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'activity_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'activity_id'           => '主键',
        'activity_name'         => '活动名称',
        'activity_introduction' => '活动简介',
        'activity_description'  => '活动描述',
        'activity_image'        => '活动图片',
        'activity_list_image'   => '活动展示图',
        'activity_starttime'    => '活动开始时间',
        'activity_finishtime'   => '活动结束时间',
        'activity_is_banner'    => '是否横幅',
        'activity_createtime'   => '创建时间',
        'activity_sort'         => '排序',
        'activity_status'       => '状态',
        'activity_lottery_type' => '彩票类型',
    ];

}