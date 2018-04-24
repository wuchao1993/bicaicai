<?php
namespace app\admin\validate;

use think\Validate;

class PayCenter extends Validate
{

    protected $rule = [
        'id'                => 'require|number',
        'payChannelId'      => 'require|number',
        'payTypeId'         => 'require|number',
        'redirectDomain'    => 'require|url',
        'userLevelId'       => 'require',
        'channelMerchantId' => 'require|number',  
        'status'            => 'require|number',
        'account'           => 'require', 
    ];

    protected $message = [
        'id.require'        => 'ID不能为空',
        'id.number'         => 'id必须是数字',
        'payChannelId'      => 'payChannelId格式不正确',   
        'redirectDomain'    => 'redirectDomain格式不正确',   
        'account'           => '商户号不能为空',   
        'userLevelId'       => 'userLevelId格式不正确',   
        'channelMerchantId' => 'channelMerchantId格式不正确',   
        'status'            => '修改状态码格式不正确',
    ];


    public $scene = [
        'editMerchantInfo'      => ['id'],
        'createChannelMerchant' => ['payChannelId', 'payTypeId', 'redirectDomain', 'userLevelId', 'account'],
        'updateChannelMerchant' => ['payChannelId', 'payTypeId', 'redirectDomain', 'account', 'userLevelId', 'channelMerchantId'],
        'changeChannelMerchantStatus' => ['channelMerchantId', 'status'],
    ];

}