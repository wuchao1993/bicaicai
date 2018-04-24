<?php
/**
 * 弹窗广告验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Validate;

class Advert extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'id'     => 'require',
        'page'   => 'number',
        'num'    => 'number',
        'name'   => 'require',
        'status' => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'id.require'     => 'ID不能为空',
        'page'           => '页码格式不合法',
        'num'            => '分页数量不合法',
        'name'           => '名称不能为空',
        'status.require' => '状态不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'getAdvertInfo'      => ['id'],
        'addAdvert'          => ['name'],
        'editAdvert'         => [
            'id',
            'name'
        ],
        'changeAdvertStatus' => [
            'id',
            'status'
        ],
    ];

}