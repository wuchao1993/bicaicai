<?php
/**
 * 篮球赛事信息业务逻辑
 * @createTime 2017/8/07 10:45
 */

namespace app\api\basketball;

use think\Cache;
use think\Config;
use think\Loader;
use think\Model;

class Events extends Model {

    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 每页显示数量
     * @var int
     */
    public $pageSize = 10;

    /**
     * 前端要求当有传page_all的时候，返回的total_page按照$pageSize来计算
     * @var
     */
    public $pageSizeWeb;

    /**
     * 获取滚球，今日赛事，早盘，综合过关数据
     * @param $type in_play_now 滚球, today 今日，early 早盘，parlay 综合过关
     * @param $params
     * @return bool|mixed
     */
    public function getEventsData($type, $params) {
        empty($params['page']) && $params['page'] = 1;
        $this->pageSizeWeb = $this->pageSize;

        //一次性获取N页的数据，用于客户端刷新数据
        if ($params['page_all'] > 0) {
            $params['page'] = 1;
            $this->pageSize = $this->pageSize * $params['page_all'];
        }

        //缓存获取数据
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'basketball:' . $type . ':' . $params['play_type_group'] . ':' . md5(serialize($params));
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        //获取玩法数据
        switch($params['play_type_group']) {
            //独赢/让球/大小/单双
            case '1x2-handicap-ou-oe' :
                $data = $this->handicapOuOe1x2($type, $params['matches'], $params['master'], $params['period'], $params['order'], $params['page'], $params['date']);
                break;
            //冠军
            case 'outright' :
                //暂时没有该玩法的赛事类型
                if (in_array($type, ['in_play_now', 'parlay'])) {
                    //$this->errorcode = EC_EVENTS_NO_OUTRIGHT;
                    return ['total_page' => 0, 'result' => []];
                }
                $data = $this->outright($type, $params['matches'], $params['order'], $params['page']);
                break;
            default :
                $this->errorcode = EC_EVENTS_PLAY_TYPE_ERROR;
                return false;
                break;
        }

        if ($type == 'in_play_now') {
            $cacheTime = Config::get('common.cache_time')['in_play_now'];
        } else {
            $cacheTime = Config::get('common.cache_time')['events_data'];
        }
        Cache::set($cacheKey, $data, $cacheTime);
        return $data;
    }

