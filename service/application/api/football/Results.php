<?php
/**
 * 赛果业务逻辑
 * @createTime 2017/4/25 14:46
 */

namespace app\api\football;

use think\Loader;
use think\Model;

class Results extends \app\common\football\Results {
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
     * 返回足球的联赛赛果
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
            'sfr_begin_time' => ['BETWEEN', [$sTime, $eTime]]
        ];
        $orderBy = [
            'sfr_begin_time' => 'asc'
        ];

        //计算总数
        $total = Loader::model('SportsFootballResults')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        if ($page == 'all') {
            $ret = Loader::model('SportsFootballResults')->where($where)->order($orderBy)->select();
        } else if (is_numeric($page)) {
            $ret = Loader::model('SportsFootballResults')->where($where)->order($orderBy)->page($page, $this->pageSize)->select();
        }

        if (!$ret) {
            return ['total_page' => 0, 'result' => []];
        }

        //获取球队和联赛名称
        $list = [];
        foreach($ret as $key => $item) {
            $item = $item->toArray();
            $list[$key]['game_id'] = $item['sfr_game_id'];
            $list[$key]['match_id'] = $item['sfr_sfm_id'];
            $list[$key]['home_score'] = $item['sfr_home_score'];
            $list[$key]['guest_score'] = $item['sfr_guest_score'];
            $list[$key]['home_score_1h'] = $item['sfr_home_score_1h'];
            $list[$key]['guest_score_1h'] = $item['sfr_guest_score_1h'];
            $list[$key]['begin_time'] = $item['sfr_begin_time'];
            $list[$key]['game_type'] = $item['sfr_game_type'];

            $matchInfo = Loader::model('Matches', 'football')->getInfoById($item['sfr_sfm_id']);
            $list[$key]['match_name'] = isset($matchInfo['sfm_name']) ? $matchInfo['sfm_name'] : '';
            $teamInfo = Loader::model('Teams', 'football')->getInfoById($item['sfr_home_id']);
            $list[$key]['home_name'] = isset($teamInfo['sft_name']) ? $teamInfo['sft_name'] : '';
            $teamInfo = Loader::model('Teams', 'football')->getInfoById($item['sfr_guest_id']);
            $list[$key]['guest_name'] = isset($teamInfo['sft_name']) ? $teamInfo['sft_name'] : '';
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
            'sfo_end_time' => ['BETWEEN', [$sTime, $eTime]],
            'sfo_result' => ['NEQ', ''],
        ];
        $orderBy = [
            'sfo_end_time' => 'asc'
        ];
        $field = [
            'sfo_game_id AS game_id',
            'sfo_sfm_id AS match_id',
            'sfo_game_type AS game_type',
            'sfo_end_time AS end_time',
            'sfo_result AS result',
        ];

        //计算总数
        $total = Loader::model('SportsFootballOutright')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        if ($page == 'all') {
            $ret = Loader::model('SportsFootballOutright')->where($where)->order($orderBy)->column($field);
        } else if (is_numeric($page)) {
            $ret = Loader::model('SportsFootballOutright')->where($where)->order($orderBy)->page($page, $this->pageSize)->column($field);
        }

        if (!$ret) {
            return ['total_page' => 0, 'result' => []];
        }
        $ret = array_values($ret);

        //按联赛归类
        $flag = 0;
        $data = [];
        foreach($ret as $key => $item) {
            $matchInfo = Loader::model('Matches', 'football')->getInfoById($item['match_id']);
            $item['match_name'] = $matchInfo['sfm_name'];
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