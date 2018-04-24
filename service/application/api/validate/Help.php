<?php
namespace app\api\validate;

use think\Validate;

class Help extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'content'  => 'require',
        'contact' => 'require',
        'page'      => 'number|gt:0',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'content'  => '反馈内容不能为空',
        'contact' => '请留下您的联系方式',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'feedback' => ['content', 'contact'],
    ];
}