    /**
     * 独赢/让球/大小/单双
     * @param $type  in_play_now 滚球, today 今日，early 早盘，parlay 综合过关
     * @param $matches 1,2,3 联赛id
     * @param $master 是否只返回主盘口
     * @param string $period 是否显示赛节投注
     * @param string $order 排序
     * @param int $page 页码
     * @param string $date 日期
     * @return array
     */
    public function handicapOuOe1x2($type, $matches, $master, $period, $order = 'time_asc', $page, $date = '') {
        //获取排序
        $orderBy = $this->getOrderBy($order);

        //获取公共where
        $where = $this->getCommonWhere($type, $matches, $date);
        if ($master == 'yes') {
            $where[] = [
                'exp',
                'sbg_master=' . Config::get('status.basketball_game_master')['yes'] . ' OR ' . 'sbg_is_period=' . Config::get('status.basketball_game_is_period')['yes']
            ];
        }
        if ($period == 'no') {
            $where['sbg_is_period'] = Config::get('status.basketball_game_is_period')[$period];
        }

        //获取查询字段
        $field = $this->getFieldHandicapOuOe1x2($type);

        $join = [
            ['sports_basketball_matches m', 'g.sbg_sbm_id=m.sbm_id', 'LEFT'],
            ['sports_basketball_schedules s', 'g.sbg_sbs_id=s.sbs_id', 'LEFT']
        ];

        //计算总数
        $total = Loader::model('SportsBasketballGames')
            ->alias('g')
            ->where($where)
            ->join($join)
            ->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsBasketballGames')
            ->alias('g')
            ->where($where)
            ->field($field)
            ->join($join)
            ->order($orderBy)
            ->page($page, $this->pageSize)
            ->select();
        if (!$ret) {
            return ['total_page' => 0, 'result' => []];
        }

        //生成联赛筛选的sql缓存
        Loader::model('Matches', 'basketball')->createFilter($type, '1x2-handicap-ou-oe', $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']    = $item['sbm_id'];
            $data[$key]['match_name']  = $item['sbm_name'];
            $data[$key]['game_id']     = $item['sbg_game_id'];
            $data[$key]['game_type']   = Config::get('status.basketball_game_type')[$item['sbg_game_type']];
            $data[$key]['schedule_id'] = $item['sbs_id'];
            $data[$key]['home_name']   = $item['sbs_home_name'];
            $data[$key]['guest_name']  = $item['sbs_guest_name'];
            $data[$key]['in_play_now'] = $item['sbs_in_play_now'];
            $data[$key]['begin_time']  = $item['sbs_begin_time'];
            $data[$key]['is_master']   = Config::get('status.basketball_game_master_id')[$item['sbg_master']];
            if ($type == 'in_play_now') {
                $data[$key]['home_score']  = $item['sbs_home_score'];
                $data[$key]['guest_score'] = $item['sbs_guest_score'];
                $data[$key]['quarter']     = $item['sbs_quarter'];
                $data[$key]['timer']       = $item['sbs_timer'];
            }
            if ($type == 'parlay') {
                $data[$key]['parlay_min'] = $item['sbg_parlay_min'];
                $data[$key]['parlay_max'] = $item['sbg_parlay_max'];
                $data[$key]['odds']['1x2']        = !empty($item['sbg_parlay_1x2']) ? json_decode($item['sbg_parlay_1x2'], true) : [];
                $data[$key]['odds']['handicap']   = !empty($item['sbg_parlay_handicap']) ? json_decode($item['sbg_parlay_handicap'], true) : [];
                $data[$key]['odds']['ou']         = !empty($item['sbg_parlay_ou']) ? json_decode($item['sbg_parlay_ou'], true) : [];
                $data[$key]['odds']['ou_team']    = !empty($item['sbg_parlay_ou_team']) ? json_decode($item['sbg_parlay_ou_team'], true) : [];
                $data[$key]['odds']['oe']         = !empty($item['sbg_parlay_oe']) ? json_decode($item['sbg_parlay_oe'], true) : [];
            } else {
                $data[$key]['odds']['1x2']      = !empty($item['sbg_1x2']) ? json_decode($item['sbg_1x2'], true) : [];
                $data[$key]['odds']['handicap'] = !empty($item['sbg_handicap']) ? json_decode($item['sbg_handicap'], true) : [];
                $data[$key]['odds']['ou']       = !empty($item['sbg_ou']) ? json_decode($item['sbg_ou'], true) : [];
                $data[$key]['odds']['ou_team']  = !empty($item['sbg_ou_team']) ? json_decode($item['sbg_ou_team'], true) : [];
                $data[$key]['odds']['oe']       = !empty($item['sbg_oe']) ? json_decode($item['sbg_oe'], true) : [];
            }
        }

        return ['total_page' => ceil($total / $this->pageSizeWeb), 'result' => $this->formatReturnData($data)];
    }

