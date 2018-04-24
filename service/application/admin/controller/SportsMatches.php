<?php
/**
 * 联赛管理
 * @createTime 2017/6/23 15:33
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class SportsMatches {

    /**
     * 联赛列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request) {
        $params['sport_type'] = $request->param('sportType');
        $params['is_hot']     = $request->param('isHot');
        $params['page']       = $request->param('page');
        $params['page_size']  = $request->param('num');
        $params['search_word']= $request->param('search_word');

        $matchLogic = Loader::model('SportsMatches', 'logic');
        $data = $matchLogic->getList($params);

        return return_result($matchLogic->errorcode, output_format($data));
    }

    /**
     * 修改联赛排序
     * @param Request $request
     * @return array
     */
    public function updateSort(Request $request) {
        $sportType = $request->param('sportType');
        $id = $request->param('id');
        $sort = $request->param('sort');
        $matchLogic = Loader::model('SportsMatches', 'logic');
        $matchLogic->updateSort($sportType, $id, $sort);

        return return_result($matchLogic->errorcode);
    }

    /**
     * 修改是否热门
     * @param Request $request
     * @return array
     */
    public function updateHot(Request $request) {
        $sportType = $request->param('sportType');
        $id = $request->param('id');
        $hot = $request->param('hot');
        $matchLogic = Loader::model('SportsMatches', 'logic');
        $matchLogic->updateHot($sportType, $id, $hot);

        return return_result($matchLogic->errorcode);
    }
}