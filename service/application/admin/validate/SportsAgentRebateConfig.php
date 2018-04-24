<?php
/**
 * Created by PhpStorm.
 * User: joyce
 * Date: 2017/11/17
 * Time: 14:29
 */

namespace app\admin\validate;

use think\Validate;

class SportsAgentRebateConfig extends  Validate
{
    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'profitMin' => 'require|number|gt:0',
        'profitMax' => 'require|number|gt:0',
        'validUser' => 'require|number|gt:0',
        'rebate' => 'require|number|gt:0',
        'status' => 'require|number|between:1,2',
        'id' => 'require|number|gt:0',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'profitMin.require' => '最小盈利数不能为空',
        'profitMin.number' => '最小盈利数必须是数字',
        'profitMin.gt' => '最小盈利数必须大于0',
        'profitMax.require' =>'最大盈利数不能为空',
        'profitMax.number' => '最大盈利数必须是数字',
        'profitMax.gt' => '最大盈利数必须大于0',
        'validUser.require' => '有效用户数不能为空',
        'validUser.number' => '有效用户数必须为数字',
        'validUser.gt' => '有效用户数必须大于0',
        'rebate.require' => '返点比例不能为空',
        'rebate.number' => '返点比例必须为数字',
        'rebate.gt' => '返点比例大于0',
        'status.require' => '使用状态不能为空',
        'status.number' => '使用状态必须为数字',
        'status.between' => '使用状态在1-2之间',
        'id.require' => 'id不能为空',
        'id.number' => 'id必须为数字',
        'id.gt' => 'id必须大于0',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'addRebateConfig'                  => ['profitMin', 'profitMax', 'validUser','rebate'],
        'editRebateConfig'                 => ['id','status','profitMin', 'profitMax', 'validUser','rebate'],
        'deleteRebateConfig'               => ['id'],

    ];

}