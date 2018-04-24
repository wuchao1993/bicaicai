<?php
namespace app\admin\validate;

use think\Validate;

class AgentDomain extends Validate
{

    protected $rule = [
        'id'            => 'require|number',
        'uid'           => 'require|number',
        'domainName'    => 'require',
        'userType'      => 'require',
        'status'        => 'require',

    ];


    protected $message = [
        'id.require'            => 'ID不能为空',
        'id.number'             => 'ID必须是数字',
        'uid.require'           => '用户ID不能为空',
        'uid.number'            => '用户ID必须是数字',
        'domainName.require'    => '域名不能为空',
        'userType.require'      => '用户类型不能为空',
        'status.require'        => '是否启用不能为空',
    ];


    public $scene = [
        'addAgentDomain'            => ['uid', 'domainName', 'userType', 'status'],
        'changeAgentDomainStatus'   => ['id', 'status'],
    ];

}