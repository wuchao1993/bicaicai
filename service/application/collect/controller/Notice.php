<?php
/**
 * 公告
 * @createTime 2017/7/20 9:38
 */

namespace app\collect\controller;

use think\Loader;
use think\Request;

class Notice {

    public function index(Request $request) {
        $date = $request->param('date');
        Loader::model('Notice', 'logic')->collect($date);
    }
}