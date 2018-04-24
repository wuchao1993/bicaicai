<?php

namespace app\admin\model;

use think\Model;

class PayPlatformUserLevelRelation extends Model
{

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pay_platform_id'      => '主键',
        'user_level_id'        => '用户层级',
    ];

    public function getPlatformIds($userLevelId)
    {
        $condition = ['user_level_id' => $userLevelId];
        $list = $this->where($condition)->column('pay_platform_id');

        return array_values($list);
    }

}