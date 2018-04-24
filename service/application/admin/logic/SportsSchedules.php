<?php
/**
 * 比赛管理业务逻辑
 * @createTime 2017/4/17 14:29
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;
use curl\Curlrequest;

class SportsSchedules extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 每页显示数量
     * @var int
     */
    public $pageSize = 20;

    /**
     * 获取对阵列表
     * @param $params
     * @return array
     */
    public function getList($params) {
        switch($params['sport_type']) {
            //足球
            case 'football' :
                return $this->getFootballList($params);
            case 'basketball' :
                return $this->getBasketballList($params);
            case 'tennis' :
                return $this->getTennisList($params);
            default :
                return ['total_count' => 0, 'list' => []];
        }
    }

    /**
     * 获取足球对阵列表
     * @param $params
     * @return array
     */
    protected function getFootballList($params) {
        $where = [];
        !$params['page']           && $params['page'] = 1;
        !$params['page_size']      && $params['page_size'] = $this->pageSize;
        $params['schedule_id']     && $where['sfs_id'] = $params['schedule_id'];
        $params['clearing_status'] && $where['sfs_clearing'] = Config::get('status.football_schedule_clearing')[$params['clearing_status']];
        $params['check_status']    && $where['sfs_check_status'] = Config::get('status.football_schedule_check_status')[$params['check_status']];
        $params['in_play_now']     && $where['sfs_in_play_now'] = Config::get('status.football_schedule_in_play_now')[$params['in_play_now']];
        $params['team_name']       && $where['sfs_home_name|sfs_guest_name'] = ['LIKE', '%' . $params['team_name'] . '%'];

        if ($params['status'] == 'in_game') {
            $where['sfs_status'] = ['IN', [
                Config::get('status.football_schedule_status')['1h_in_game'],
                Config::get('status.football_schedule_status')['half_time'],
                Config::get('status.football_schedule_status')['2h_in_game'],
            ]];
        } else {
            $params['status'] && $where['sfs_status'] = Config::get('status.football_schedule_status')[$params['status']];
        }

        if ($params['start_time']) {
            if ($params['end_time']) {
                $startTime = $params['start_time'] . ' 00:00:00';
                $endTime   = $params['end_time'] . ' 23:59:59';
            }else{
                $startTime = $params['start_time'] . ' 00:00:00';
                $endTime   = $params['start_time'] . ' 23:59:59';
            }
            $where['sfs_begin_time'] = ['between', [$startTime, $endTime]];
        }

        //计算总数
        $total = Loader::model('SportsFootballSchedules')
            ->alias('s')
            ->where($where)
            ->count();
        if (!$total) {
            return ['total_count' => 0, 'list' => []];
        }

        //获取数据
        $orderBy = ['sfs_clearing' => 'asc', 'sfs_begin_time' => 'asc', 'sfs_id' => 'desc'];
        $ret = Loader::model('SportsFootballSchedules')
            ->alias('s')
            ->where($where)
            ->order($orderBy)
            ->page($params['page'], $params['page_size'])
            ->select();
        if (!$ret) {
            return ['total_count' => 0, 'list' => []];
        }

        //获取联赛名
        $matchIds = array_unique(extract_array(collection($ret)->toArray(), 'sfs_sfm_id'));
        $condition['sfm_id'] = array('in', $matchIds);
        $matchNames = Loader::model('SportsFootballMatches')->where($condition)->column('sfm_id, sfm_name');

        //球类信息
        $sportTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName($params['sport_type']);

        //数据处理
        $data = [];
        $resultsLogic = Loader::model('Results', 'football');
        foreach($ret as $key => $item) {
            $result = $resultsLogic->getResultByScheduleId($item->sfs_id);
            $countOrders = $this->countOrderInfoByScheduleId($item->sfs_id, $sportTypeInfo['st_id']);

            if (isset($matchNames[$item->sfs_sfm_id])) {
                $data[$key]['match_name'] = $matchNames[$item->sfs_sfm_id];
            }
            $data[$key]['schedule_id']      = $item->sfs_id;
            $data[$key]['new_schedule_id']  = $item->sfs_new_id;
            $data[$key]['home_name']        = $item->sfs_home_name;
            $data[$key]['guest_name']       = $item->sfs_guest_name;
            $data[$key]['begin_time']       = $item->sfs_begin_time;
            $data[$key]['home_red']         = $item->sfs_home_red;
            $data[$key]['guest_red']        = $item->sfs_guest_red;
            $data[$key]['game_type']        = $item->sfs_game_type;
            $data[$key]['home_score']       = isset($result['sfr_home_score']) ? $result['sfr_home_score'] : '';
            $data[$key]['guest_score']      = isset($result['sfr_guest_score']) ? $result['sfr_guest_score'] : '';
            $data[$key]['timer']            = $item->sfs_timer;
            $data[$key]['status']           = Config::get('status.football_schedule_status_id')[$item->sfs_status];
            $data[$key]['clearing_status']  = Config::get('status.football_schedule_clearing_id')[$item->sfs_clearing];
            $data[$key]['check_status']     = Config::get('status.football_schedule_check_status_id')[$item->sfs_check_status];
            $data[$key]['total_orders']     = $countOrders['total_orders'];
            $data[$key]['total_bet_amount'] = $countOrders['total_bet_amount'];
            $data[$key]['total_bonus']      = $countOrders['total_bonus'];
            $data[$key]['sport_id']         = $sportTypeInfo['st_id'];
            unset($key, $item);
        }

        return [
            'total_count' => $total,
            'list' => $data
        ];
    }

    /**
     * 获取篮球对阵列表
     * @param $params
     * @return array
     */
    protected function getBasketballList($params) {
        $where = [];
        !$params['page']           && $params['page'] = 1;
        !$params['page_size']      && $params['page_size'] = $this->pageSize;
        $params['schedule_id']     && $where['sbs_id'] = $params['schedule_id'];
        $params['clearing_status'] && $where['sbs_clearing'] = Config::get('status.basketball_schedule_clearing')[$params['clearing_status']];
        $params['check_status']    && $where['sbs_check_status'] = Config::get('status.basketball_schedule_check_status')[$params['check_status']];
        $params['in_play_now']     && $where['sbs_in_play_now'] = Config::get('status.basketball_schedule_in_play_now')[$params['in_play_now']];
        $params['status']          && $where['sbs_status'] = Config::get('status.basketball_schedule_status')[$params['status']];
        $params['team_name']       && $where['sbs_home_name|sbs_guest_name'] = ['LIKE', '%' . $params['team_name'] . '%'];

        if ($params['start_time']) {
            if ($params['end_time']) {
                $startTime = $params['start_time'] . ' 00:00:00';
                $endTime   = $params['end_time'] . ' 23:59:59';
            }else{
                $startTime = $params['start_time'] . ' 00:00:00';
                $endTime   = $params['start_time'] . ' 23:59:59';
            }
            $where['sbs_begin_time'] = ['between', [$startTime, $endTime]];
        }

        //计算总数
        $total = Loader::model('SportsBasketballSchedules')
            ->alias('s')
            ->where($where)
            ->count();
        if (!$total) {
            return ['total_count' => 0, 'list' => []];
        }

        $orderBy = ['sbs_clearing' => 'asc', 'sbs_begin_time' => 'asc', 'sbs_id' => 'desc'];
        $ret = Loader::model('SportsBasketballSchedules')
            ->alias('s')
            ->where($where)
            ->order($orderBy)
            ->page($params['page'], $params['page_size'])
            ->select();
        if (!$ret) {
            return ['total_count' => 0, 'list' => []];
        }

        //获取联赛id
        $matchIds = array_unique(extract_array(collection($ret)->toArray(), 'sbs_sbm_id'));
        $condition['sbm_id'] = array('in', $matchIds);
        $matchNames = Loader::model('SportsBasketballMatches')->where($condition)->column('sbm_id, sbm_name');

        //球类信息
        $sportTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName($params['sport_type']);

        //数据处理
        $data = [];
        $resultsLogic = Loader::model('Results', 'basketball');
        foreach($ret as $key => $item) {
            $countOrders = $this->countOrderInfoByScheduleId($item->sbs_id, $sportTypeInfo['st_id']);
            $result = $resultsLogic->getInfoByScheduleId($item->sbs_id);
            if (isset($matchNames[$item->sbs_sbm_id])) {
                $data[$key]['match_name'] = $matchNames[$item->sbs_sbm_id];
            }
            $data[$key]['schedule_id']      = $item->sbs_id;
            $data[$key]['new_schedule_id']  = $item->sbs_new_id;
            $data[$key]['home_name']        = $item->sbs_home_name;
            $data[$key]['guest_name']       = $item->sbs_guest_name;
            $data[$key]['begin_time']       = $item->sbs_begin_time;
            $data[$key]['home_score']       = isset($result['sbr_home_score']) ? $result['sbr_home_score'] : '';
            $data[$key]['guest_score']      = isset($result['sbr_guest_score']) ? $result['sbr_guest_score'] : '';
            $data[$key]['timer']            = $item->sbs_timer;
            $data[$key]['quarter']          = $item->sbs_quarter;
            $data[$key]['status']           = Config::get('status.basketball_schedule_status_id')[$item->sbs_status];
            $data[$key]['clearing_status']  = Config::get('status.basketball_schedule_clearing_id')[$item->sbs_clearing];
            $data[$key]['check_status']     = Config::get('status.basketball_schedule_check_status_id')[$item->sbs_check_status];
            $data[$key]['total_orders']     = $countOrders['total_orders'];
            $data[$key]['total_bet_amount'] = $countOrders['total_bet_amount'];
            $data[$key]['total_bonus']      = $countOrders['total_bonus'];
            $data[$key]['sport_id']         = $sportTypeInfo['st_id'];
            unset($key, $item);
        }

        return [
            'total_count' => $total,
            'list' => $data
        ];
    }

    /**
     * 获取网球对阵列表
     * @param $params
     * @return array
     */
    protected function getTennisList($params) {
        $where = [];
        !$params['page']           && $params['page'] = 1;
        !$params['page_size']      && $params['page_size'] = $this->pageSize;
        $params['schedule_id']     && $where['sts_id'] = $params['schedule_id'];
        $params['clearing_status'] && $where['sts_clearing'] = Config::get('status.tennis_schedule_clearing')[$params['clearing_status']];
        $params['check_status']    && $where['sts_check_status'] = Config::get('status.tennis_schedule_check_status')[$params['check_status']];
        $params['in_play_now']     && $where['sts_in_play_now'] = Config::get('status.tennis_schedule_in_play_now')[$params['in_play_now']];
        $params['status']          && $where['sts_status'] = Config::get('status.tennis_schedule_status')[$params['status']];
        $params['team_name']       && $where['sts_home_name|sts_guest_name'] = ['LIKE', '%' . $params['team_name'] . '%'];

        if ($params['start_time']) {
            if ($params['end_time']) {
                $startTime = $params['start_time'] . ' 00:00:00';
                $endTime   = $params['end_time'] . ' 23:59:59';
            } else {
                $startTime = $params['start_time'] . ' 00:00:00';
                $endTime   = $params['start_time'] . ' 23:59:59';
            }
            $where['sts_begin_time'] = ['between', [$startTime, $endTime]];
        }

        //计算总数_sports_tennis_schedules
        $total = Loader::model('SportsTennisSchedules')
            ->alias('s')
            ->where($where)
            ->count();
        if (!$total) {
            return ['total_count' => 0, 'list' => []];
        }

        $orderBy = ['sts_clearing' => 'asc', 'sts_begin_time' => 'asc', 'sts_id' => 'desc'];
        $ret = Loader::model('SportsTennisSchedules')
            ->alias('s')
            ->where($where)
            ->order($orderBy)
            ->page($params['page'], $params['page_size'])
            ->select();
        if (!$ret) {
            return ['total_count' => 0, 'list' => []];
        }

        //获取联赛id
        $matchIds = array_unique(extract_array(collection($ret)->toArray(), 'sts_stm_id'));
        $condition['stm_id'] = array('in', $matchIds);
        $matchNames = Loader::model('SportsTennisMatches')->where($condition)->column('stm_id, stm_name');

        //球类信息
        $sportTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName($params['sport_type']);

        //数据处理
        $data = [];
        $resultsLogic = Loader::model('Results', 'tennis');
        foreach($ret as $key => $item) {
            $countOrders = $this->countOrderInfoByScheduleId($item->sts_id, $sportTypeInfo['st_id']);
            $result = $resultsLogic->getInfoByScheduleId($item->sts_id);
            if (isset($matchNames[$item->sts_stm_id])) {
                $data[$key]['match_name'] = $matchNames[$item->sts_stm_id];
            }
            $data[$key]['schedule_id']      = $item->sts_id;
            $data[$key]['new_schedule_id']  = $item->sts_new_id;
            $data[$key]['home_name']        = $item->sts_home_name;
            $data[$key]['guest_name']       = $item->sts_guest_name;
            $data[$key]['begin_time']       = $item->sts_begin_time;
            $data[$key]['home_score']       = isset($result['str_home_score']) ? $result['str_home_score'] : '';
            $data[$key]['guest_score']      = isset($result['str_guest_score']) ? $result['str_guest_score'] : '';
            $data[$key]['timer']            = $item->sts_timer;
            $data[$key]['status']           = Config::get('status.tennis_schedule_status_id')[$item->sts_status];
            $data[$key]['clearing_status']  = Config::get('status.tennis_schedule_clearing_id')[$item->sts_clearing];
            $data[$key]['check_status']     = Config::get('status.tennis_schedule_check_status_id')[$item->sts_check_status];
            $data[$key]['total_orders']     = $countOrders['total_orders'];
            $data[$key]['total_bet_amount'] = $countOrders['total_bet_amount'];
            $data[$key]['total_bonus']      = $countOrders['total_bonus'];
            $data[$key]['sport_id']         = $sportTypeInfo['st_id'];
            unset($key, $item);
        }

        return [
            'total_count' => $total,
            'list' => $data
        ];
    }

    /**
     * 统计订单信息
     * @param $scheduleId
     * @param $sportId
     * @return mixed
     */
    protected function countOrderInfoByScheduleId($scheduleId, $sportId) {
        $fields = [
            'sum(so_bet_amount)' => 'total_bet_amount',
            'sum(so_bonus)'      => 'total_bonus',
            'count(so_id)'       => 'total_orders'
        ];

        $condition['so_st_id'] = $sportId;
        $condition[] = ['exp', 'FIND_IN_SET(' . $scheduleId . ',so_source_ids)'];
        $result = Loader::model('SportsOrders')->where($condition)->field($fields)->find();
        return !empty($result) ? $result->toArray() : ['total_bet_amount' => 0, 'total_bonus' => 0, 'total_orders' => 0];
    }

    /**
     * 撤销这场比赛的订单；只是做个标记，然后程序自动去跑
     * @param $sportType 球类
     * @param $scheduleId 比赛ID
     * @param $remark 撤单原因
     * @return mixed
     */
    public function cancel($sportType, $scheduleId, $remark) {
        switch($sportType) {
            case 'football':
                //判断对阵是否有锁
                $orderLockKey = Config::get('cache_option.prefix')['sports_football_schedule_lock'] . $scheduleId;
                if (Cache::get($orderLockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }

                //判断可以撤单的状态
                $info = Loader::model('Schedules', $sportType)->getInfoById($scheduleId, 'sfs_clearing,sfs_check_status');
                if ($info['sfs_clearing'] != Config::get('status.football_schedule_clearing')['no']) {
                    $this->errorcode = EC_AD_SCHEDULE_CLEARED;
                    return false;
                }
                if (!in_array($info['sfs_check_status'], [
                    Config::get('status.football_schedule_check_status')['normal'],
                    Config::get('status.football_schedule_check_status')['halt_sales'],
                    Config::get('status.football_schedule_check_status')['wait_hand_clearing']
                ])) {
                    $this->errorcode = EC_AD_SCHEDULE_STATUS_NOT_AVAILABLE;
                    return false;
                }

                $status = Config::get('status.football_schedule_check_status')['wait_cancel'];
                return Loader::model('Schedules', $sportType)->updateCheckStatusById($scheduleId, $status, $remark);
            case 'basketball':
                //判断对阵是否有锁
                $orderLockKey = Config::get('cache_option.prefix')['sports_basketball_schedule_lock'] . $scheduleId;
                if (Cache::get($orderLockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }

                //判断可以撤单的状态
                $info = Loader::model('Schedules', $sportType)->getInfoById($scheduleId, 'sbs_clearing,sbs_check_status');
                if ($info['sbs_clearing'] != Config::get('status.basketball_schedule_clearing')['no']) {
                    $this->errorcode = EC_AD_SCHEDULE_CLEARED;
                    return false;
                }
                if (!in_array($info['sbs_check_status'], [
                    Config::get('status.basketball_schedule_check_status')['normal'],
                    Config::get('status.basketball_schedule_check_status')['halt_sales'],
                    Config::get('status.basketball_schedule_check_status')['wait_hand_clearing']
                ])) {
                    $this->errorcode = EC_AD_SCHEDULE_STATUS_NOT_AVAILABLE;
                    return false;
                }

                $status = Config::get('status.basketball_schedule_check_status')['wait_cancel'];
                return Loader::model('Schedules', $sportType)->updateCheckStatusById($scheduleId, $status, $remark);
            case 'tennis':
                //判断对阵是否有锁
                $orderLockKey = Config::get('cache_option.prefix')['sports_tennis_schedule_lock'] . $scheduleId;
                if (Cache::get($orderLockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }

                //判断可以撤单的状态
                $info = Loader::model('Schedules', $sportType)->getInfoById($scheduleId, 'sts_clearing,sts_check_status');
                if ($info['sts_clearing'] != Config::get('status.tennis_schedule_clearing')['no']) {
                    $this->errorcode = EC_AD_SCHEDULE_CLEARED;
                    return false;
                }
                if (!in_array($info['sts_check_status'], [
                    Config::get('status.tennis_schedule_check_status')['normal'],
                    Config::get('status.tennis_schedule_check_status')['halt_sales'],
                    Config::get('status.tennis_schedule_check_status')['wait_hand_clearing']
                ])) {
                    $this->errorcode = EC_AD_SCHEDULE_STATUS_NOT_AVAILABLE;
                    return false;
                }

                $status = Config::get('status.tennis_schedule_check_status')['wait_cancel'];
                return Loader::model('Schedules', $sportType)->updateCheckStatusById($scheduleId, $status, $remark);
        }
    }

    /**
     * 撤销结算；只是做个标记，然后程序自动去跑
     * @param $sportType
     * @param $scheduleId
     * @return mixed
     */
    public function cancelClearing($sportType, $scheduleId) {
        switch($sportType) {
            case 'football':
                //判断对阵是否有锁
                $orderLockKey = Config::get('cache_option.prefix')['sports_football_schedule_lock'] . $scheduleId;
                if (Cache::get($orderLockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }

                //判断可以操作的状态
                $info = Loader::model('Schedules', $sportType)->getInfoById($scheduleId, 'sfs_clearing,sfs_check_status');
                /** 未结算的比赛也可以撤销结算：不然有一场综合过关没结算，其他已结算的单子都不能及时撤销结算
                if ($info['sfs_clearing'] != Config::get('status.football_schedule_clearing')['yes']) {
                    $this->errorcode = EC_AD_SCHEDULE_UNCLEARED;
                    return false;
                }**/
                if (!in_array($info['sfs_check_status'], [
                    Config::get('status.tennis_schedule_check_status')['normal'],
                    Config::get('status.tennis_schedule_check_status')['halt_sales']
                ])) {
                    $this->errorcode = EC_AD_SCHEDULE_STATUS_NOT_AVAILABLE;
                    return false;
                }

                $status = Config::get('status.football_schedule_check_status')['wait_cancel_clearing'];
                return Loader::model('Schedules', $sportType)->updateCheckStatusById($scheduleId, $status);
            case 'basketball':
                //判断对阵是否有锁
                $orderLockKey = Config::get('cache_option.prefix')['sports_basketball_schedule_lock'] . $scheduleId;
                if (Cache::get($orderLockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }

                //判断可以操作的状态
                $info = Loader::model('Schedules', $sportType)->getInfoById($scheduleId, 'sbs_clearing,sbs_check_status');
                /**  看上面注释
                if ($info['sbs_clearing'] != Config::get('status.basketball_schedule_clearing')['yes']) {
                    $this->errorcode = EC_AD_SCHEDULE_UNCLEARED;
                    return false;
                }**/
                if (!in_array($info['sbs_check_status'], [
                    Config::get('status.basketball_schedule_check_status')['normal'],
                    Config::get('status.basketball_schedule_check_status')['halt_sales']
                ])) {
                    $this->errorcode = EC_AD_SCHEDULE_STATUS_NOT_AVAILABLE;
                    return false;
                }

                $status = Config::get('status.basketball_schedule_check_status')['wait_cancel_clearing'];
                return Loader::model('Schedules', $sportType)->updateCheckStatusById($scheduleId, $status);
            case 'tennis':
                //判断对阵是否有锁
                $orderLockKey = Config::get('cache_option.prefix')['sports_tennis_schedule_lock'] . $scheduleId;
                if (Cache::get($orderLockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }

                //判断可以操作的状态
                $info = Loader::model('Schedules', $sportType)->getInfoById($scheduleId, 'sts_clearing,sts_check_status');
                /**  看上面注释
                if ($info['sts_clearing'] != Config::get('status.tennis_schedule_clearing')['yes']) {
                    $this->errorcode = EC_AD_SCHEDULE_UNCLEARED;
                    return false;
                } **/
                if (!in_array($info['sts_check_status'], [
                    Config::get('status.tennis_schedule_check_status')['normal'],
                    Config::get('status.tennis_schedule_check_status')['halt_sales']
                ])) {
                    $this->errorcode = EC_AD_SCHEDULE_STATUS_NOT_AVAILABLE;
                    return false;
                }

                $status = Config::get('status.tennis_schedule_check_status')['wait_cancel_clearing'];
                return Loader::model('Schedules', $sportType)->updateCheckStatusById($scheduleId, $status);
        }
    }

    /**
     * 结算；只是做个标记，然后程序自动去跑
     * @param $sportType
     * @param $scheduleId
     * @return mixed
     */
    public function clearing($sportType, $scheduleId) {
        switch($sportType) {
            case 'football':
                //判断对阵是否有锁
                $orderLockKey = Config::get('cache_option.prefix')['sports_football_schedule_lock'] . $scheduleId;
                if (Cache::get($orderLockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }

                //判断可以操作的状态
                $info = Loader::model('Schedules', $sportType)->getInfoById($scheduleId, 'sfs_clearing,sfs_check_status');
                if ($info['sfs_clearing'] != Config::get('status.football_schedule_clearing')['no']) {
                    $this->errorcode = EC_AD_SCHEDULE_CLEARED;
                    return false;
                }
                if (!in_array($info['sfs_check_status'], [
                    Config::get('status.football_schedule_check_status')['normal'],
                    Config::get('status.football_schedule_check_status')['halt_sales'],
                    Config::get('status.football_schedule_check_status')['wait_hand_clearing']
                ])) {
                    $this->errorcode = EC_AD_SCHEDULE_STATUS_NOT_AVAILABLE;
                    return false;
                }

                $status = Config::get('status.football_schedule_check_status')['wait_clearing'];
                return Loader::model('common/Schedules', $sportType)->updateCheckStatusById($scheduleId, $status);
            case 'basketball':
                //判断对阵是否有锁
                $orderLockKey = Config::get('cache_option.prefix')['sports_basketball_schedule_lock'] . $scheduleId;
                if (Cache::get($orderLockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }

                //判断可以操作的状态
                $info = Loader::model('Schedules', $sportType)->getInfoById($scheduleId, 'sbs_clearing,sbs_check_status');
                if ($info['sbs_clearing'] != Config::get('status.basketball_schedule_clearing')['no']) {
                    $this->errorcode = EC_AD_SCHEDULE_UNCLEARED;
                    return false;
                }
                if (!in_array($info['sbs_check_status'], [
                    Config::get('status.basketball_schedule_check_status')['normal'],
                    Config::get('status.basketball_schedule_check_status')['halt_sales'],
                    Config::get('status.basketball_schedule_check_status')['wait_hand_clearing']
                ])) {
                    $this->errorcode = EC_AD_SCHEDULE_STATUS_NOT_AVAILABLE;
                    return false;
                }

                $status = Config::get('status.basketball_schedule_check_status')['wait_clearing'];
                return Loader::model('common/Schedules', $sportType)->updateCheckStatusById($scheduleId, $status);
            case 'tennis':
                //判断对阵是否有锁
                $orderLockKey = Config::get('cache_option.prefix')['sports_tennis_schedule_lock'] . $scheduleId;
                if (Cache::get($orderLockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }

                //判断可以操作的状态
                $info = Loader::model('Schedules', $sportType)->getInfoById($scheduleId, 'sts_clearing,sts_check_status');
                if ($info['sts_clearing'] != Config::get('status.tennis_schedule_clearing')['no']) {
                    $this->errorcode = EC_AD_SCHEDULE_UNCLEARED;
                    return false;
                }
                if (!in_array($info['sts_check_status'], [
                    Config::get('status.tennis_schedule_check_status')['normal'],
                    Config::get('status.tennis_schedule_check_status')['halt_sales'],
                    Config::get('status.tennis_schedule_check_status')['wait_hand_clearing']
                ])) {
                    $this->errorcode = EC_AD_SCHEDULE_STATUS_NOT_AVAILABLE;
                    return false;
                }

                $status = Config::get('status.tennis_schedule_check_status')['wait_clearing'];
                return Loader::model('common/Schedules', $sportType)->updateCheckStatusById($scheduleId, $status);
        }
    }

    /**
     * 修改销售状态：封盘，开盘
     * @param $sportType
     * @param $scheduleId
     * @param $status
     * @return mixed
     */
    public function updateSalesStatus($sportType, $scheduleId, $status) {
        switch($sportType) {
            case 'football':
                //判断对阵是否有锁
                $lockKey = Config::get('cache_option.prefix')['sports_football_schedule_lock'] . $scheduleId;
                if (Cache::get($lockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }
                $status = Config::get('status.football_schedule_check_status')[$status];
                return Loader::model('Schedules', 'football')->updateCheckStatusById($scheduleId, $status);
            case 'basketball':
                //判断对阵是否有锁
                $lockKey = Config::get('cache_option.prefix')['sports_basketball_schedule_lock'] . $scheduleId;
                if (Cache::get($lockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }
                $status = Config::get('status.basketball_schedule_check_status')[$status];
                return Loader::model('Schedules', 'basketball')->updateCheckStatusById($scheduleId, $status);
            case 'tennis':
                //判断对阵是否有锁
                $lockKey = Config::get('cache_option.prefix')['sports_tennis_schedule_lock'] . $scheduleId;
                if (Cache::get($lockKey)) {
                    $this->errorcode = EC_AD_SCHEDULE_LOCKED;
                    return false;
                }
                $status = Config::get('status.tennis_schedule_check_status')[$status];
                return Loader::model('Schedules', 'tennis')->updateCheckStatusById($scheduleId, $status);
        }
    }

    /**
     * 获取赛果
     * @param $sportType
     * @param $scheduleId
     * @return array
     */
    public function getResult($sportType, $scheduleId) {
        switch($sportType) {
            case 'football':
                $masterGameId = Loader::model('Games', 'football')->getMasterGameIdByScheduleId($scheduleId);
                break;
            case 'basketball':
                $masterGameId = Loader::model('Games', 'basketball')->getMasterGameIdByScheduleId($scheduleId);
                break;
            case 'tennis':
                $masterGameId = Loader::model('Games', 'tennis')->getMasterGameIdByScheduleId($scheduleId);
                break;
            default :
                return [];

        }
        
        return Loader::model('Results', 'logic')->getInfo($sportType, 'match', $masterGameId);
    }


    /**
     * 手动获取赛果
     * @param $sportType
     * @param $scheduleId
     * @return bool
     */
    public function updateResult($sportType, $scheduleId) {
        switch($sportType) {
            case 'football':
                $scheduleInfo = Loader::model('common/Schedules', 'football')->getInfoById($scheduleId, 'sfs_clearing');
                if ($scheduleInfo['sfs_clearing'] == Config::get('status.football_schedule_clearing')['yes']) {
                    $this->errorcode = EC_AD_SCHEDULE_CLEARED;
                    return false;
                }
                $masterGameId = Loader::model('common/Games', 'football')->getMasterGameIdByScheduleId($scheduleId);
                return $this->_updateFootballResult($masterGameId, $scheduleId);
                break;
            case 'basketball':
                $scheduleInfo = Loader::model('common/Schedules', 'basketball')->getInfoById($scheduleId, 'sbs_clearing');
                if ($scheduleInfo['sbs_clearing'] == Config::get('status.basketball_schedule_clearing')['yes']) {
                    $this->errorcode = EC_AD_SCHEDULE_CLEARED;
                    return false;
                }
                $masterGameId = Loader::model('common/Games', 'basketball')->getMasterGameIdByScheduleId($scheduleId);
                return $this->_updateBasketballResult($masterGameId, $scheduleId);
                break;
            case 'tennis':
                $scheduleInfo = Loader::model('common/Schedules', 'tennis')->getInfoById($scheduleId, 'sts_clearing');
                if ($scheduleInfo['sts_clearing'] == Config::get('status.tennis_schedule_clearing')['yes']) {
                    $this->errorcode = EC_AD_SCHEDULE_CLEARED;
                    return false;
                }
                $masterGameId = Loader::model('common/Games', 'tennis')->getMasterGameIdByScheduleId($scheduleId);
                return $this->_updateTennisResult($masterGameId, $scheduleId);
                break;
            default :
                return false;
        }
    }

    /**
     * 更新赛果
     * @param $masterGameId
     * @param $scheduleId
     * @return bool
     */
    private function _updateFootballResult($masterGameId, $scheduleId) {
        //获取赛果
        $collectConfig = Config::load(APP_PATH . 'config/collect/config.php');
        $url = $collectConfig['collect_url']['football_result_by_game_id'];
        $resultData = json_decode(Curlrequest::post($url, ['id' => $masterGameId]), true);
        if(empty($resultData['data'])) {
            $this->errorcode = EC_AD_GET_SCHEDULES_RESULTS_ERROR;
            return false;
        }

        //判断是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_football_schedule_lock'] . $scheduleId;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_SCHEDULE_LOCKED;
            return false;
        }

        //更新赛果
        $condition['sfr_game_id'] = $masterGameId;
        $resultsLogic = Loader::model('sportsFootballResults');
        $res = $resultsLogic->where($condition)->find();
        if ($res) {
            $update =[
                'sfr_home_score'     => $resultData['data']['sfs_home_score'],
                'sfr_guest_score'    => $resultData['data']['sfs_guest_score'],
                'sfr_home_score_1h'  => $resultData['data']['sfs_1h_home_score'],
                'sfr_guest_score_1h' => $resultData['data']['sfs_1h_guest_score'],
                'sfr_game_type'      => $resultData['data']['sfs_game_type'],
                'sfr_begin_time'     => $resultData['data']['sfs_begin_time'], 
                'sfr_modify_time'    => date('Y-m-d H:i:s'),
            ];

            $ret = $resultsLogic->where($condition)->update($update);
            if(false === $ret) {
               $this->errorcode = EC_AD_UPDATE_SCHEDULES_RESULTS_ERROR;
               return false;
            }

        } else {
            $ret = $this->_addFootballResult($masterGameId, $scheduleId, $resultData);
            if(false === $ret) {
                return false;
            }
        }

        //修改比赛状态
        $res = Loader::model('Schedules', 'football')->updateStatusById($scheduleId, $resultData['data']['sfs_status']);
        if(false === $res) {
            $this->errorcode = EC_AD_UPDATE_SCHEDULES_RESULTS_ERROR;
            return false;
        }

        return true;
    }

    /**
     * 新增赛果
     * @param $masterGameId
     * @param $scheduleId
     * @param $resultData
     * @return bool
     */
    private function _addFootballResult($masterGameId, $scheduleId, $resultData) {
        $scheduleInfo = Loader::model('Schedules', 'football')->getInfoById($scheduleId);
        $data =[
            'sfr_game_id'        => $masterGameId,
            'sfr_sfs_id'         => $scheduleId,
            'sfr_sfm_id'         => $scheduleInfo['sfs_sfm_id'],
            'sfr_home_id'        => $scheduleInfo['sfs_home_id'],
            'sfr_guest_id'       => $scheduleInfo['sfs_guest_id'],
            'sfr_home_score'     => $resultData['data']['sfs_home_score'],
            'sfr_guest_score'    => $resultData['data']['sfs_guest_score'],
            'sfr_home_score_1h'  => $resultData['data']['sfs_1h_home_score'],
            'sfr_guest_score_1h' => $resultData['data']['sfs_1h_guest_score'],
            'sfr_game_type'      => $resultData['data']['sfs_game_type'],
            'sfr_begin_time'     => $resultData['data']['sfs_begin_time'],
            'sfr_create_time'    => date('Y-m-d H:i:s'),
            'sfr_modify_time'    => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('sportsFootballResults')->insert($data);
        if(false === $ret) {
            $this->errorcode = EC_AD_UPDATE_SCHEDULES_RESULTS_ERROR;
            return false;
        }

        return true;
    }

    private function _updateBasketballResult($masterGameId, $scheduleId) {
        //采集数据
        $collectConfig = Config::load(APP_PATH . 'config/collect/config.php');
        $url = $collectConfig['collect_url']['basketball_result_by_game_id'];
        $resultData = json_decode(Curlrequest::post($url, ['id' => $masterGameId]), true);
        if(empty($resultData['data'])) {
            $this->errorcode = EC_AD_GET_SCHEDULES_RESULTS_ERROR;
            return false;
        }

        //判断是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_basketball_schedule_lock'] . $scheduleId;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_SCHEDULE_LOCKED;
            return false;
        }

        //更新赛果
        $condition['sbr_game_id'] = $masterGameId;
        $resultsLogic = Loader::model('sportsBasketballResults');
        $res = $resultsLogic->where($condition)->find();
        if ($res) {
            $update =[
                'sbr_home_score_1q'  => $resultData['data']['sbs_1st_home_score'],
                'sbr_guest_score_1q' => $resultData['data']['sbs_1st_guest_score'],
                'sbr_home_score_2q'  => $resultData['data']['sbs_2nd_home_score'],
                'sbr_guest_score_2q' => $resultData['data']['sbs_2nd_guest_score'],
                'sbr_home_score_3q'  => $resultData['data']['sbs_3rd_home_score'],
                'sbr_guest_score_3q' => $resultData['data']['sbs_3rd_guest_score'],
                'sbr_home_score_4q'  => $resultData['data']['sbs_4th_home_score'],
                'sbr_guest_score_4q' => $resultData['data']['sbs_4th_guest_score'],
                'sbr_home_score_1h'  => $resultData['data']['sbs_1h_home_score'],
                'sbr_guest_score_1h' => $resultData['data']['sbs_1h_guest_score'],
                'sbr_home_score_2h'  => $resultData['data']['sbs_2h_home_score'],
                'sbr_guest_score_2h' => $resultData['data']['sbs_2h_guest_score'],
                'sbr_home_score_ot'  => $resultData['data']['sbs_ot_home_score'],
                'sbr_guest_score_ot' => $resultData['data']['sbs_ot_guest_score'],
                'sbr_home_score'     => $resultData['data']['sbs_home_score'],
                'sbr_guest_score'    => $resultData['data']['sbs_guest_score'],
                'sbr_begin_time'     => $resultData['data']['sbs_begin_time'],
                'sbr_modify_time'    => date('Y-m-d H:i:s'),
            ];
            $ret = $resultsLogic->where($condition)->update($update);
            if(false === $ret) {
                $this->errorcode = EC_AD_UPDATE_SCHEDULES_RESULTS_ERROR;
                return false;
            }
        } else {
            $ret = $this->_addBasketballResult($masterGameId, $scheduleId, $resultData);
            if(false === $ret) {
                return false;
            }
        }

        //修改比赛状态
        $res = Loader::model('Schedules', 'basketball')->updateStatusById($scheduleId, $resultData['data']['sbs_status']);
        if(false === $res) {
            $this->errorcode = EC_AD_UPDATE_SCHEDULES_RESULTS_ERROR;
            return false;
        }

        return true;
    }

    private function _addBasketballResult($masterGameId, $scheduleId, $resultData) {
        $scheduleInfo = Loader::model('Schedules', 'basketball')->getInfoById($scheduleId);
        $data =[
            'sbr_game_id'        => $masterGameId,
            'sbr_sbs_id'         => $scheduleId,
            'sbr_sbm_id'         => $scheduleInfo['sbs_sbm_id'],
            'sbr_home_id'        => $scheduleInfo['sbs_home_id'],
            'sbr_guest_id'       => $scheduleInfo['sbs_guest_id'],
            'sbr_home_score_1q'  => $resultData['data']['sbs_1st_home_score'],
            'sbr_guest_score_1q' => $resultData['data']['sbs_1st_guest_score'],
            'sbr_home_score_2q'  => $resultData['data']['sbs_2nd_home_score'],
            'sbr_guest_score_2q' => $resultData['data']['sbs_2nd_guest_score'],
            'sbr_home_score_3q'  => $resultData['data']['sbs_3rd_home_score'],
            'sbr_guest_score_3q' => $resultData['data']['sbs_3rd_guest_score'],
            'sbr_home_score_4q'  => $resultData['data']['sbs_4th_home_score'],
            'sbr_guest_score_4q' => $resultData['data']['sbs_4th_guest_score'],
            'sbr_home_score_1h'  => $resultData['data']['sbs_1h_home_score'],
            'sbr_guest_score_1h' => $resultData['data']['sbs_1h_guest_score'],
            'sbr_home_score_2h'  => $resultData['data']['sbs_2h_home_score'],
            'sbr_guest_score_2h' => $resultData['data']['sbs_2h_guest_score'],
            'sbr_home_score_ot'  => $resultData['data']['sbs_ot_home_score'],
            'sbr_guest_score_ot' => $resultData['data']['sbs_ot_guest_score'],
            'sbr_home_score'     => $resultData['data']['sbs_home_score'],
            'sbr_guest_score'    => $resultData['data']['sbs_guest_score'],
            'sbr_begin_time'     => $resultData['data']['sbs_begin_time'],
            'sbr_create_time'    => date('Y-m-d H:i:s'),
            'sbr_modify_time'    => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('sportsBasketballResults')->insert($data);
        if(false === $ret) {
            $this->errorcode = EC_AD_UPDATE_SCHEDULES_RESULTS_ERROR;
            return false;
        }

        return true;
    }

    private function _updateTennisResult($masterGameId, $scheduleId) {
        //采集赛果
        $collectConfig = Config::load(APP_PATH . 'config/collect/config.php');
        $url = $collectConfig['collect_url']['tennis_result_by_game_id'];
        $resultData = json_decode(Curlrequest::post($url, ['id' => $masterGameId]), true);
        if(empty($resultData['data'])) {
            $this->errorcode = EC_AD_GET_SCHEDULES_RESULTS_ERROR;
            return false;
        }

        //判断是否有锁
        $orderLockKey = Config::get('cache_option.prefix')['sports_tennis_schedule_lock'] . $scheduleId;
        if (Cache::get($orderLockKey)) {
            $this->errorcode = EC_AD_SCHEDULE_LOCKED;
            return false;
        }

        //更新赛果
        $condition['str_game_id'] = $masterGameId;
        $resultsLogic = Loader::model('sportsTennisResults');
        $res = $resultsLogic->where($condition)->find();
        if ($res) {
            $update =[
                'str_home_score_1st'       => $resultData['data']['sts_1st_home_score'],
                'str_guest_score_1st'      => $resultData['data']['sts_1st_guest_score'],
                'str_home_score_2nd'       => $resultData['data']['sts_2nd_home_score'],
                'str_guest_score_2nd'      => $resultData['data']['sts_2nd_guest_score'],
                'str_home_score_3rd'       => $resultData['data']['sts_3rd_home_score'],
                'str_guest_score_3rd'      => $resultData['data']['sts_3rd_guest_score'],
                'str_home_score_4th'       => $resultData['data']['sts_4th_home_score'],
                'str_guest_score_4th'      => $resultData['data']['sts_4th_guest_score'],
                'str_home_score_5th'       => $resultData['data']['sts_5th_home_score'],
                'str_guest_score_5th'      => $resultData['data']['sts_5th_guest_score'],
                'str_home_score_handicap'  => $resultData['data']['sts_gm_home_score'],
                'str_guest_score_handicap' => $resultData['data']['sts_gm_guest_score'],
                'str_home_score_ou'        => $resultData['data']['sts_ou_home_score'],
                'str_guest_score_ou'       => $resultData['data']['sts_ou_guest_score'],
                'str_home_score'           => $resultData['data']['sts_home_score'],
                'str_guest_score'          => $resultData['data']['sts_guest_score'],
                'str_begin_time'           => $resultData['data']['sts_begin_time'],
                'str_modify_time'          => date('Y-m-d H:i:s'),
            ];
            $ret = $resultsLogic->where($condition)->update($update);
            if(false === $ret) {
                $this->errorcode = EC_AD_UPDATE_SCHEDULES_RESULTS_ERROR;
                return false;
            }
        } else {
            $ret = $this->_addTennisResult($masterGameId, $scheduleId, $resultData);
            if(false === $ret) {
                return false;
            }
        }

        //修改比赛状态
        $res = Loader::model('Schedules', 'tennis')->updateStatusById($scheduleId, $resultData['data']['sts_status']);
        if(false === $res) {
            $this->errorcode = EC_AD_UPDATE_SCHEDULES_RESULTS_ERROR;
            return false;
        }

        return true;
    }

    private function _addTennisResult($masterGameId, $scheduleId, $resultData){
        $scheduleInfo = Loader::model('Schedules', 'tennis')->getInfoById($scheduleId);
        $data =[
            'str_game_id'              => $masterGameId,
            'str_sts_id'               => $scheduleId,
            'str_stm_id'               => $scheduleInfo['sts_stm_id'],
            'str_home_id'              => $scheduleInfo['sts_home_id'],
            'str_guest_id'             => $scheduleInfo['sts_guest_id'],
            'str_home_score_1st'       => $resultData['data']['sts_1st_home_score'],
            'str_guest_score_1st'      => $resultData['data']['sts_1st_guest_score'],
            'str_home_score_2nd'       => $resultData['data']['sts_2nd_home_score'],
            'str_guest_score_2nd'      => $resultData['data']['sts_2nd_guest_score'],
            'str_home_score_3rd'       => $resultData['data']['sts_3rd_home_score'],
            'str_guest_score_3rd'      => $resultData['data']['sts_3rd_guest_score'],
            'str_home_score_4th'       => $resultData['data']['sts_4th_home_score'],
            'str_guest_score_4th'      => $resultData['data']['sts_4th_guest_score'],
            'str_home_score_5th'       => $resultData['data']['sts_5th_home_score'],
            'str_guest_score_5th'      => $resultData['data']['sts_5th_guest_score'],
            'str_home_score_handicap'  => $resultData['data']['sts_gm_home_score'],
            'str_guest_score_handicap' => $resultData['data']['sts_gm_guest_score'],
            'str_home_score_ou'        => $resultData['data']['sts_ou_home_score'],
            'str_guest_score_ou'       => $resultData['data']['sts_ou_guest_score'],
            'str_home_score'           => $resultData['data']['sts_home_score'],
            'str_guest_score'          => $resultData['data']['sts_guest_score'],
            'str_begin_time'           => $resultData['data']['sts_begin_time'],
            'str_create_time'          => date('Y-m-d H:i:s'),
            'str_modify_time'          => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('sportsTennisResults')->insert($data);
        if(false === $ret) {
            $this->errorcode = EC_AD_UPDATE_SCHEDULES_RESULTS_ERROR;
            return false;
        }

        return true;
    }
}
