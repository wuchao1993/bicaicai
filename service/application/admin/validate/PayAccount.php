<?php
namespace app\admin\validate;

use think\Validate;

class PayAccount extends Validate
{

    protected $rule = [
        'id'            => 'require|number',
        'bankId'        => 'require',
        'bankAddr'      => 'require',
        'accountName'   => 'require',
        'accountNumber' => 'require',
        'ulId'          => 'require',

    ];


    protected $message = [
        'id.require'            => 'ID不能为空',
        'id.number'             => 'id必须是数字',
        'bankId.require'        => '银行id不能为空',
        'bankAddr.require'      => '开户行地址不能为空',
        'accountName.require'   => '账号姓名不能为空',
        'accountNumber.require' => '卡号/账号不能为空',
        'ulId.require'          => '层级id不能为空',
    ];


    public $scene = [
        'getPayAccountInfo' => ['id'],
        'editPayAccount'    => ['bankId', 'bankAddr', 'accountName', 'accountNumber', 'ulId'],
    ];

}