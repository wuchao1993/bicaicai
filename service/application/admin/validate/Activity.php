<?php
/**
 * 活动验证器
 *
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class Activity extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
    	'activityCategoryName' => 'require',
    	'activityCategoryId'   => 'require|number',
    	'num'                  => 'number',
    	'page'                 => 'number',
    	'id'                   => 'require|number',
    	'name'                 => 'require',
    	'status'               => 'require|number',
    	'starttime'            => 'require',
    	'finishtime'           => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
    	'activityCategoryName' => '活动类目名字不能为空',
    	'num'                  => '分页数量不合法',
    	'page'                 => '分页数量不合法',
    	'activityCategoryId'   => '活动类目id不合法',
    	'id'                   => '活动id不合法',
    	'name'                 => '活动名字不能为空',
    	'status'               => '活动状态不合法',
    	'starttime'            => '活动开始时间不能为空',
    	'finishtime'           => '活动结束时间不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getActivityCategoryList'  => ['page', 'num'],
    	'addActivityCategory'      => ['activityCategoryName'],
    	'editActivityCategory'     => ['activityCategoryName','activityCategoryId'],
    	'deleteActivityCategory'   => ['activityCategoryId'],
    	'getActivityList'          => ['page', 'num'],
    	'getActivityInfo'          => ['id'],
    	'addActivity'              => ['name', 'status', 'starttime', 'finishtime', 'activityCategoryId'],
    	'editActivity'             => ['id', 'name', 'status', 'starttime', 'finishtime', 'activityCategoryId'],
    	'delActivity'              => ['id'],
    	'changeActivityStatus'     => ['id', 'status'],
    ];

}