<?php
/**
 * 足球赛事信息业务逻辑
 * @createTime 2017/4/10 10:45
 */

namespace app\api\football;

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
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'football:' . $type . ':' . $params['play_type_group'] . ':' . md5(serialize($params));
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        //获取玩法数据
        switch($params['play_type_group']) {
            //独赢/让球/大小/单双
            case '1x2-handicap-ou-oe' :
                $data = $this->handicapOuOe1x2($type, $params['matches'], $params['master'], $params['order'], $params['page'], $params['date']);
                break;
            //全场波胆
            case 'ft_correct_score' :
            case 'correct_score' :
                //暂时没有波胆玩法的赛事类型
                if (in_array($type, ['in_play_now', 'parlay'])) {
                    return ['total_page' => 0, 'result' => []];
                }
                $data = $this->correctScore($type, $params['matches'], 'ft', $params['order'], $params['page'], $params['date']);
                break;
            //半场波胆
            case '1h_correct_score' :
                //暂时没有波胆玩法的赛事类型
                if (in_array($type, ['in_play_now', 'parlay'])) {
                    return ['total_page' => 0, 'result' => []];
                }
                $data = $this->correctScore($type, $params['matches'], '1h', $params['order'], $params['page'], $params['date']);
                break;
            //半场/全场
            case 'ht_ft' :
                //暂时没有该玩法的赛事类型
                if (in_array($type, ['in_play_now', 'parlay'])) {
                    //$this->errorcode = EC_EVENTS_NO_HT_FT;
                    return ['total_page' => 0, 'result' => []];
                }
                $data = $this->htFt($type, $params['matches'], $params['order'], $params['page'], $params['date']);
                break;
            //总入球
            case 'total_goals' :
                //暂时没有该玩法的赛事类型
                if (in_array($type, ['in_play_now', 'parlay'])) {
                    //$this->errorcode = EC_EVENTS_NO_TOTAL_GOALS;
                    return ['total_page' => 0, 'result' => []];
                }
                $data = $this->totalGoals($type, $params['matches'], $params['order'], $params['page'], $params['date']);
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
     * @param string $order 排序
     * @param int $page 页码
     * @param string $date 日期
     * @return array
     */
    public function handicapOuOe1x2($type, $matches, $master, $order = 'time_asc', $page, $date = '') {
        //获取排序
        $orderBy = $this->getOrderBy($order);

        //获取公共where
        $where = $this->getCommonWhere($type, $matches, $date);
        if ($master == 'no') {
            unset($where['sfg_master']);
        }

        //坑逼；半场波胆玩法是单独一个盘口返回，取数据的时候要过滤掉
        $where['sfg_1h_correct_score'] = '';

        //获取查询字段
        $field = $this->getFieldHandicapOuOe1x2($type);

        $join = [
            ['sports_football_matches m', 'g.sfg_sfm_id=m.sfm_id', 'LEFT'],
            ['sports_football_schedules s', 'g.sfg_sfs_id=s.sfs_id', 'LEFT']
        ];

        //计算总数
        $total = Loader::model('SportsFootballGames')
            ->alias('g')
            ->where($where)
            ->join($join)
            ->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsFootballGames')
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
        Loader::model('Matches', 'football')->createFilter($type, '1x2-handicap-ou-oe', $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']    = $item['sfm_id'];
            $data[$key]['match_name']  = $item['sfm_name'];
            $data[$key]['game_id']     = $item['sfg_game_id'];
            $data[$key]['game_type']   = $item['sfg_game_type'];
            $data[$key]['schedule_id'] = $item['sfs_id'];
            $data[$key]['home_name']   = $item['sfs_home_name'];
            $data[$key]['guest_name']  = $item['sfs_guest_name'];
            $data[$key]['neutral']     = $item['sfs_neutral'];
            $data[$key]['in_play_now'] = $item['sfs_in_play_now'];
            $data[$key]['begin_time']  = $item['sfs_begin_time'];
            $data[$key]['is_master']   = Config::get('status.football_game_master_id')[$item['sfg_master']];
            if ($type == 'in_play_now') {
                $data[$key]['home_score']  = $item['sfs_home_score'];
                $data[$key]['guest_score'] = $item['sfs_guest_score'];
                $data[$key]['home_red']    = $item['sfs_home_red'];
                $data[$key]['guest_red']   = $item['sfs_guest_red'];
                $data[$key]['timer']       = transfer_time($item['sfs_retimeset']);
            }
            if ($type == 'parlay') {
                $data[$key]['parlay_min']  = $item['sfg_parlay_min'];
                $data[$key]['parlay_max']  = $item['sfg_parlay_max'];
                $data[$key]['odds']['ft1x2']       = !empty($item['sfg_parlay_ft_1x2']) ? json_decode($item['sfg_parlay_ft_1x2'], true) : [];
                $data[$key]['odds']['ft_handicap'] = !empty($item['sfg_parlay_ft_handicap']) ? json_decode($item['sfg_parlay_ft_handicap'], true) : [];
                $data[$key]['odds']['ft_ou']       = !empty($item['sfg_parlay_ft_ou']) ? json_decode($item['sfg_parlay_ft_ou'], true) : [];
                $data[$key]['odds']['ft_oe']       = !empty($item['sfg_parlay_ft_oe']) ? json_decode($item['sfg_parlay_ft_oe'], true) : [];
                $data[$key]['odds']['1h1x2']       = !empty($item['sfg_parlay_1h_1x2']) ? json_decode($item['sfg_parlay_1h_1x2'], true) : [];
                $data[$key]['odds']['1h_handicap'] = !empty($item['sfg_parlay_1h_handicap']) ? json_decode($item['sfg_parlay_1h_handicap'], true) : [];
                $data[$key]['odds']['1h_ou']       = !empty($item['sfg_parlay_1h_ou']) ? json_decode($item['sfg_parlay_1h_ou'], true) : [];
                $data[$key]['odds']['1h_oe']       = !empty($item['sfg_parlay_1h_oe']) ? json_decode($item['sfg_parlay_1h_oe'], true) : [];
            } else {
                $data[$key]['odds']['ft1x2']       = !empty($item['sfg_ft_1x2']) ? json_decode($item['sfg_ft_1x2'], true) : [];
                $data[$key]['odds']['ft_handicap'] = !empty($item['sfg_ft_handicap']) ? json_decode($item['sfg_ft_handicap'], true) : [];
                $data[$key]['odds']['ft_ou']       = !empty($item['sfg_ft_ou']) ? json_decode($item['sfg_ft_ou'], true) : [];
                $data[$key]['odds']['ft_oe']       = !empty($item['sfg_ft_oe']) ? json_decode($item['sfg_ft_oe'], true) : [];
                $data[$key]['odds']['1h1x2']       = !empty($item['sfg_1h_1x2']) ? json_decode($item['sfg_1h_1x2'], true) : [];
                $data[$key]['odds']['1h_handicap'] = !empty($item['sfg_1h_handicap']) ? json_decode($item['sfg_1h_handicap'], true) : [];
                $data[$key]['odds']['1h_ou']       = !empty($item['sfg_1h_ou']) ? json_decode($item['sfg_1h_ou'], true) : [];
                $data[$key]['odds']['1h_oe']       = !empty($item['sfg_1h_oe']) ? json_decode($item['sfg_1h_oe'], true) : [];
            }
        }

        return ['total_page' => ceil($total / $this->pageSizeWeb), 'result' => $this->formatReturnData($data)];
    }

    /**
     * 波胆玩法
     * @param $type  in_play_now 滚球, today 今日，early 早盘，parlay 综合过关
     * @param $matches 1,2,3 联赛id
     * @param string $ftOr1h 全场 ft OR 半场 1h
     * @param string $order 排序
     * @param int $page 页码
     * @param string $date 日期
     * @return array
     */
    public function correctScore($type, $matches, $ftOr1h, $order = 'time_asc', $page, $date = '') {
        //获取排序
        $orderBy = $this->getOrderBy($order);

        $field = [
            'sfs_id',
            'sfm_id',
            'sfm_name',
            'sfg_game_id',
            'sfg_game_type',
            'sfs_begin_time',
            'sfs_home_name',
            'sfs_guest_name',
            'sfs_neutral',
            'sfs_in_play_now'
        ];

        //获取公共where
        $where = $this->getCommonWhere($type, $matches, $date);
        if ($ftOr1h == 'ft') {
            $where['sfg_ft_correct_score'] = ['NEQ', ''];
            $field[] = 'sfg_ft_correct_score';
        } elseif ($ftOr1h == '1h') {
            $where['sfg_1h_correct_score'] = ['NEQ', ''];
            $where['sfg_master'] = Config::get('status.football_game_master')['no']; //半场波胆不是主盘口
            $field[] = 'sfg_1h_correct_score';
        }

        $join = [
            ['sports_football_matches m', 'g.sfg_sfm_id=m.sfm_id', 'LEFT'],
            ['sports_football_schedules s', 'g.sfg_sfs_id=s.sfs_id', 'LEFT']
        ];

        //计算总数
        $total = Loader::model('SportsFootballGames')->alias('g')->where($where)->join($join)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsFootballGames')
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
        if ($ftOr1h == 'ft') {
            $filterPlayType = 'correct_score';
        } elseif ($ftOr1h == '1h') {
            $filterPlayType = '1h_correct_score';
        } else {
            $filterPlayType = 'correct_score';
        }
        Loader::model('Matches', 'football')->createFilter($type, $filterPlayType, $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']    = $item['sfm_id'];
            $data[$key]['match_name']  = $item['sfm_name'];
            $data[$key]['game_id']     = $item['sfg_game_id'];
            $data[$key]['game_type']   = $item['sfg_game_type'];
            $data[$key]['schedule_id'] = $item['sfs_id'];
            $data[$key]['home_name']   = $item['sfs_home_name'];
            $data[$key]['guest_name']  = $item['sfs_guest_name'];
            $data[$key]['neutral']     = $item['sfs_neutral'];
            $data[$key]['in_play_now'] = $item['sfs_in_play_now'];
            $data[$key]['begin_time']  = $item['sfs_begin_time'];
            if ($ftOr1h == 'ft') {
                $data[$key]['odds']['ft_correct_score'] = !empty($item['sfg_ft_correct_score']) ? json_decode($item['sfg_ft_correct_score'], true) : [];
            } elseif ($ftOr1h == '1h') {
                $data[$key]['odds']['1h_correct_score'] = !empty($item['sfg_1h_correct_score']) ? json_decode($item['sfg_1h_correct_score'], true) : [];
            }
        }

        return ['total_page' => ceil($total / $this->pageSizeWeb), 'result' => $this->formatReturnData($data)];
    }

    /**
     * 半场/全场
     * @param $type  in-play-now 滚球, today 今日，early 早盘，parlay 综合过关
     * @param $matches 1,2,3 联赛id
     * @param string $order 排序
     * @param int $page 页码
     * @param string $date 日期
     * @return array
     */
    public function htFt($type, $matches, $order = 'time_asc', $page, $date = '') {
        //获取排序
        $orderBy = $this->getOrderBy($order);

        //获取公共where
        $where = $this->getCommonWhere($type, $matches, $date);
        $where['sfg_ht_ft'] = ['NEQ', ''];

        $field = [
            'sfs_id',
            'sfm_id',
            'sfm_name',
            'sfg_game_id',
            'sfg_game_type',
            'sfg_ht_ft',
            'sfs_begin_time',
            'sfs_home_name',
            'sfs_guest_name',
            'sfs_neutral',
            'sfs_in_play_now'
        ];

        $join = [
            ['sports_football_matches m', 'g.sfg_sfm_id=m.sfm_id', 'LEFT'],
            ['sports_football_schedules s', 'g.sfg_sfs_id=s.sfs_id', 'LEFT']
        ];

        //计算总数
        $total = Loader::model('SportsFootballGames')->alias('g')->where($where)->join($join)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsFootballGames')
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
        Loader::model('Matches', 'football')->createFilter($type, 'ht_ft', $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']    = $item['sfm_id'];
            $data[$key]['match_name']  = $item['sfm_name'];
            $data[$key]['game_id']     = $item['sfg_game_id'];
            $data[$key]['game_type']   = $item['sfg_game_type'];
            $data[$key]['schedule_id'] = $item['sfs_id'];
            $data[$key]['home_name']   = $item['sfs_home_name'];
            $data[$key]['guest_name']  = $item['sfs_guest_name'];
            $data[$key]['neutral']     = $item['sfs_neutral'];
            $data[$key]['in_play_now'] = $item['sfs_in_play_now'];
            $data[$key]['begin_time']  = $item['sfs_begin_time'];
            $data[$key]['odds']['ht_ft']       = !empty($item['sfg_ht_ft']) ? json_decode($item['sfg_ht_ft'], true) : [];
        }

        return ['total_page' => ceil($total / $this->pageSizeWeb), 'result' => $this->formatReturnData($data)];
    }

    /**
     * 总入球
     * @param $type  in-play-now 滚球, today 今日，early 早盘，parlay 综合过关
     * @param $matches 1,2,3 联赛id
     * @param string $order 排序
     * @param int $page 页码
     * @param string $date 日期
     * @return array
     */
    public function totalGoals($type, $matches, $order = 'time_asc', $page, $date) {
        //获取排序
        $orderBy = $this->getOrderBy($order);

        //获取公共where
        $where = $this->getCommonWhere($type, $matches, $date);
        $where['sfg_ft_total_goals|sfg_1h_total_goals'] = ['NEQ', ''];

        $field = [
            'sfs_id',
            'sfm_id',
            'sfm_name',
            'sfg_game_id',
            'sfg_game_type',
            'sfg_ft_total_goals',
            'sfg_1h_total_goals',
            'sfs_begin_time',
            'sfs_home_name',
            'sfs_guest_name',
            'sfs_neutral',
            'sfs_in_play_now'
        ];

        $join = [
            ['sports_football_matches m', 'g.sfg_sfm_id=m.sfm_id', 'LEFT'],
            ['sports_football_schedules s', 'g.sfg_sfs_id=s.sfs_id', 'LEFT']
        ];

        //计算总数
        $total = Loader::model('SportsFootballGames')->alias('g')->where($where)->join($join)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsFootballGames')
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
        Loader::model('Matches', 'football')->createFilter($type, 'total_goals', $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']    = $item['sfm_id'];
            $data[$key]['match_name']  = $item['sfm_name'];
            $data[$key]['game_id']     = $item['sfg_game_id'];
            $data[$key]['game_type']   = $item['sfg_game_type'];
            $data[$key]['schedule_id'] = $item['sfs_id'];
            $data[$key]['home_name']   = $item['sfs_home_name'];
            $data[$key]['guest_name']  = $item['sfs_guest_name'];
            $data[$key]['neutral']     = $item['sfs_neutral'];
            $data[$key]['in_play_now'] = $item['sfs_in_play_now'];
            $data[$key]['begin_time']  = $item['sfs_begin_time'];
            $data[$key]['odds']['ft_total_goals'] = !empty($item['sfg_ft_total_goals']) ? json_decode($item['sfg_ft_total_goals'], true) : [];
            $data[$key]['odds']['1h_total_goals'] = !empty($item['sfg_1h_total_goals']) ? json_decode($item['sfg_1h_total_goals'], true) : [];
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
                $orderBy = ['sfo_end_time' => 'asc', 'sfm_sort' => 'asc'];
                break;
            case 'time_desc' :
                $orderBy = ['sfo_end_time' => 'desc', 'sfm_sort' => 'asc'];
                break;
            case 'match_asc' :
                $orderBy = ['sfm_sort' => 'asc', 'sfo_end_time' => 'asc'];
                break;
            case 'match_desc' :
                $orderBy = ['sfm_sort' => 'desc', 'sfo_end_time' => 'asc'];
                break;
            default :
                $orderBy = ['sfo_end_time' => 'asc', 'sfm_sort' => 'asc'];
                break;
        }

        //where查询条件
        $where = [
            'sfo_is_show' => Config::get('status.football_outright_is_show')['yes'],
            'sfo_end_time' => ['EGT', date('Y-m-d H:i:s')]
        ];
        //联赛筛选
        if ($matches) {
            $where['sfo_sfm_id'] = ['in', $matches];
        }

        $field = [
            'sfo_game_id',
            'sfm_name',
            'sfm_id',
            'sfo_game_type',
            'sfo_odds',
            'sfo_end_time',
        ];

        $join = [
            ['sports_football_matches m', 'o.sfo_sfm_id=m.sfm_id', 'LEFT'],
        ];

        //计算总数
        $total = Loader::model('SportsFootballOutright')->alias('o')->where($where)->join($join)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $ret = Loader::model('SportsFootballOutright')
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
        Loader::model('Matches', 'football')->createFilter($type, 'outright', $where, $join);

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $data[$key]['match_id']   = $item['sfm_id'];
            $data[$key]['match_name'] = $item['sfm_name'];
            $data[$key]['game_id']    = $item['sfo_game_id'];
            $data[$key]['game_type']  = $item['sfo_game_type'];
            $data[$key]['end_time']   = $item['sfo_end_time'];
            $data[$key]['outright']   = !empty($item['sfo_odds']) ? json_decode($item['sfo_odds'], true) : [];
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
            'sfs_id',
            'sfm_id',
            'sfm_name',
            'sfg_game_id',
            'sfg_game_type',
            'sfs_begin_time',
            'sfs_home_name',
            'sfs_guest_name',
            'sfs_neutral',
            'sfg_master',
            'sfs_in_play_now',
        ];

        //滚球字段
        if ($type == 'in_play_now') {
            $field = array_merge($field, [
                'sfs_home_score',
                'sfs_guest_score',
                'sfs_home_red',
                'sfs_guest_red',
                'sfs_timer',
                'sfs_retimeset',
            ]);
        }

        //综合过关字段
        if ($type == 'parlay') {
            $field = array_merge($field, [
                'sfg_parlay_ft_1x2',
                'sfg_parlay_ft_handicap',
                'sfg_parlay_ft_ou',
                'sfg_parlay_ft_oe',
                'sfg_parlay_1h_1x2',
                'sfg_parlay_1h_handicap',
                'sfg_parlay_1h_ou',
                'sfg_parlay_1h_oe',
                'sfg_parlay_min',
                'sfg_parlay_max',
            ]);
        } else {
            $field = array_merge($field, [
                'sfg_ft_1x2',
                'sfg_ft_handicap',
                'sfg_ft_ou',
                'sfg_ft_oe',
                'sfg_1h_1x2',
                'sfg_1h_handicap',
                'sfg_1h_ou',
                'sfg_1h_oe',
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
        $commonOrder = ['sfs_begin_time' => 'asc', 'sfs_id' => 'asc', 'sfg_game_id' => 'asc'];
        switch($order) {
            case 'time_asc' :
                $orderBy = $commonOrder;
                break;
            case 'time_desc' :
                $orderBy = ['sfs_begin_time' => 'desc', 'sfs_id' => 'asc', 'sfg_game_id' => 'asc'];
                break;
            case 'match_asc' :
                $orderBy = ['sfm_sort' => 'asc', 'sfm_id' => 'desc'];
                $orderBy = array_merge($orderBy, $commonOrder);
                break;
            case 'match_desc' :
                $orderBy = ['sfm_sort' => 'desc', 'sfm_id' => 'desc'];
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

        //不筛选出所有盘口，只显示一条主盘口
        $where['sfg_master']       = Config::get('status.football_game_master')['yes'];
        $where['sfg_is_show']      = Config::get('status.football_game_is_show')['yes'];
        $where['sfs_check_status'] = Config::get('status.football_schedule_check_status')['normal'];

        //赛事类型的查询条件
        $eventTypeWhere = $this->getEventTypeWhere($eventType, $date);
        if ($eventTypeWhere) {
            $where = array_merge($where, $eventTypeWhere);
        }

        //联赛筛选
        if ($matches) {
            $where['sfg_sfm_id'] = ['in', $matches];
        }

        return $where;
    }

    /**
     * 返回赛事类型的查询条件
     * @param $eventType
     * @param string $date
     * @return bool
     */
    public function getEventTypeWhere($eventType, $date = '') {
        switch($eventType) {
            case 'in_play_now' :
                $where['sfs_in_play_now'] = ['EQ', Config::get('status.football_schedule_in_play_now')['yes']];
                $where['sfs_status'] = ['IN', [
                    Config::get('status.football_schedule_status')['half_time'],
                    Config::get('status.football_schedule_status')['1h_in_game'],
                    Config::get('status.football_schedule_status')['2h_in_game'],
                ]];
                break;
            case 'today' :
                $sTime = date('Y-m-d H:i:s');
                $eTime = date('Y-m-d') . ' 23:59:59';
                $where['sfs_status'] = ['EQ', Config::get('status.football_schedule_status')['not_begin']];
                $where['sfs_begin_time'] = ['BETWEEN', [$sTime, $eTime]];
                break;
            case 'early' :
                if ($date) {
                    $sTime = $date . ' 00:00:00';
                    $eTime = $date . ' 23:59:59';
                    $where['sfs_begin_time'] = ['BETWEEN', [$sTime, $eTime]];
                } else {
                    $sTime = date('Y-m-d', strtotime('+1 day')) . ' 00:00:00';
                    $where['sfs_begin_time'] = ['EGT', $sTime];
                }
                break;
            case 'parlay' :
                $where['sfg_parlay'] = ['EQ', Config::get('status.football_game_parlay')['yes']];
                if ($date) {
                    $sTime = $date . ' 00:00:00';
                    $eTime = $date . ' 23:59:59';
                    $where['sfs_begin_time'] = ['BETWEEN', [$sTime, $eTime]];
                } else {
                    $sTime = date('Y-m-d H:i:s');
                    $where['sfs_begin_time'] = ['EGT', $sTime];
                }
                break;
            default:
                return false;
                break;
        }
        return $where;
    }

    /**
     * 计算足球的滚球，今日赛事，早盘，综合过关的赛事数量
     * @param $eventsTypeArr
     * @param bool $isCache 是否走缓存
     * @return mixed
     */
    public function countEventsTypeNum($eventsTypeArr = [], $isCache = true) {
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'football_count_events_type_num';
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }

        $total = 0;

        //滚球赛事数量
        $where = $this->getCommonWhere('in_play_now');
        $join = [
            ['sports_football_games g', 's.sfs_id=g.sfg_sfs_id', 'LEFT']
        ];
        $eventsTypeArr['in_play_now'] = Loader::model('SportsFootballSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['in_play_now'];

        //今日赛事数量
        $where = $this->getCommonWhere('today');
        $join = [
            ['sports_football_games g', 's.sfs_id=g.sfg_sfs_id', 'LEFT']
        ];
        $eventsTypeArr['today'] = Loader::model('SportsFootballSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['today'];

        //早盘赛事数量
        $where = $this->getCommonWhere('early');
        $join = [
            ['sports_football_games g', 's.sfs_id=g.sfg_sfs_id', 'LEFT']
        ];
        $eventsTypeArr['early'] = Loader::model('SportsFootballSchedules')
            ->alias('s')
            ->where($where)
            ->join($join)
            ->count();
        $total += $eventsTypeArr['early'];

        //综合过关赛事数量
        $where = $this->getCommonWhere('parlay');
        $join = [
            ['sports_football_games g', 's.sfs_id=g.sfg_sfs_id', 'LEFT']
        ];
        $eventsTypeArr['parlay'] = Loader::model('SportsFootballSchedules')
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
        if (!isset(Config::get('common.play_type')['football'][$params['play_type']])) {
            $this->errorcode = EC_EVENTS_PLAY_TYPE_ERROR;
            return false;
        }
        if ($params['event_type'] == 'parlay') {
            $playType = [
                //综合过关
                'parlay_ft1x2'       => 'sfg_parlay_ft_1x2',
                'parlay_ft_handicap' => 'sfg_parlay_ft_handicap',
                'parlay_ft_ou'       => 'sfg_parlay_ft_ou',
                'parlay_ft_oe'       => 'sfg_parlay_ft_oe',
                'parlay_1h1x2'       => 'sfg_parlay_1h_1x2',
                'parlay_1h_handicap' => 'sfg_parlay_1h_handicap',
                'parlay_1h_ou'       => 'sfg_parlay_1h_ou',
                'parlay_1h_oe'       => 'sfg_parlay_1h_oe',
            ];
        } else {
            $playType = [
                //全场
                'ft1x2'              => 'sfg_ft_1x2',
                'ft_handicap'        => 'sfg_ft_handicap',
                'ft_ou'              => 'sfg_ft_ou',
                'ft_oe'              => 'sfg_ft_oe',
                'ft_correct_score'   => 'sfg_ft_correct_score',
                'ft_total_goals'     => 'sfg_ft_total_goals',
                //半场
                '1h1x2'              => 'sfg_1h_1x2',
                '1h_handicap'        => 'sfg_1h_handicap',
                '1h_ou'              => 'sfg_1h_ou',
                '1h_oe'              => 'sfg_1h_oe',
                '1h_correct_score'   => 'sfg_1h_correct_score',
                '1h_total_goals'     => 'sfg_1h_total_goals',
                //半全场
                'ht_ft'              => 'sfg_ht_ft',
                'outright'           => 'sfo_odds',
            ];
        }

        if ($params['play_type'] == 'outright') {
            if (!isset($playType[$params['play_type']])) {
                $this->errorcode = EC_EVENTS_PLAY_TYPE_ERROR;
                return false;
            }
            $field[] = $playTypeField = $playType[$params['play_type']];

            //获取盘口信息
            $where = ['sfo_game_id' => $params['game_id']];
            $gameInfo = Loader::model('SportsFootballOutright')->where($where)->field($field)->find();
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
            $field = ['sfg_sfm_id', 'sfg_home_id', 'sfg_guest_id', 'sfg_game_type', 'sfg_event_type'];
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
            $where = ['sfg_game_id' => $params['game_id']];
            $gameInfo = Loader::model('SportsFootballGames')->where($where)->field($field)->find();
            is_object($gameInfo) && $gameInfo = $gameInfo->toArray();
            if (!$gameInfo || empty($gameInfo[$playTypeField])) {
                $this->errorcode = EC_GAME_INFO_EMPTY;
                return false;
            }

            //判断是否从今日变到滚球
            if ($params['event_type'] == 'today' && Config::get('status.football_game_event_type_id')[$gameInfo['sfg_event_type']] != $params['event_type']) {
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
                if ($params['play_type'] == 'ft_handicap') {
                    $data['strong'] = $playTypeInfo['strong'];
                }
                if ($params['play_type'] == '1h_handicap') {
                    $data['strong'] = $playTypeInfo['hstrong'];
                }
            }

            //返回详细信息
            if ($detailInfo) {
                //获取球队信息
                $teamInfo = Loader::model('Teams', 'football')->getInfoById($gameInfo['sfg_home_id']);
                $data['home_name'] = $teamInfo['sft_name'];
                $teamInfo = Loader::model('Teams', 'football')->getInfoById($gameInfo['sfg_guest_id']);
                $data['guest_name'] = $teamInfo['sft_name'];

                //盘口类型
                $data['game_type'] = $gameInfo['sfg_game_type'];

                //获取联赛信息
                $matchInfo = Loader::model('Matches', 'football')->getInfoById($gameInfo['sfg_sfm_id']);
                $data['match_name'] = $matchInfo['sfm_name'];

                //玩法名称
                $data['play_type_name'] = Config::get('common.play_type')['football'][$params['play_type']];

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