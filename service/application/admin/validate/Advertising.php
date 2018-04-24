<?php
/**
 * 广告位验证信息
 */

namespace app\admin\validate;

use think\Validate;

class Advertising extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
            'name' 	        => 'require',
            'sketchImage'   => 'require',
            'size'          => 'require',
            'webImage'      => 'require',
            'id'            => 'require|number',
            'siteType'      => 'require|number',
            'terminal'      => 'require|number',
            'identifier'    => 'require',
        ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
            'id'             => '广告id不能为空',
            'name'           => '广告位名称不能为空',
            'sketchImage'    => '位置示意图不能为空',
            'size'           => '尺寸不能为空',
            'webImage'       => '广告图不能为空',
            'siteType'       => '广告类型不能为空',
            'terminal'       => '广告平台不能为空',
            'id.number'      => '广告id必须是数字',
            'siteType.number'=> '广告类型必须是数字',
            'terminal.number'=> '广告平台必须是数字',
            'identifier'     => '广告唯一标识不能为空',
        ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'addAdvertising' 	 => ['name','sketchImage','size','siteType','terminal','identifier'],
        'editAdvertising'    => ['id','name','sketchImage','size','webImage','siteType','terminal','identifier'],
        'getAdvertisingInfo' => ['id'],
    ];

}