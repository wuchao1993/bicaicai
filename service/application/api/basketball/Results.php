<?php
/**
 * 赛果业务逻辑
 * @createTime 2017/4/25 14:46
 */

namespace app\api\basketball;

use think\Loader;
use think\Model;

class Results extends \app\common\basketball\Results {
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
     * 返回联赛赛果
     * @param $date 筛选时间
     * @param $page 页数
     * @return array|bool
     */
    public function getResultsMatch($date, $page) {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $sTime = $date . ' 00:00:00';
        $eTime = $date . ' 23:59:59';
        $where = [
            'sbr_begin_time' => ['BETWEEN', [$sTime, $eTime]]
        ];
        $orderBy = [
            'sbr_begin_time' => 'asc'
        ];

        //计算总数
        $total = Loader::model('SportsBasketballResults')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        if ($page == 'all') {
            $ret = Loader::model('SportsBasketballResults')->where($where)->order($orderBy)->select();
        } else if (is_numeric($page)) {
            $ret = Loader::model('SportsBasketballResults')->where($where)->order($orderBy)->page($page, $this->pageSize)->select();
        }

        if (!$ret) {
            return ['total_page' => 0, 'result' => []];
        }

        //获取球队和联赛名称
        $list = [];
        $matchesLogic = Loader::model('Matches', 'basketball');
        $teamsLogic = Loader::model('Teams', 'basketball');
        foreach($ret as $key => $item) {
            $item                         = $item->toArray();
            $list[$key]['game_id']        = $item['sbr_game_id'];
            $list[$key]['match_id']       = $item['sbr_sbm_id'];
            $list[$key]['home_score']     = $item['sbr_home_score'];
            $list[$key]['guest_score']    = $item['sbr_guest_score'];
            $list[$key]['home_score_ot']  = $item['sbr_home_score_ot'];
            $list[$key]['guest_score_ot'] = $item['sbr_guest_score_ot'];
            $list[$key]['begin_time']     = $item['sbr_begin_time'];
            $matchInfo                    = $matchesLogic->getInfoById($item['sbr_sbm_id']);
            $list[$key]['match_name']     = $matchInfo['sbm_name'];
            $teamInfo                     = $teamsLogic->getInfoById($item['sbr_home_id']);
            $list[$key]['home_name']      = $teamInfo['sbt_name'];
            $teamInfo                     = $teamsLogic->getInfoById($item['sbr_guest_id']);
            $list[$key]['guest_name']     = $teamInfo['sbt_name'];
        }

        //按联赛归类
        $flag = 0;
        $data = [];
        foreach($list as $key => $item) {
            if ($key == 0) {
                $data[$flag]['match_id'] = $item['match_id'];
                $data[$flag]['match_name'] = $item['match_name'];
                unset($item['match_id'], $item['match_name']);
                $data[$flag]['schedule'][] = $item;
            } else if ($list[$key]['match_id'] == $list[$key - 1]['match_id']) {
                unset($item['match_id'], $item['match_name']);
                $data[$flag]['schedule'][] = $item;
            } else {
                ++ $flag;
                $data[$flag]['match_id'] = $item['match_id'];
                $data[$flag]['match_name'] = $item['match_name'];
                unset($item['match_id'], $item['match_name']);
                $data[$flag]['schedule'][] = $item;
            }
        }
        unset($ret, $list);
        return ['total_page' => ceil($total / $this->pageSize), 'result' => $data];
    }

    /**
     * 获取冠军的赛果
     * @param $date 日期
     * @param $page 页数
     * @return array|bool
     */
    public function getResultsOutright($date, $page) {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $sTime = $date . ' 00:00:00';
        $eTime = $date . ' 23:59:59';
        $where = [
            'sbo_end_time' => ['BETWEEN', [$sTime, $eTime]],
            'sbo_result' => ['NEQ', ''],
        ];
        $orderBy = [
            'sbo_end_time' => 'asc'
        ];
        $field = [
            'sbo_game_id AS game_id',
            'sbo_sbm_id AS match_id',
            'sbo_game_type AS game_type',
            'sbo_end_time AS end_time',
            'sbo_result AS result',
        ];

        //计算总数
        $total = Loader::model('SportsBasketballOutright')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        if ($page == 'all') {
            $ret = Loader::model('SportsBasketballOutright')->where($where)->order($orderBy)->column($field);
        } else if (is_numeric($page)) {
            $ret = Loader::model('SportsBasketballOutright')->where($where)->order($orderBy)->page($page, $this->pageSize)->column($field);
        }

        if (!$ret) {
            return ['total_page' => 0, 'result' => []];
        }
        $ret = array_values($ret);

        //按联赛归类
        $flag = 0;
        $data = [];
        $matchesLogic = Loader::model('Matches', 'basketball');
        foreach($ret as $key => $item) {
            $matchInfo = $matchesLogic->getInfoById($item['match_id']);
            $item['match_name'] = $matchInfo['sbm_name'];
            $item['result'] = json_decode($item['result'], true);
            if ($key == 0) {
                $data[$flag]['match_id'] = $item['match_id'];
                $data[$flag]['match_name'] = $item['match_name'];
                unset($item['match_id'], $item['match_name']);
                $data[$flag]['games'][] = $item;
            } else if ($ret[$key]['match_id'] == $ret[$key - 1]['match_id']) {
                unset($item['match_id'], $item['match_name']);
                $data[$flag]['games'][] = $item;
            } else {
                ++ $flag;
                $data[$flag]['match_id'] = $item['match_id'];
                $data[$flag]['match_name'] = $item['match_name'];
                unset($item['match_id'], $item['match_name']);
                $data[$flag]['games'][] = $item;
            }
        }
        return ['total_page' => ceil($total / $this->pageSize), 'result' => $data];
    }
}