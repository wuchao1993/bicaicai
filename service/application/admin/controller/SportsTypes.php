<?php
/**
 * 体育项目
 * @createTime 2017/6/20 16:33
 */

namespace app\admin\controller;

use think\Loader;
use think\Config;

class SportsTypes {

    /**
     * 体育项目列表
     * @return array
     */
    public function index() {
        $sportsLogic = Loader::model('common/SportsTypes', 'logic');
        $data = $sportsLogic->getList();
        return return_result($sportsLogic->errorcode, output_format($data));
    }
}