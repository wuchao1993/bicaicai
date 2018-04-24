<?php
/**
 * 广告位验证信息
 */

namespace app\api\validate;

use think\Validate;

class Advertising extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
            'siteType'      => 'require|number',
            'terminal'      => 'require|number',
        ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
            'siteType'       => '广告类型不能为空',
            'terminal'       => '广告平台不能为空',
            'siteType.number'=> '广告类型必须是数字',
            'terminal.number'=> '广告平台必须是数字',
        ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getList' 	 => ['siteType','terminal'],
    ];

}