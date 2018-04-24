<?php
/**
 * 球类赛事数据 TODO 验证器
 * @createTime 2017/8/8 11:35
 */

namespace app\api\controller;

use think\Cache;
use think\Config;
use think\helper\Str;
use think\Loader;
use think\Request;

class SportsEvents {

    /**
     * 盘口列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request) {
        $params['matches']         = $request->param('matches');
        $params['order']           = $request->param('order', 'time_asc');
        $params['play_type_group'] = $request->param('playTypeGroup');
        $params['page']            = $request->param('page');
        $params['page_all']        = $request->param('pageAll');
        $params['period']          = $request->param('period', 'yes');
        $params['master']          = $request->param('master', 'no');
        $params['date']            = $request->param('date');
        $eventType                 = Str::snake($request->param('eventType'));
        $sportType                 = $request->param('sportType');

        //获取维护状态
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'collect_status';
        $collectStatusInfo = Cache::get($cacheKey);
        if ($collectStatusInfo && $collectStatusInfo['status'] == false) {
            $response = [];
            if($collectStatusInfo['startTime'] && $collectStatusInfo['endTime']){
                $response = [
                    'startTime' => $collectStatusInfo['startTime'],
                    'endTime' => $collectStatusInfo['endTime'],
                ];
            }
            return return_result(COLLECT_SYSTEM_MAINTENANCE, $response);
        }

        $eventsLogic = Loader::model('Events', $sportType);
        $data = $eventsLogic->getEventsData($eventType, $params);

        return return_result($eventsLogic->errorcode, output_format($data));
    }

    /**
     * 联赛列表
     * @param Request $request
     * @return array
     */
    public function leagueMatches(Request $request) {
        $eventType     = $request->param('eventType');
        $playTypeGroup = $request->param('playTypeGroup');
        $sportType     = $request->param('sportType');
        $matchesLogic  = Loader::model('Matches', $sportType);
        $data = $matchesLogic->getLeagueMatches($eventType, $playTypeGroup);

        return return_result($matchesLogic->errorcode, output_format($data));
    }

    /**
     * 赛事类型
     * @param Request $request
     * @return array
     */
    public function eventsType(Request $request) {
        $sportId = $request->param('sportId');
        $sportsLogic = Loader::model('SportsTypes', 'logic');
        $data = $sportsLogic->getEventsTypeBySportId($sportId);
        return return_result($sportsLogic->errorcode, output_format($data));
    }

    /**
     * 玩法列表
     * @param Request $request
     * @return array
     */
    public function playType(Request $request) {
        $sportId = $request->param('sportId');
        $logic = Loader::model('SportsTypes', 'logic');
        $data = $logic->getPlayTypeGroupsBySportId($sportId);
        return return_result($logic->errorcode, output_format($data));
    }

    /**
     * 正在滚球的球类
     * @return mixed
     */
    public function inPlayNowSports() {
        $logic = Loader::model('Events', 'logic');
        $data = $logic->inPlayNowSports();
        return return_result($logic->errorcode, output_format($data));
    }

    /**
     * 根据今日早盘获取球类的玩法列表
     * @param Request $request
     * @return array
     */
    public function sportsPlayTypes(Request $request) {
        $eventType = $request->param('eventType');
        $logic = Loader::model('SportsTypes', 'logic');
        $data = $logic->getSportsTypeByEventType($eventType);
        return return_result($logic->errorcode, output_format($data));
    }

    /**
     * 更新单个盘口赔率
     * @param Request $request
     * @return array
     */
    public function refreshOdds(Request $request) {
        $params['game_id']    = $request->param('gameId');
        $params['event_type'] = $request->param('eventType');
        $params['play_type']  = Str::snake($request->param('playType'));
        $params['odds_key']   = Str::snake($request->param('oddsKey'));
        $params['ratio_key']  = Str::snake($request->param('ratioKey'));
        $sportType            = $request->param('sportType');
        $logic = Loader::model('Events', $sportType);
        $data = $logic->refreshOdds($params);

        return return_result($logic->errorcode, output_format($data));
    }

    /**
     * 批量赔率更新
     * @param Request $request
     * @return array
     */
    public function refreshOddsMulti(Request $request) {
        $gameInfo  = json_decode(htmlspecialchars_decode($request->param('gameInfo')), true);
        $gameInfo  = input_format($gameInfo, true);
        $sportType = $request->param('sportType');
        $logic = Loader::model('Events', $sportType);
        $data = $logic->refreshOddsMulti($gameInfo, true);

        return return_result($logic->errorcode, output_format($data));
    }

    /**
     * 日程表
     * @param Request $request
     * @return mixed
     */
    public function calendar(Request $request) {
        $sport = $request->param('sportType');
        $eventsLogic = Loader::model('Events', 'logic');
        $data = $eventsLogic->calendar($sport);
        return return_result($eventsLogic->errorcode, output_format($data));
    }
}