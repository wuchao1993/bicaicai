<?php
/**
 * 公告表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Notice extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'notice_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'notice_id'           => '主键',
        'notice_lottery_type' => '彩票类型',
        'notice_type'         => '公告类型',
        'notice_title'        => '标题',
        'notice_introduction' => '简介',
        'notice_content'      => '内容',
        'notice_createtime'   => '创建时间',
        'notice_sort'         => '排序',
        'notice_status'       => '状态',
    ];

}