<?php
/**
 * TODO 后台所有控制器加验证
 * 比赛玩法限额验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class SportsMatches extends Validate {

	/**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'sportType' => 'require',
        'page'      => 'number',
        'num'       => 'number',
        'id'        => 'number|require',
        'sort'      => 'require',
        'hot'       => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'sportType' => '类型不能为空',
        'page'      => '页码格式不合法',
        'num'       => '分页数量不合法',
        'id'        => 'id不能为空',
        'sort'      => '排序id不能为空',
        'hot'       => '热门id不能为空',
    ];    

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'index'      => ['num', 'page'],
        'updateSort' => ['sportType', 'id', 'sort'],
        'updateHot'  => ['sportType', 'id', 'hot'],
    ];

}