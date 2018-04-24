<?php
/**
 * 公告验证器
 */

namespace app\admin\validate;

use think\Config;
use think\Validate;

class Notice extends Validate {

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
    	'createtime' => 'require',
    	'status'	 => 'require',
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
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getNoticeList' 	 => ['page', 'num'],
    	'getNoticeInfo' 	 => ['id'],
    	'addNotice' 		 => ['title', 'type', 'createtime', 'status'],
    	'editNotice' 		 => ['id', 'title', 'type', 'createtime', 'status'],
    	'delNotice' 		 => ['id'],
    	'changeNoticeStatus' => ['id', 'status'],
    ];

}