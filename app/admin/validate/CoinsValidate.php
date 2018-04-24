<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\validate;

use think\Validate;

class CoinsValidate extends Validate
{
    protected $rule = [
        'coin_name' => 'require',
        'icon_url'  => 'require',
    ];

    protected $message = [
        'coin_name.require' => '数字货币名称不能为空',
        'icon_url.require'  => '数字货币图标不能为空',
    ];

    protected $scene = [
        'add'  => ['coin_name','icon_url'],
        'edit' => ['coin_name','icon_url'],
    ];
}