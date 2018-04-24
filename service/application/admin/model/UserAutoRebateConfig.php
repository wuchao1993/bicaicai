<?php
/**
 * 用户返点设置表
 */

namespace app\admin\model;

use think\Model;

class UserAutoRebateConfig extends Model {

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'user_id'             => '主键',
        'lottery_category_id' => '游戏分类ID',
        'user_rebate'         => '用户返点数',
        'user_pid'            => '上级用户ID',
    ];

}