    /**
     * 冠军
     * @param $type  in-play-now 滚球, today 今日，early 早盘，parlay 综合过关
     * @param $matches 1,2,3 联赛id
     * @param string $order 排序
     * @param int $page 页码
     * @return array
     */
    public function outright($type, $matches, $order = 'time_asc', $page) {
        //获取排序
        switch($order) {
            case 'time_asc' :
                $orderBy = ['sbo_end_time' => 'asc', 'sbm_sort' => 'asc'];
                break;
            case 'time_desc' :
                $orderBy = ['sbo_end_time' => 'desc', 'sbm_sort' => 'asc'];
                break;
            case 'match_asc' :
                $orderBy = ['sbm_sort' => 'asc', 'sbo_end_time' => 'asc'];
                break;
            case 'match_desc' :
                $orderBy = ['sbm_sort' => 'desc', 'sbo_end_time' => 'asc'];
                break;
            default :
                $orderBy = ['sbo_end_time' => 'asc', 'sbm_sort' => 'asc'];
                break;
        }

        //where查询条件
        $where = [
            'sbo_is_show' => Config::get('status.basketball_outright_is_show')['yes'],
            'sbo_end_time' => ['EGT', date('Y-m-d H:i:s')]
        ];
        //联赛筛选
        if ($matches) {
            $where['sbo_sbm_id'] = ['in', $matches];
        }

        $field = [
            'sbo_game_id',
            'sbm_name',
            'sbm_id',
            'sbo_game_type',
            'sbo_odds',
            'sbo_end_time',
        ];

        $join = [
            ['sports_basketball_matches m', 'o.sbo_sbm_id=m.sbm_id', 'LEFT'],
        ];

        //计算总数
        $total = Loader::model('SportsBasketballOutright')->alias('o')->where($where)->join($join)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsBasketballOutright')
            ->alias('o')
            ->where($where)
            ->field($field)
            ->join($join)
            ->order($orderBy)
            ->page($page, $this->pageSize)
            ->select();
        if (!$ret) {
            return ['total_page' => 0, 'result' => []];
        }

        //生成联赛筛选的sql缓存
        Loader::model('Matches', 'basketball')->createFilter($type, 'outright', $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']   = $item['sbm_id'];
            $data[$key]['match_name'] = $item['sbm_name'];
            $data[$key]['game_id']    = $item['sbo_game_id'];
            $data[$key]['game_type']  = $item['sbo_game_type'];
            $data[$key]['end_time']   = $item['sbo_end_time'];
            $data[$key]['outright']   = !empty($item['sbo_odds']) ? json_decode($item['sbo_odds'], true) : [];
        }

        $matches = [];
        foreach($data as $key => $item) {
            $matchId = $item['match_id'];
            if ($matches[$matchId]) {
                unset($item['match_id'], $item['match_name'], $item['end_time']);
                $matches[$matchId]['game'][] = $item;
            } else {
                $matches[$matchId]['match_id'] = $item['match_id'];
                $matches[$matchId]['match_name'] = $item['match_name'];
                $matches[$matchId]['end_time'] = $item['end_time'];
                unset($item['match_id'], $item['match_name'], $item['end_time']);
                $matches[$matchId]['game'][] = $item;
            }
        }
        unset($data);
        return ['total_page' => ceil($total / $this->pageSizeWeb), 'result' => array_values($matches)];
    }

    /**
     * 返回查询字段
     * @param $type
     * @return array
     */
    public function getFieldHandicapOuOe1x2($type) {
        $field = [
            'sbs_id',
            'sbm_id',
            'sbm_name',
            'sbg_game_id',
            'sbg_game_type',
            'sbs_begin_time',
            'sbs_home_name',
            'sbs_guest_name',
            'sbs_in_play_now',
            'sbg_master',
        ];

        //滚球字段
        if ($type == 'in_play_now') {
            $field = array_merge($field, [
                'sbs_home_score',
                'sbs_guest_score',
                'sbs_quarter',
                'sbs_timer',
            ]);
        }

        //综合过关字段
        if ($type == 'parlay') {
            $field = array_merge($field, [
                'sbg_parlay_1x2',
                'sbg_parlay_handicap',
                'sbg_parlay_ou',
                'sbg_parlay_ou_team',
                'sbg_parlay_oe',
                'sbg_parlay_min',
                'sbg_parlay_max',
            ]);
        } else {
            $field = array_merge($field, [
                'sbg_1x2',
                'sbg_handicap',
                'sbg_ou',
                'sbg_ou_team',
                'sbg_oe',
            ]);
        }
        return $field;
    }

