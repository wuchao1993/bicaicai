<?php
/**
 * 球队业务逻辑
 * @createTime 2017/4/8 17:14
 */

namespace app\api\basketball;

use think\Cache;
use think\Model;

class Teams extends \app\common\basketball\Teams {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;
}