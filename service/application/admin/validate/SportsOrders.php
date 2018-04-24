<?php
/**
 * 订单验证器
 * 
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class SportsOrders extends Validate {

	/**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'orderNo'  => 'require',
        'gameId'   => 'number',
        'page'     => 'number',
        'num'      => 'number',
        'status'   => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'orderNo'   => '订单id不能为空',
        'gameId'    => 'gameId不能为空',
        'page'      => '页码格式不合法',
        'num'       => '分页数量不合法',
        'status'    => '状态不能为空',
    ];    

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'info'                 => ['orderNo'],
        'getList'              => ['page', 'num'],
        'cancel'               => ['orderNo'],
        'clearing'             => ['orderNo'],
        'cancelClearing'       => ['orderNo'],
        'getUncheckedList'     => ['page', 'num'],
        'check'                => ['orderNo', 'status'],
        'parlayCancel'         => ['orderNo', 'gameId'],
        'parlayClearing'       => ['orderNo', 'gameId'],
        'parlayCancelClearing' => ['orderNo', 'gameId'],
        'editAbnormalOrder'    => ['orderNo'],
    ];

}