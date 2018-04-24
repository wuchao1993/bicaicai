<?php
/**
 * 支付配置文件
 * 
 */
use \think\Env;

$payDomain = Env::get('pay_center.pay_url');

return [
    //创建订单url
    'create_order_url'                 => $payDomain . '/pay/order/create',
    //支付类型
    'pay_type_list_url'                => $payDomain . '/pay/payType/index',
    //银行列表
    'get_bank_list_url'                => $payDomain . '/pay/bank/index',
    //支付渠道列表
    'get_pay_channel_list_url'         => $payDomain . '/pay/payChannel/index',
    //创建支付渠道商户
    'create_pay_channel_merchant'      => $payDomain . '/pay/channelMerchant/create',
    //更新支付渠道商户
    'update_pay_channel_merchant'      => $payDomain . '/pay/channelMerchant/update',
    //启用支付渠道商户
    'enable_pay_channel_merchant'      => $payDomain . '/pay/channelMerchant/enable',
    //关闭支付渠道商户
    'disable_pay_channel_merchant'     => $payDomain . '/pay/channelMerchant/disable',
    //删除支付渠道商户
    'delete_pay_channel_merchant'      => $payDomain . '/pay/channelMerchant/delete',
    //支付渠道商户列表
    'get_pay_channel_merchant_list'    => $payDomain . '/pay/channelMerchant/index',
    //根据paytypecode查询支付类型信息
    'get_pay_type_by_short_name'       => $payDomain . '/pay/payType/info',
    //支付中心订单查询
    'pay_center_order_query_url'       => $payDomain . '/pay/order/query',
    //充值中心渠道网银同步url
    'pay_center_channel_merchant_bank' => $payDomain . '/pay/channelBank/index',
    //=====================v2=====================
    //获取好友付列表
    'get_friend_pay_account_list'      => $payDomain . '/pay_v2/FriendAccount/index',
    //添加好友付账户
    'add_friend_pay_account'           => $payDomain . '/pay_v2/FriendAccount/add',
    //编辑好友付账户
    'edit_friend_pay_account'          => $payDomain . '/pay_v2/FriendAccount/edit',
    //删除好友付账户
    'delete_friend_pay_account'        => $payDomain . '/pay_v2/FriendAccount/delete',
    //好友付账户详情
    'get_friend_pay_account_detail'    => $payDomain . '/pay_v2/FriendAccount/detail',
    //好友付账户状态修改
    'change_friend_pay_account_status' => $payDomain . '/pay_v2/FriendAccount/updateStatus',
    //好友付类型列表
    'get_friend_pay_type_list'         => $payDomain . '/pay_v2/FriendAccount/friendTypeList',
    //公司入款账户列表
    'get_bank_account_list'            => $payDomain . '/pay_v2/BankAccount/index',
    //公司入款账号添加
    'add_bank_account'                 => $payDomain . '/pay_v2/BankAccount/add',
    //公司入款账户编辑
    'edit_bank_account'                => $payDomain . '/pay_v2/BankAccount/edit',
    //公司入款账号删除
    'delete_bank_account'              => $payDomain . '/pay_v2/BankAccount/delete',
    //公司入款账号启用禁用
    'change_bank_account_status'       => $payDomain . '/pay_v2/BankAccount/updateStatus',
    //公司入款账户详情
    'get_bank_account_detail'          => $payDomain . '/pay_v2/BankAccount/detail',
    //支付类型列表
    'get_pay_type_list'                => $payDomain . '/pay_v2/payType/index',
    //支付类型详情
    'get_pay_type_detail'              => $payDomain . '/pay_v2/payType/detail',
    //修改支付类型分组
    'update_pay_type_group'            => $payDomain . '/pay_v2/payType/updateGroup',
    //支付类型分组列表
    'get_pay_type_group_list'          => $payDomain . '/pay_v2/PayTypeGroup/index',
    //新增支付类型分组
    'add_pay_type_group'               => $payDomain . '/pay_v2/PayTypeGroup/add',
    //修改支付类型分组
    'edit_pay_type_group'              => $payDomain . '/pay_v2/PayTypeGroup/edit',
    //删除支付类型分组
    'delete_pay_type_group'            => $payDomain . '/pay_v2/PayTypeGroup/delete',
    //支付类型分组详情
    'get_pay_type_group_detail'        => $payDomain . '/pay_v2/PayTypeGroup/detail',
    //修改支付类型状态(启用，禁用)
    'change_pay_type_group_status'     => $payDomain . '/pay_v2/PayTypeGroup/updateStatus',
    //获取渠道商户列表
    'get_pay_channel_merchant'         => $payDomain . '/pay_v2/merchant/index',
    //创建渠道商户
    'create_channel_merchant'          => $payDomain . '/pay_v2/merchant/add',
    //获取渠道商户详情
    'get_channel_merchant_detail'      => $payDomain . '/pay_v2/merchant/detail',
    //修改渠道商户
    'edit_channel_merchant'            => $payDomain . '/pay_v2//merchant/edit',
    //删除渠道商户
    'delete_channel_merchant'          => $payDomain . '/pay_v2/merchant/delete',
    //修改渠道商户状态（启用禁用）
    'change_channel_merchant_status'   => $payDomain . '/pay_v2/merchant/updateStatus',
    //支付渠道列表(新)
    'get_pay_channel_list'             => $payDomain . '/pay_v2/payChannel/index',
    //渠道支持支付类型列表
    'channel_usable_pay_type_list'     => $payDomain . '/pay_v2/payChannel/usableTypeList',
    //支付类型分组列表（前端充值选择商户列表）
    'api_get_pay_type_group_list'     => $payDomain . '/pay_v2/PayTypeGroup/showList',
     //v2银行列表
    'get_bank_list'                    => $payDomain . '/pay_v2/bank/index',
    //充值中心创建订单接口 v2版本， 稳定后废弃旧接口
    'api_pay_center_create_order_v2'  => $payDomain. '/pay_v2/order/create',

    'pay_center_error' => [
        EC_PAY_RECHARGE_ONLINE_NO_PLATFORM => "充值渠道不可用",
        EC_PAY_RECHARGE_NOTIFY_SIGN_ERROR => '签名错误',
        EC_PAY_RECHARGE_RECORD_NOT_EXIST => '商户不存在',
        EC_PAY_RECHARGE_MERCHANT_CODE_ERROR => '商户号错误',
        EC_PAY_RECHARGE_MERCHANT_NOT_EXIST => '商户号不存在',
        EC_PAY_RECHARGE_PAY_TYPE_NOT_EXIST => '支付类型不存在',
        EC_PAY_RECHARGE_CHANNEL_MERCHANT_NOT_EXIST => "支付渠道商户不存在",
    ],


    'app_config' => [
        'app_id' => Env::get('pay_center.app_id'),
        'sign_key' => Env::get('pay_center.sign_key'),
        'notify_url' => Env::get('pay_center.notify_url'),
        'callback_url' => Env::get('pay_center.callback_url'),
    ],

];