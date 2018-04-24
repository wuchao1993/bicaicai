<?php
/**
 * 足球赛果业务逻辑
 * @createTime 2017/5/1 10:21
 */

namespace app\collect\football;

use think\Config;
use think\Loader;
use think\Model;

class Results extends Model {

    /**
     * 采集数据入库
     * @param string $date
     * @return bool
     */
    public function collect($date = '') {
        //获取采集数据
        $data = Loader::model('Football', 'service')->collectResults($date);
        if (!$data) {
            return false;
        }

        $flag = 0;
        $valueArr = $scheduleStatus = [];
        $count = count($data);
        $gamesLogic = Loader::model('common/Games', 'football');
        $schedulesLogic = Loader::model('common/Schedules', 'football');
        foreach($data as $item) {
            ++$flag;

            $gameInfo = $gamesLogic->getInfoByGameId($item['sfg_id'], 'sfg_sfs_id,sfg_sfm_id,sfg_home_id,sfg_guest_id,sfg_game_type');
            if ($gameInfo) {
                //已结算不更新赛果
                $scheduleInfo = $schedulesLogic->getInfoById($gameInfo['sfg_sfs_id'], 'sfs_clearing');
                if ($scheduleInfo['sfs_clearing'] == Config::get('status.football_schedule_clearing')['yes']) {
                    continue;
                }

                $item['schedule_id'] = $gameInfo['sfg_sfs_id'];
                $item['match_id']    = $gameInfo['sfg_sfm_id'];
                $item['home_id']     = $gameInfo['sfg_home_id'];
                $item['guest_id']    = $gameInfo['sfg_guest_id'];
                $item['game_type']   = $gameInfo['sfg_game_type'];

                //N条数据的value一次insert
                $valueArr[] = $this->getValue($item);

                $scheduleStatus[$gameInfo['sfg_sfs_id']]['schedule_id'] = $gameInfo['sfg_sfs_id'];
                $scheduleStatus[$gameInfo['sfg_sfs_id']]['status'] = $item['sfs_status'];
            }

            if ($valueArr && ($flag % 100 == 0 || $flag === $count)) {
                $ret = $this->checkResults($valueArr);
                $valueArr = [];
                if ($ret === false) {
                    return false;
                }
            }
        }

        if ($scheduleStatus) {
            Loader::model('Schedules', 'football')->updateStatus($scheduleStatus);
        }
        return true;
    }

    /**
     * 修复一些当天没有出赛果的比赛
     * @return string
     */
    public function repair() {
        $join = [
            ['sports_football_results r', 's.sfs_id=r.sfr_sfs_id', 'LEFT']
        ];
        $where = [
            'sfs_status' => ['IN', [
                Config::get('status.football_schedule_status')['not_begin'],
                Config::get('status.football_schedule_status')['half_time'],
                Config::get('status.football_schedule_status')['1h_in_game'],
                Config::get('status.football_schedule_status')['2h_in_game'],
            ]],
            'sfs_begin_time' => ['LT', date('Y-m-d') . ' 00:00:00'],
        ];
        $data = Loader::model('SportsFootballSchedules')
            ->alias('s')
            ->where($where)
            ->field('LEFT(sfs_begin_time,10) AS begin_time')
            ->join($join)
            ->group('begin_time')
            ->select();

        if ($data) {
            foreach($data as $key => $item) {
                $this->collect($item->begin_time);
            }
        }
        return 'success';
    }

    /**
     * 组合value字符串
     * @param $params
     * @return mixed
     */
    public function getValue($params) {
        $valueArr = [
            $params['sfg_id'],
            $params['schedule_id'],
            $params['match_id'],
            $params['home_id'],
            $params['guest_id'],
            "'{$params['sfs_home_score']}'",
            "'{$params['sfs_guest_score']}'",
            "'{$params['sfs_1h_home_score']}'",
            "'{$params['sfs_1h_guest_score']}'",
            "'{$params['sfs_game_type']}'",
            "'{$params['sfs_begin_time']}'",
            '\'' . date('Y-m-d H:i:s') . '\'',
            '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        return implode(',', $valueArr);
    }

    /**
     * 判断是否存在，不存在则插入，存在更新
     * @param $valueArr
     * @return bool
     */
    public function checkResults($valueArr) {
        //组合values
        $valueStr = '';
        foreach($valueArr as $value) {
            $valueStr .= '(' . $value .  '),';
        }
        $valueStr = trim($valueStr, ',');

        $updateArr = [
            'sfr_home_score=VALUES(sfr_home_score)',
            'sfr_guest_score=VALUES(sfr_guest_score)',
            'sfr_home_score_1h=VALUES(sfr_home_score_1h)',
            'sfr_guest_score_1h=VALUES(sfr_guest_score_1h)',
            'sfr_begin_time=VALUES(sfr_begin_time)',
            'sfr_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        $resultsModel = Loader::model('SportsFootballResults');

        //执行sql
        $table = $resultsModel->getTable();
        $sql = "INSERT INTO {$table} VALUES {$valueStr} ON DUPLICATE KEY UPDATE {$update}";
        $ret = $resultsModel->execute($sql);
        if ($ret === false) {
            return false;
        }
        return true;
    }
}