<?php
/**
 * 比赛验证器
 * 
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class SportsSchedules extends Validate {
    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'sportType'  => 'require',
        'page'       => 'number',
        'num'        => 'number',
        'scheduleId' => 'require|number',
        'status'     => 'require',
        'remark'     => 'max:50',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'sportType'   => '类型不能为空',
        'page'        => '页码格式不合法',
        'num'         => '分页数量不合法',
        'scheduleId'  => '比赛id不能为空',
        'status'      => '状态不能为空',
    ];    

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'index'             => ['num', 'page', 'require'],
        'cancel'            => ['sportType', 'scheduleId', 'remark'],
        'cancelClearing'    => ['sportType', 'scheduleId'],
        'clearing'          => ['sportType', 'scheduleId'],
        'updateSalesStatus' => ['sportType', 'scheduleId', 'status'],
        'editScore'         => ['sportType'],
        'getResult'         => ['sportType', 'scheduleId'],
    ];

}