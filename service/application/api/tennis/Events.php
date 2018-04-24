<?php
/**
 * 赛事信息业务逻辑
 * @createTime 2017/8/07 10:45
 */

namespace app\api\tennis;

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
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'tennis:' . $type . ':' . $params['play_type_group'] . ':' . md5(serialize($params));
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        //获取玩法数据
        switch($params['play_type_group']) {
            //赛事
            case 'events' :
                $data = $this->events($type, $params['matches'], $params['master'], $params['period'], $params['order'], $params['page'], $params['date']);
                break;
            //波胆
            case 'correct_score' :
                //暂时没有波胆玩法的赛事类型
                if (in_array($type, ['in_play_now', 'parlay'])) {
                    //$this->errorcode = EC_EVENTS_NO_CORRECT_SCORE;
                    return ['total_page' => 0, 'result' => []];
                }
                $data = $this->correctScore($type, $params['matches'], $params['order'], $params['page'], $params['date']);
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
     * 赛事，独赢/让球/大小/球员局数大小
     * @param $type  in_play_now 滚球, today 今日，early 早盘，parlay 综合过关
     * @param $matches 1,2,3 联赛id
     * @param $master 是否只返回主盘口
     * @param string $period 是否显示赛节投注
     * @param string $order 排序
     * @param int $page 页码
     * @param string $date 日期
     * @return array
     */
    public function events($type, $matches, $master, $period, $order = 'time_asc', $page, $date = '') {
        //获取排序
        $orderBy = $this->getOrderBy($order);

        //获取公共where
        $where = $this->getCommonWhere($type, $matches, $date);
        if ($master == 'yes') {
            $where[] = [
                'exp',
                'stg_master=' . Config::get('status.tennis_game_master')['yes'] . ' OR ' . 'stg_is_period=' . Config::get('status.tennis_game_is_period')['yes']
            ];
        }
        if ($period == 'no') {
            $where['stg_is_period'] = Config::get('status.tennis_game_is_period')[$period];
        }

        //获取查询字段
        $field = $this->getFieldEvents($type);

        $join = [
            ['sports_tennis_matches m', 'g.stg_stm_id=m.stm_id', 'LEFT'],
            ['sports_tennis_schedules s', 'g.stg_sts_id=s.sts_id', 'LEFT']
        ];

        //计算总数
        $total = Loader::model('SportsTennisGames')
            ->alias('g')
            ->where($where)
            ->join($join)
            ->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsTennisGames')
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
        Loader::model('Matches', 'tennis')->createFilter($type, 'events', $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']    = $item['stm_id'];
            $data[$key]['match_name']  = $item['stm_name'];
            $data[$key]['game_id']     = $item['stg_game_id'];
            $data[$key]['game_type']   = $item['stg_game_type'];
            $data[$key]['master']      = $item['stg_master'];
            $data[$key]['schedule_id'] = $item['sts_id'];
            $data[$key]['home_name']   = $item['sts_home_name'];
            $data[$key]['guest_name']  = $item['sts_guest_name'];
            $data[$key]['in_play_now'] = $item['sts_in_play_now'];
            $data[$key]['begin_time']  = $item['sts_begin_time'];
            $data[$key]['best']        = $item['sts_best'];
            if ($type == 'in_play_now') {
                $data[$key]['home_game_score']   = $item['sts_home_game_score'];
                $data[$key]['guest_game_score']  = $item['sts_guest_game_score'];
                $data[$key]['home_set_score']    = $item['sts_home_set_score'];
                $data[$key]['guest_set_score']   = $item['sts_guest_set_score'];
                $data[$key]['home_point_score']  = $item['sts_home_point_score'];
                $data[$key]['guest_point_score'] = $item['sts_guest_point_score'];
                $data[$key]['home_score_1st']    = $item['sts_home_score_1st'];
                $data[$key]['guest_score_1st']   = $item['sts_guest_score_1st'];
                $data[$key]['home_score_2nd']    = $item['sts_home_score_2nd'];
                $data[$key]['guest_score_2nd']   = $item['sts_guest_score_2nd'];
                $data[$key]['home_score_3rd']    = $item['sts_home_score_3rd'];
                $data[$key]['guest_score_3rd']   = $item['sts_guest_score_3rd'];
                $data[$key]['home_score_4th']    = $item['sts_home_score_4th'];
                $data[$key]['guest_score_4th']   = $item['sts_guest_score_4th'];
                $data[$key]['home_score_5th']    = $item['sts_home_score_5th'];
                $data[$key]['guest_score_5th']   = $item['sts_guest_score_5th'];
                $data[$key]['now_server']        = $item['sts_now_server'];
                $data[$key]['timer']             = $item['sts_timer'];
                $data[$key]['show_delay']        = Config::get('status.tennis_schedule_show_delay_id')[$item['sts_show_delay']];
            }
            if ($type == 'parlay') {
                $data[$key]['parlay_min'] = $item['stg_parlay_min'];
                $data[$key]['parlay_max'] = $item['stg_parlay_max'];
                $data[$key]['odds']['1x2']        = !empty($item['stg_parlay_1x2']) ? json_decode($item['stg_parlay_1x2'], true) : [];
                $data[$key]['odds']['handicap']   = !empty($item['stg_parlay_handicap']) ? json_decode($item['stg_parlay_handicap'], true) : [];
                $data[$key]['odds']['ou']         = !empty($item['stg_parlay_ou']) ? json_decode($item['stg_parlay_ou'], true) : [];
                $data[$key]['odds']['ou_pg']      = !empty($item['stg_parlay_ou_pg']) ? json_decode($item['stg_parlay_ou_pg'], true) : [];
            } else {
                $data[$key]['odds']['1x2']      = !empty($item['stg_1x2']) ? json_decode($item['stg_1x2'], true) : [];
                $data[$key]['odds']['handicap'] = !empty($item['stg_handicap']) ? json_decode($item['stg_handicap'], true) : [];
                $data[$key]['odds']['ou']       = !empty($item['stg_ou']) ? json_decode($item['stg_ou'], true) : [];
                $data[$key]['odds']['ou_pg']    = !empty($item['stg_ou_pg']) ? json_decode($item['stg_ou_pg'], true) : [];
            }
        }

        return ['total_page' => ceil($total / $this->pageSizeWeb), 'result' => $this->formatEventsReturnData($data)];
    }

    /**
     * 波胆玩法
     * @param $type  in_play_now 滚球, today 今日，early 早盘，parlay 综合过关
     * @param $matches 1,2,3 联赛id
     * @param string $order 排序
     * @param int $page 页码
     * @param string $date 日期
     * @return array
     */
    public function correctScore($type, $matches, $order = 'time_asc', $page, $date = '') {
        //获取排序
        $orderBy = $this->getOrderBy($order);

        //获取公共where
        $where = $this->getCommonWhere($type, $matches, $date);
        $where['stg_correct_score'] = ['NEQ', ''];

        $field = [
            'sts_id',
            'stm_id',
            'stm_name',
            'stg_game_id',
            'stg_game_type',
            'stg_correct_score',
            'sts_begin_time',
            'sts_home_name',
            'sts_guest_name',
            'sts_in_play_now'
        ];

        $join = [
            ['sports_tennis_matches m', 'g.stg_stm_id=m.stm_id', 'LEFT'],
            ['sports_tennis_schedules s', 'g.stg_sts_id=s.sts_id', 'LEFT']
        ];

        //计算总数
        $total = Loader::model('SportsTennisGames')->alias('g')->where($where)->join($join)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsTennisGames')
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
        Loader::model('Matches', 'tennis')->createFilter($type, 'correct_score', $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']    = $item['stm_id'];
            $data[$key]['match_name']  = $item['stm_name'];
            $data[$key]['game_id']     = $item['stg_game_id'];
            $data[$key]['game_type']   = $item['stg_game_type'];
            $data[$key]['schedule_id'] = $item['sts_id'];
            $data[$key]['home_name']   = $item['sts_home_name'];
            $data[$key]['guest_name']  = $item['sts_guest_name'];
            $data[$key]['in_play_now'] = $item['sts_in_play_now'];
            $data[$key]['begin_time']  = $item['sts_begin_time'];
            $data[$key]['odds']['correct_score'] = !empty($item['stg_correct_score']) ? json_decode($item['stg_correct_score'], true) : [];
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
                $orderBy = ['sto_end_time' => 'asc', 'stm_sort' => 'asc'];
                break;
            case 'time_desc' :
                $orderBy = ['sto_end_time' => 'desc', 'stm_sort' => 'asc'];
                break;
            case 'match_asc' :
                $orderBy = ['stm_sort' => 'asc', 'sto_end_time' => 'asc'];
                break;
            case 'match_desc' :
                $orderBy = ['stm_sort' => 'desc', 'sto_end_time' => 'asc'];
                break;
            default :
                $orderBy = ['sto_end_time' => 'asc', 'stm_sort' => 'asc'];
                break;
        }

        //where查询条件
        $where = [
            'sto_is_show' => Config::get('status.tennis_outright_is_show')['yes'],
            'sto_end_time' => ['EGT', date('Y-m-d H:i:s')]
        ];
        //联赛筛选
        if ($matches) {
            $where['sto_stm_id'] = ['in', $matches];
        }

        $field = [
            'sto_game_id',
            'stm_name',
            'stm_id',
            'sto_game_type',
            'sto_odds',
            'sto_end_time',
        ];

        $join = [
            ['sports_tennis_matches m', 'o.sto_stm_id=m.stm_id', 'LEFT'],
        ];

        //计算总数
        $total = Loader::model('SportsTennisOutright')->alias('o')->where($where)->join($join)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsTennisOutright')
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
        Loader::model('Matches', 'tennis')->createFilter($type, 'outright', $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']   = $item['stm_id'];
            $data[$key]['match_name'] = $item['stm_name'];
            $data[$key]['game_id']    = $item['sto_game_id'];
            $data[$key]['game_type']  = $item['sto_game_type'];
            $data[$key]['end_time']   = $item['sto_end_time'];
            $data[$key]['outright']   = !empty($item['sto_odds']) ? json_decode($item['sto_odds'], true) : [];
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
    public function getFieldEvents($type) {
        $field = [
            'sts_id',
            'stm_id',
            'stm_name',
            'stg_game_id',
            'stg_game_type',
            'stg_master',
            'sts_begin_time',
            'sts_home_name',
            'sts_guest_name',
            'sts_in_play_now',
            'sts_best',
        ];

        //滚球字段
        if ($type == 'in_play_now') {
            $field = array_merge($field, [
                'sts_home_game_score',
                'sts_guest_game_score',
                'sts_home_set_score',
                'sts_guest_set_score',
                'sts_home_point_score',
                'sts_guest_point_score',
                'sts_home_score_1st',
                'sts_guest_score_1st',
                'sts_home_score_2nd',
                'sts_guest_score_2nd',
                'sts_home_score_3rd',
                'sts_guest_score_3rd',
                'sts_home_score_4th',
                'sts_guest_score_4th',
                'sts_home_score_5th',
                'sts_guest_score_5th',
                'sts_now_server',
                'sts_timer',
                'sts_show_delay',
            ]);
        }

        //综合过关字段
        if ($type == 'parlay') {
            $field = array_merge($field, [
                'stg_parlay_1x2',
                'stg_parlay_handicap',
                'stg_parlay_ou',
                'stg_parlay_ou_pg',
                'stg_parlay_min',
                'stg_parlay_max',
            ]);
        } else {
            $field = array_merge($field, [
                'stg_1x2',
                'stg_handicap',
                'stg_ou',
                'stg_ou_pg',
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
        $commonOrder = ['sts_begin_time' => 'asc', 'sts_id' => 'asc', 'stg_game_id' => 'asc'];
        switch($order) {
            case 'time_asc' :
                $orderBy = $commonOrder;
                break;
            case 'time_desc' :
                $orderBy = ['sts_begin_time' => 'desc', 'sts_id' => 'asc', 'stg_game_id' => 'asc'];
                break;
            case 'match_asc' :
                $orderBy = ['stm_sort' => 'asc', 'stm_id' => 'desc'];
                $orderBy = array_merge($orderBy, $commonOrder);
                break;
            case 'match_desc' :
                $orderBy = ['stm_sort' => 'desc', 'stm_id' => 'desc'];
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
        $where['stg_is_show']      = Config::get('status.tennis_game_is_show')['yes'];
        $where['sts_check_status'] = Config::get('status.tennis_schedule_check_status')['normal'];

        //赛事类型的查询条件
        $eventTypeWhere = $this->getEventTypeWhere($eventType, $date);
        if ($eventTypeWhere) {
            $where = array_merge($where, $eventTypeWhere);
        }

        //联赛筛选
        if ($matches) {
            $where['stg_stm_id'] = ['in', $matches];
        }

        return $where;
    }

    /**
     * 返回赛事类型的查询条件
     * @param $eventType
     * @param $date
     * @return bool
     */
    public function getEventTypeWhere($eventType, $date = '') {
        switch($eventType) {
            case 'in_play_now' :
                $where['sts_in_play_now'] = ['EQ', Config::get('status.tennis_schedule_in_play_now')['yes']];
                $where['sts_status'] = Config::get('status.tennis_schedule_status')['in_game'];
                break;
            case 'today' :
                $sTime = date('Y-m-d H:i:s');
                $eTime = date('Y-m-d') . ' 23:59:59';
                $where['sts_status'] = ['EQ', Config::get('status.tennis_schedule_status')['not_begin']];
                $where['sts_begin_time'] = ['BETWEEN', [$sTime, $eTime]];
                break;
            case 'early' :
                if ($date) {
                    $sTime = $date . ' 00:00:00';
                    $eTime = $date . ' 23:59:59';
                    $where['sts_begin_time'] = ['BETWEEN', [$sTime, $eTime]];
                } else {
                    $sTime = date('Y-m-d', strtotime('+1 day')) . ' 00:00:00';
                    $where['sts_begin_time'] = ['EGT', $sTime];
                }
                break;
            case 'parlay' :
                $where['stg_parlay'] = ['EQ', Config::get('status.tennis_game_parlay')['yes']];
                if ($date) {
                    $sTime = $date . ' 00:00:00';
                    $eTime = $date . ' 23:59:59';
                    $where['sts_begin_time'] = ['BETWEEN', [$sTime, $eTime]];
                } else {
                    $sTime = date('Y-m-d H:i:s');
                    $where['sts_begin_time'] = ['EGT', $sTime];
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
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'tennis_count_events_type_num';
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }

        $total = 0;

        $whereMaster['stg_master'] = Config::get('status.tennis_game_master')['yes'];

        //滚球赛事数量
        $where = array_merge($whereMaster, $this->getCommonWhere('in_play_now'));
        $join = [
            ['sports_tennis_games g', 's.sts_id=g.stg_sts_id', 'LEFT']
        ];
        $eventsTypeArr['in_play_now'] = Loader::model('SportsTennisSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['in_play_now'];

        //今日赛事数量
        $where = array_merge($whereMaster, $this->getCommonWhere('today'));
        $join = [
            ['sports_tennis_games g', 's.sts_id=g.stg_sts_id', 'LEFT']
        ];
        $eventsTypeArr['today'] = Loader::model('SportsTennisSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['today'];

        //早盘赛事数量
        $where = array_merge($whereMaster, $this->getCommonWhere('early'));
        $join = [
            ['sports_tennis_games g', 's.sts_id=g.stg_sts_id', 'LEFT']
        ];
        $eventsTypeArr['early'] = Loader::model('SportsTennisSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['early'];

        //综合过关赛事数量
        $where = array_merge($whereMaster, $this->getCommonWhere('parlay'));
        $join = [
            ['sports_tennis_games g', 's.sts_id=g.stg_sts_id', 'LEFT']
        ];
        $eventsTypeArr['parlay'] = Loader::model('SportsTennisSchedules')
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
    public function formatEventsReturnData($data) {
        $schedules = $matches = [];
        foreach($data as $key => $item) {
            $scheduleId = $item['schedule_id'];
            if (isset($schedules[$scheduleId])) {
                $gameInfo['odds']      = $item['odds'];
                $gameInfo['game_id']   = $item['game_id'];
                $gameInfo['game_type'] = $item['game_type'];
                $gameInfo['is_master'] = Config::get('status.tennis_game_master_id')[$item['master']];

                $schedules[$scheduleId]['games'][] = $gameInfo;
            } else {
                $gameInfo['odds']      = $item['odds'];
                $gameInfo['game_id']   = $item['game_id'];
                $gameInfo['game_type'] = $item['game_type'];
                $gameInfo['is_master'] = Config::get('status.tennis_game_master_id')[$item['master']];

                unset($item['odds'], $item['game_id'], $item['game_type'], $item['master']);
                $schedules[$scheduleId] = $item;
                $schedules[$scheduleId]['games'][] = $gameInfo;
            }
        }

        $schedules = array_values($schedules);

        foreach($schedules as $key => $item) {
            $matchId = $item['match_id'];
            if (isset($matches[$matchId])) {
                unset($item['match_id'], $item['match_name']);
                $matches[$matchId]['schedules'][] = $item;
            } else {
                $matches[$matchId]['match_id'] = $item['match_id'];
                $matches[$matchId]['match_name'] = $item['match_name'];
                unset($item['match_id'], $item['match_name']);
                $matches[$matchId]['schedules'][] = $item;
            }
        }
        unset($data, $schedules);
        return array_values($matches);
    }

    /**
     * 格式化玩法的返回数据，按照联赛归类
     * @param $data
     * @return array
     */
    public function formatReturnData($data) {
        $matches = [];
        foreach($data as $key => $item) {
            $matchId = $item['match_id'];
            if (isset($matches[$matchId])) {
                unset($item['match_id'], $item['match_name']);
                $matches[$matchId]['schedules'][] = $item;
            } else {
                $matches[$matchId]['match_id'] = $item['match_id'];
                $matches[$matchId]['match_name'] = $item['match_name'];
                unset($item['match_id'], $item['match_name']);
                $matches[$matchId]['schedules'][] = $item;
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
        if (!isset(Config::get('common.play_type')['tennis'][$params['play_type']])) {
            $this->errorcode = EC_EVENTS_PLAY_TYPE_ERROR;
            return false;
        }
        if ($params['event_type'] == 'parlay') {
            $playType = [
                //综合过关
                'parlay_1x2'      => 'stg_parlay_1x2',
                'parlay_handicap' => 'stg_parlay_handicap',
                'parlay_ou'       => 'stg_parlay_ou',
                'parlay_ou_pg'    => 'stg_parlay_ou_pg',
            ];
        } else {
            $playType = [
                '1x2'           => 'stg_1x2',
                'handicap'      => 'stg_handicap',
                'ou'            => 'stg_ou',
                'ou_pg'         => 'stg_ou_pg',
                'outright'      => 'sto_odds',
                'correct_score' => 'stg_correct_score',
            ];
        }

        if ($params['play_type'] == 'outright') {
            if (!isset($playType[$params['play_type']])) {
                $this->errorcode = EC_EVENTS_PLAY_TYPE_ERROR;
                return false;
            }
            $field[] = $playTypeField = $playType[$params['play_type']];

            //获取盘口信息
            $where = ['sto_game_id' => $params['game_id']];
            $gameInfo = Loader::model('SportsTennisOutright')->where($where)->field($field)->find();
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
            $field = ['stg_stm_id', 'stg_home_id', 'stg_guest_id', 'stg_game_type', 'stg_event_type'];
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
            $where = ['stg_game_id' => $params['game_id']];
            $gameInfo = Loader::model('SportsTennisGames')->where($where)->field($field)->find();
            is_object($gameInfo) && $gameInfo = $gameInfo->toArray();
            if (!$gameInfo || !$gameInfo[$playTypeField]) {
                $this->errorcode = EC_GAME_INFO_EMPTY;
                return false;
            }

            //判断是否从今日变到滚球
            if ($params['event_type'] == 'today' && Config::get('status.tennis_game_event_type_id')[$gameInfo['stg_event_type']] != $params['event_type']) {
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
                $teamInfo = Loader::model('Teams', 'tennis')->getInfoById($gameInfo['stg_home_id']);
                $data['home_name'] = $teamInfo['stt_name'];
                $teamInfo = Loader::model('Teams', 'tennis')->getInfoById($gameInfo['stg_guest_id']);
                $data['guest_name'] = $teamInfo['stt_name'];

                //盘口类型
                $data['game_type'] = Config::get('status.tennis_game_type')[$gameInfo['stg_game_type']];

                //获取联赛信息
                $matchInfo = Loader::model('Matches', 'tennis')->getInfoById($gameInfo['stg_stm_id']);
                $data['match_name'] = $matchInfo['stm_name'];

                //玩法名称
                $data['play_type_name'] = Config::get('common.play_type')['tennis'][$params['play_type']];

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