<?php
/**
 * TODO 后台所有控制器加验证
 * 比赛玩法限额验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class SportsPlayTypes extends Validate {

	/**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'sportId'        => 'require',
        'betLimitMin'    => 'require|number|gt:0',
        'matchLimit'     => 'require|number|gt:0',
        'betLimitMax'    => 'elt:matchLimit|gt:0|number|require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'sportId'               => '球类Id不能为空',
        'betLimitMax.require'   => '单注最高额度不能为空',
        'betLimitMax.gt'        => '单注最高额度必须大于0',
        'betLimitMin.require'   => '单注最低额度不能为空',
        'matchLimit.gt'         => '单场最高额度必须大于0',
        'matchLimit.require'    => '单场最高额度不能为空',
        'betLimitMax.elt'       => '单注最高额度不能大于单场最高额度',
    ];    

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'index'       => ['sportId'],
        'updateLimit' => ['betLimitMax', 'betLimitMin', 'matchLimit'],
    ];

}