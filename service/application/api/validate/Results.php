<?php
namespace app\api\validate;

use think\Validate;

class Results extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'gameId'  => 'require|number',
        'type' => 'require',
        'sport'  => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'gameId'  => 'gameId不能为空',
        'type' => '类型不能为空',
        'sport' => '球类不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getInfo' => ['gameId', 'type', 'sport'],
        'getList' => ['sport', 'type']
    ];
}