    /**
     * 获取排序方式
     * @param $order 排序值
     * @return array
     */
    public function getOrderBy($order) {
        //排序
        $commonOrder = ['sbs_begin_time' => 'asc', 'sbs_id' => 'asc', 'sbg_game_type' => 'asc', 'sbg_game_id' => 'asc'];
        switch($order) {
            case 'time_asc' :
                $orderBy = $commonOrder;
                break;
            case 'time_desc' :
                $orderBy = ['sbs_begin_time' => 'desc', 'sbs_id' => 'asc', 'sbg_game_type' => 'asc', 'sbg_game_id' => 'asc'];
                break;
            case 'match_asc' :
                $orderBy = ['sbm_sort' => 'asc', 'sbm_id' => 'desc'];
                $orderBy = array_merge($orderBy, $commonOrder);
                break;
            case 'match_desc' :
                $orderBy = ['sbm_sort' => 'desc', 'sbm_id' => 'desc'];
                $orderBy = array_merge($orderBy, $commonOrder);
                break;
            default :
                $orderBy = $commonOrder;
                break;
        }

        return $orderBy;
    }

    /**
     * 返回玩法的where条件
     * @param $eventType
     * @param $matches
     * @param $date
     * @return mixed
     */
    public function getCommonWhere($eventType, $matches = '', $date = '') {
        $where = [];
        $where['sbg_is_show']      = Config::get('status.basketball_game_is_show')['yes'];
        $where['sbs_check_status'] = Config::get('status.basketball_schedule_check_status')['normal'];

        //赛事类型的查询条件
        $eventTypeWhere = $this->getEventTypeWhere($eventType, $date);
        if ($eventTypeWhere) {
            $where = array_merge($where, $eventTypeWhere);
        }

        //联赛筛选
        if ($matches) {
            $where['sbg_sbm_id'] = ['in', $matches];
        }

        return $where;
    }

    /**
     * 返回赛事类型的查询条件
     * @param $eventType 赛事类型
     * @param string $date 具体日期
     * @return bool
     */
    public function getEventTypeWhere($eventType, $date = '') {
        switch($eventType) {
            case 'in_play_now' :
                $where['sbs_in_play_now'] = ['EQ', Config::get('status.basketball_schedule_in_play_now')['yes']];
                $where['sbs_status'] = ['IN', [
                    Config::get('status.basketball_schedule_status')['half_time'],
                    Config::get('status.basketball_schedule_status')['in_game'],
                ]];
                break;
            case 'today' :
                $sTime = date('Y-m-d H:i:s');
                $eTime = date('Y-m-d') . ' 23:59:59';
                $where['sbs_status'] = ['EQ', Config::get('status.basketball_schedule_status')['not_begin']];
                $where['sbs_begin_time'] = ['BETWEEN', [$sTime, $eTime]];
                break;
            case 'early' :
                if ($date) {
                    $sTime = $date . ' 00:00:00';
                    $eTime = $date . ' 23:59:59';
                    $where['sbs_begin_time'] = ['BETWEEN', [$sTime, $eTime]];
                } else {
                    $sTime = date('Y-m-d', strtotime('+1 day')) . ' 00:00:00';
                    $where['sbs_begin_time'] = ['EGT', $sTime];
                }
                break;
            case 'parlay' :
                $where['sbg_parlay'] = ['EQ', Config::get('status.basketball_game_parlay')['yes']];
                if ($date) {
                    $sTime = $date . ' 00:00:00';
                    $eTime = $date . ' 23:59:59';
                    $where['sbs_begin_time'] = ['BETWEEN', [$sTime, $eTime]];
                } else {
                    $sTime = date('Y-m-d H:i:s');
                    $where['sbs_begin_time'] = ['EGT', $sTime];
                }
                break;
            default:
                return false;
                break;
        }
        return $where;
    }

    /**
     * 计算滚球，今日赛事，早盘，综合过关的赛事数量
     * @param $eventsTypeArr
     * @param bool $isCache 是否走缓存
     * @return mixed
     */
    public function countEventsTypeNum($eventsTypeArr = [], $isCache = true) {
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'basketball_count_events_type_num';
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }

        $total = 0;

        $whereMaster['sbg_master'] = Config::get('status.basketball_game_master')['yes'];

