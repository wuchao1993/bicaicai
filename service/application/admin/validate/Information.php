<?php
/**
 * 后台帮助验证器
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class Information extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
    	'id' 	     => 'require',
    	'title'      => 'require',
    	'type'       => 'require',
    	'page'	     => 'number',
    	'num'	     => 'number',
        'content'    => 'require',
    	'createtime' => 'require',
    	'status'	 => 'require',
        'sort'       => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
    	'id'         => 'ID不能为空',
    	'page'       => '页码格式不合法',
    	'num'        => '分页数量不合法',
    	'type'       => '类型不能为空',
    	'createtime' => '发布时间不能为空',
    	'status'     => '状态不能为空',
    	'title'      => '标题不能为空',
        'content'    => '内容不能为空',
        'sort'       => '排序不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getInformationList' 	   => ['page', 'num'],
    	'getInformationInfo' 	   => ['id'],
    	'addInformation' 		   => ['title', 'type', 'createtime', 'status', 'content', 'sort'],
    	'editInformation' 		   => ['id', 'title', 'type', 'createtime', 'status', 'content', 'sort'],
    	'delInformation' 		   => ['id'],
    	'changeInformationStatus'  => ['id', 'status'],
    ];

}