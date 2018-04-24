<?php
/**
 * 赛果
 * @createTime 2017/5/04 09:09
 */

namespace app\api\controller;

use think\Request;
use think\Loader;
use think\Config;

class Results {

    /**
     * 赛果列表
     * @param Request $request
     * @return array
     */
    public function getList(Request $request) {
        $params['sport'] = $request->param('sport');
        $params['type']  = $request->param('type');
        $params['date']  = $request->param('date');
        $params['page']  = $request->param('page', 1);
        $resultsLogic = Loader::model('Results', 'logic');
        $data = $resultsLogic->getList($params);
        if (!$data) {
            $data = ['total_page' => 0, 'result' => []];
        }
        return [
            'errorcode' => $resultsLogic->errorcode,
            'message'   => Config::get('errorcode')[$resultsLogic->errorcode],
            'data'      => output_format($data)
        ];
    }

    /**
     * 获取详情
     * @param Request $request
     * @return mixed
     */
    public function getInfo(Request $request) {
        $gameId = $request->param('gameId');
        $type   = $request->param('type');
        $sport  = $request->param('sport');
        $resultsLogic = Loader::model('common/Results', 'logic');
        $data = $resultsLogic->getInfo($sport, $type, $gameId);
        return return_result($resultsLogic->errorcode, output_format($data));
    }
}