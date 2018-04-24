<?php
/**
 * 首页大厅验证器
 * @createTime 2017/4/5 10:27
 */

namespace app\api\validate;

use think\Validate;

class Home extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'activityId' => 'require|number|gt:0',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'activityId.require' => '请传入活动id',
        'activityId.number'  => '活动id不合法',
        'activityId.gt'      => '活动id不合法',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'activityInfo' => ['activityId'],
    ];
}