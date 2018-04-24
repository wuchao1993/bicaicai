<?php

namespace app\admin\model;

use think\Model;

class PayAccountUserLevelRelation extends Model {

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pay_account_id'                  => '主键',
        'user_level_id'                   => '用户层级',
    ];

}