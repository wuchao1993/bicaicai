<?php
namespace app\admin\validate;

use think\Validate;

class Bank extends Validate
{

    protected $rule = [
        'id'            => 'require|number',

    ];


    protected $message = [
        'id.require'            => 'ID不能为空',
        'id.number'             => 'id必须是数字',
    ];


    public $scene = [
        'getBankInfo'    => ['id'],
    ];

}