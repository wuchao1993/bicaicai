<?php
/**
 * 数字彩分类表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class LotteryCategory extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'lottery_category_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'lottery_category_id'         => '主键',
        'lottery_category_max_rebate' => '会员最高返水',
        'lottery_default_rebate'      => '默认返点',
        'lottery_category_sort'       => '排序',
        'lottery_category_model'      => '模式',
        'lottery_category_image'      => '图片路径',
        'lottery_category_name'       => '分类名称',
        'lottery_category_type'       => '分类类型',
    ];

}