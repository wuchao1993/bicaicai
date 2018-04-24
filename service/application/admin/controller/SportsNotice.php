<?php
/**
 * 玩法管理
 * @createTime 2017/07/12 15:33
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class SportsNotice {

    /**
     * 公告列表
     * @param Request $request
     * @return mixed
     */
    public function collected(Request $request) {
        $params['date']      = $request->param('date');
        $params['page']      = $request->param('page');
        $params['page_size'] = $request->param('num');

        $logic = Loader::model('SportsNotice', 'logic');
        $data = $logic->getCollected($params);

        return return_result($logic->errorcode, output_format($data));
    }
}