<?php
/**
 * 公告
 * @createTime 2017/7/20 9:38
 */

namespace app\collect\controller;

use think\Loader;

class System {

    public function index() {
        Loader::model('common/System', 'logic')->checkCollectStatus();
    }
}