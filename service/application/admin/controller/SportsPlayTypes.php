<?php
/**
 * 玩法管理
 * @createTime 2017/07/12 15:33
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class SportsPlayTypes {

    /**
     * 玩法列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request) {
        $params['sport_id']  = $request->param('sportId');
        $params['page']      = $request->param('page');
        $params['page_size'] = $request->param('num');

        $logic = Loader::model('SportsPlayTypes', 'logic');
        $data = $logic->getList($params);

        return return_result($logic->errorcode, output_format($data));
    }

    /**
     * 修改限额
     * @param Request $request
     * @return array
     */
    public function updateLimit(Request $request) {
        $betLimitMax   = $request->param('betLimitMax');
        $matchLimit    = $request->param('matchLimit');
        $betLimitMin   = $request->param('betLimitMin');
        $logic = Loader::model('SportsPlayTypes', 'logic');
        if ($request->param('editType') == 'all') {
           $sportId  = $request->param('sportId');
           $logic->updateAllLimit($betLimitMax, $matchLimit, $betLimitMin, $sportId);
        }else{
           $playTypeId = $request->param('playTypeId');
           $logic->updateLimit($playTypeId, $betLimitMax, $matchLimit, $betLimitMin);
        }

        return return_result($logic->errorcode);
    }
}