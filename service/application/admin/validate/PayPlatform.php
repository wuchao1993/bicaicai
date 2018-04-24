<?php
namespace app\admin\validate;

use think\Validate;

class PayPlatform extends Validate
{

    protected $rule = [
        'id'            => 'require|number',

    ];


    protected $message = [
        'id.require'            => 'ID不能为空',
        'id.number'             => 'id必须是数字',
    ];


    public $scene = [
        'getPayPlatformInfo'    => ['id'],
    ];

}