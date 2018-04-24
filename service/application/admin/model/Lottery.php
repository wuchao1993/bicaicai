<?php
/**
 * 数字彩分类表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Lottery extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'lottery_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'lottery_id'              => '主键',
        'lottery_category_id'     => '彩票分类',
        'lottery_group_id'        => '彩票组',
        'category_display_id'     => '展示分类ID',
        'lottery_name'            => '名称',
        'lottery_sort'            => '排序',
        'lottery_introduction'    => '玩法简介',
        'lottery_description'     => '玩法规则',
        'lottery_image_url'       => '图片路径',
        'lottery_is_hot'          => '是否热门',
        'lottery_createtime'      => '创建时间',
        'lottery_auto_prize'      => '开奖类型',
        'lottery_ahead_endtime'   => '投注提前截止时间（秒）',
        'lottery_day_frequency'   => '开奖频率',
        'lottery_message_explain' => '信息',
        'lottery_day_count_no'    => '每日开奖期数',
        'lottery_status'          => '状态',

    ];

}