        //滚球赛事数量
        $where = array_merge($whereMaster, $this->getCommonWhere('in_play_now'));
        $join = [
            ['sports_basketball_games g', 's.sbs_id=g.sbg_sbs_id', 'LEFT']
        ];
        $eventsTypeArr['in_play_now'] = Loader::model('SportsBasketballSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['in_play_now'];

        //今日赛事数量
        $where = array_merge($whereMaster, $this->getCommonWhere('today'));
        $join = [
            ['sports_basketball_games g', 's.sbs_id=g.sbg_sbs_id', 'LEFT']
        ];
        $eventsTypeArr['today'] = Loader::model('SportsBasketballSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['today'];

        //早盘赛事数量
        $where = array_merge($whereMaster, $this->getCommonWhere('early'));
        $join = [
            ['sports_basketball_games g', 's.sbs_id=g.sbg_sbs_id', 'LEFT']
        ];
        $eventsTypeArr['early'] = Loader::model('SportsBasketballSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['early'];

        //综合过关赛事数量
        $where = array_merge($whereMaster, $this->getCommonWhere('parlay'));
        $join = [
            ['sports_basketball_games g', 's.sbs_id=g.sbg_sbs_id', 'LEFT']
        ];
        $eventsTypeArr['parlay'] = Loader::model('SportsBasketballSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['parlay'];

        //总数
        $eventsTypeArr['total'] = $total;

        Cache::set($cacheKey, $eventsTypeArr, Config::get('common.cache_time')['count_events_type_num']);
        return $eventsTypeArr;
    }

    /**
     * 格式化玩法的返回数据，按照联赛归类
     * @param $data
     * @return array
     */
    public function formatReturnData($data) {
        $matches = [];
        foreach($data as $key => $item) {
            if ($item['home_name'] == '主场' && $item['guest_name'] == '客场' && !empty($item['game_type'])) {
                $item['home_name'] .= '-' . $item['game_type'];
                $item['guest_name'] .= '-' . $item['game_type'];
                $item['game_type'] = '';
            }
            $matchId = $item['match_id'];
            if (isset($matches[$matchId])) {
                unset($item['match_id'], $item['match_name']);
                $matches[$matchId]['schedule'][] = $item;
            } else {
                $matches[$matchId]['match_id'] = $item['match_id'];
                $matches[$matchId]['match_name'] = $item['match_name'];
                unset($item['match_id'], $item['match_name']);
                $matches[$matchId]['schedule'][] = $item;
            }
        }
        unset($data);
        return array_values($matches);
    }

    /**
     * 获取某个玩法的最新赔率
     * @param $params
     * @param bool $detailInfo 是否需要返回详细信息
     * @return bool
     */
    public function refreshOdds($params, $detailInfo = false) {
        if (!isset(Config::get('common.play_type')['basketball'][$params['play_type']])) {
            $this->errorcode = EC_EVENTS_PLAY_TYPE_ERROR;
            return false;
        }
        if ($params['event_type'] == 'parlay') {
            $playType = [
                //综合过关
                'parlay_1x2'      => 'sbg_parlay_1x2',
                'parlay_handicap' => 'sbg_parlay_handicap',
                'parlay_ou'       => 'sbg_parlay_ou',
                'parlay_ou_team'  => 'sbg_parlay_ou_team',
                'parlay_oe'       => 'sbg_parlay_oe',
            ];
        } else {
            $playType = [
                '1x2'           => 'sbg_1x2',
                'handicap'      => 'sbg_handicap',
                'ou'            => 'sbg_ou',
                'ou_team'       => 'sbg_ou_team',
                'oe'            => 'sbg_oe',
                'outright'      => 'sbo_odds',
            ];
        }

        if ($params['play_type'] == 'outright') {
            if (!isset($playType[$params['play_type']])) {
                $this->errorcode = EC_EVENTS_PLAY_TYPE_ERROR;
                return false;
            }
            $field[] = $playTypeField = $playType[$params['play_type']];

            //获取盘口信息
            $where = ['sbo_game_id' => $params['game_id']];
            $gameInfo = Loader::model('SportsBasketballOutright')->where($where)->field($field)->find();
            is_object($gameInfo) && $gameInfo = $gameInfo->toArray();
            if (!$gameInfo || empty($gameInfo[$playTypeField])) {
                $this->errorcode = EC_GAME_INFO_EMPTY;
                return false;
            }

            //获取赔率
            $playTypeInfo = json_decode($gameInfo[$playTypeField], true);
            if (!isset($playTypeInfo[$params['odds_key']]) || empty($playTypeInfo[$params['odds_key']])) {
                $this->errorcode = EC_ODDS_KEY_NONE;
                return false;
            }
            $data['odds'] = $playTypeInfo[$params['odds_key']]['odds'];
        } else {
            $field = ['sbg_sbm_id', 'sbg_home_id', 'sbg_guest_id', 'sbg_game_type', 'sbg_event_type'];
            if ($params['event_type'] == 'parlay') {
                if (!isset($playType['parlay_' . $params['play_type']])) {
                    $this->errorcode = EC_EVENTS_PLAY_TYPE_ERROR;
                    return false;
                }
                $field[] = $playTypeField = $playType['parlay_' . $params['play_type']];
            } else {
                if (!isset($playType[$params['play_type']])) {
                    $this->errorcode = EC_EVENTS_PLAY_TYPE_ERROR;
                    return false;
                }
                $field[] = $playTypeField = $playType[$params['play_type']];
            }

            //获取盘口信息
            $where = ['sbg_game_id' => $params['game_id']];
            $gameInfo = Loader::model('SportsBasketballGames')->where($where)->field($field)->find();
            is_object($gameInfo) && $gameInfo = $gameInfo->toArray();
            if (!$gameInfo || !$gameInfo[$playTypeField]) {
                $this->errorcode = EC_GAME_INFO_EMPTY;
                return false;
            }

            //判断是否从今日变到滚球
            if ($params['event_type'] == 'today' && Config::get('status.basketball_game_event_type_id')[$gameInfo['sbg_event_type']] != $params['event_type']) {
                $this->errorcode = EC_ORDER_SCHEDULE_STATUS_CHANGE;
                return false;
            }

            //获取赔率
            $playTypeInfo = json_decode($gameInfo[$playTypeField], true);
            if (!isset($playTypeInfo[$params['odds_key']]) || empty($playTypeInfo[$params['odds_key']])) {
                $this->errorcode = EC_ODDS_KEY_NONE;
                return false;
            }
            $data['odds'] = $playTypeInfo[$params['odds_key']];
            if (isset($params['ratio_key']) && !empty($params['ratio_key'])) {
                $data['ratio'] = $playTypeInfo[$params['ratio_key']];
                if ($params['play_type'] == 'handicap') {
                    $data['strong'] = $playTypeInfo['strong'];
                }
            }

            //返回详细信息
            if ($detailInfo) {
                //获取球队信息
                $teamInfo = Loader::model('Teams', 'basketball')->getInfoById($gameInfo['sbg_home_id']);
                $data['home_name'] = $teamInfo['sbt_name'];
                $teamInfo = Loader::model('Teams', 'basketball')->getInfoById($gameInfo['sbg_guest_id']);
                $data['guest_name'] = $teamInfo['sbt_name'];

                //盘口类型
                $data['game_type'] = Config::get('status.basketball_game_type')[$gameInfo['sbg_game_type']];

                //获取联赛信息
                $matchInfo = Loader::model('Matches', 'basketball')->getInfoById($gameInfo['sbg_sbm_id']);
                $data['match_name'] = $matchInfo['sbm_name'];

                //玩法名称
                $data['play_type_name'] = Config::get('common.play_type')['basketball'][$params['play_type']];

                //获取下注信息字符串
                $betInfo = array_merge($data, $playTypeInfo, $params);
                $data['bet_info_string'] = Loader::model('Orders', 'logic')->handleBetInfoStr($betInfo);
            }
        }

        return array_merge($params, $data);
    }

    /**
     * 批量刷新赔率
     * @param $params
     * @param bool $detailInfo 是否需要返回详细信息
     * @return mixed
     */
    public function refreshOddsMulti($params, $detailInfo = false) {
        foreach($params as $key => $param) {
            $gameInfo = $this->refreshOdds($param, $detailInfo);
            if ($gameInfo) {
                $params[$key] = $gameInfo;
            }
        }
        return $params;
    }
}