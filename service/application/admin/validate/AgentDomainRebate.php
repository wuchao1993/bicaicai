<?php
namespace app\admin\validate;

use think\Validate;

class AgentDomainRebate extends Validate
{

    protected $rule = [
        'id'            => 'require|number',
        'uid'           => 'require|number',
        'rebate_conf'   => 'require',

    ];


    protected $message = [
        'id.require'            => 'ID不能为空',
        'id.number'             => 'ID必须是数字',
        'uid.require'           => '用户ID不能为空',
        'uid.number'            => '用户ID必须是数字',
        'rebate_conf.require'   => '返点参数不能为空',
    ];


    public $scene = [
        'edit'    => ['id', 'uid', 'rebate_conf'],
    ];

}