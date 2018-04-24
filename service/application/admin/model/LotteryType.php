<?php
/**
 * 数字彩玩法类型表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class LotteryType extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'lottery_type_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'lottery_type_id'         => '主键',
        'lottery_category_id'     => '分类ID',
        'lottery_type_name'       => '类型名称',
        'lottery_short_name'      => '短名称',
        'lottery_type_sort'       => '排序',
        'lottery_type_createtime' => '创建时间',
        'lottery_type_default'    => '默认值',
        'lottery_type_status'     => '状态',
    ];

}