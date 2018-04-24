<?php
/**
 * 数字彩玩法类型表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class LotteryTypeConfig extends Model {


    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'lottery_id'      => '主键',
        'lottery_type_id' => '类型ID',
        'ltc_sort'        => '排序',
    ];

}