<?php

namespace app\api\validate;

use think\helper\Str;
use think\Validate;

class UserFavoriteMatches extends Validate{

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'sportId'  => 'require|number|gt:0',
        'matcheId' => 'require|number|gt:0',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'sportId' => '请传入球类id',
        'matcheId' => '请传入联赛id',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'add' => ['sportId', 'matcheId'],
        'cancel' => ['sportId', 'matcheId']
    ];


}