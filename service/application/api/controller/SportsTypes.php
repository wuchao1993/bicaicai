<?php
/**
 * 球类控制器
 * @createTime 2017/4/1 16:06
 */

namespace app\api\controller;

use think\Loader;

class SportsTypes {

    /**
     * 体育项目列表
     * @return array
     */
    public function getList() {
        $sportsLogic = Loader::model('SportsTypes', 'logic');
        $data = $sportsLogic->getList();
        return return_result($sportsLogic->errorcode, output_format($data));
    }
}