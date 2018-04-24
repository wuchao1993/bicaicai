<?php
/**
 * 足球赛果业务逻辑
 * @createTime 2017/5/1 10:21
 */

namespace app\collect\basketball;

use think\Config;
use think\Loader;
use think\Model;
use think\Log;

class Results extends Model {

    /**
     * 采集数据入库
     * @param string $date
     * @return bool
     */
    public function collect($date = '') {
        //获取采集数据
        $data = Loader::model('Basketball', 'service')->collectResults($date);
        if (!$data) {
            return false;
        }

        $flag = 0;
        $valueArr = $scheduleStatus = [];
        $count = count($data);
        $gamesLogic = Loader::model('common/Games', 'basketball');
        $schedulesLogic = Loader::model('common/Schedules', 'basketball');
        foreach($data as $item) {
            ++$flag;

            $gameInfo = $gamesLogic->getInfoByGameId($item['sbg_id'], 'sbg_sbs_id,sbg_sbm_id,sbg_home_id,sbg_guest_id,sbg_game_type');
            if ($gameInfo) {
                //已结算不更新赛果
                $scheduleInfo = $schedulesLogic->getInfoById($gameInfo['sbg_sbs_id'], 'sbs_clearing');
                if ($scheduleInfo['sbs_clearing'] == Config::get('status.basketball_schedule_clearing')['yes']) {
                    continue;
                }

                $item['schedule_id'] = $gameInfo['sbg_sbs_id'];
                $item['match_id']    = $gameInfo['sbg_sbm_id'];
                $item['home_id']     = $gameInfo['sbg_home_id'];
                $item['guest_id']    = $gameInfo['sbg_guest_id'];
                $item['game_type']   = $gameInfo['sbg_game_type'];

                //N条数据的value一次insert
                $valueArr[] = $this->getValue($item);

                $scheduleStatus[$gameInfo['sbg_sbs_id']]['schedule_id'] = $gameInfo['sbg_sbs_id'];
                $scheduleStatus[$gameInfo['sbg_sbs_id']]['status'] = $item['sbs_status'];
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
            //TODO 改成通过schedule表的主盘口id更新，这样延赛的比赛也会更新状态
            Loader::model('Schedules', 'basketball')->updateStatus($scheduleStatus);
        }

        return true;
    }

    /**
     * 修复一些当天没有出赛果的比赛
     * @return string
     */
    public function repair() {
        $join = [
            ['sports_basketball_results r', 's.sbs_id=r.sbr_sbs_id', 'LEFT']
        ];
        $where = [
            'sbs_status' => ['IN', [
                Config::get('status.basketball_schedule_status')['not_begin'],
                Config::get('status.basketball_schedule_status')['in_game'],
                Config::get('status.basketball_schedule_status')['half_time'],
            ]],
            'sbs_begin_time' => ['LT', date('Y-m-d') . ' 00:00:00'],
        ];
        $data = Loader::model('SportsBasketballSchedules')
            ->alias('s')
            ->where($where)
            ->field('LEFT(sbs_begin_time,10) AS begin_time')
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
            $params['sbg_id'],
            $params['schedule_id'],
            $params['match_id'],
            $params['home_id'],
            $params['guest_id'],
            "'{$params['sbs_1st_home_score']}'",
            "'{$params['sbs_1st_guest_score']}'",
            "'{$params['sbs_2nd_home_score']}'",
            "'{$params['sbs_2nd_guest_score']}'",
            "'{$params['sbs_3rd_home_score']}'",
            "'{$params['sbs_3rd_guest_score']}'",
            "'{$params['sbs_4th_home_score']}'",
            "'{$params['sbs_4th_guest_score']}'",
            "'{$params['sbs_1h_home_score']}'",
            "'{$params['sbs_1h_guest_score']}'",
            "'{$params['sbs_2h_home_score']}'",
            "'{$params['sbs_2h_guest_score']}'",
            "'{$params['sbs_ot_home_score']}'",
            "'{$params['sbs_ot_guest_score']}'",
            "'{$params['sbs_home_score']}'",
            "'{$params['sbs_guest_score']}'",
            "'{$params['sbs_begin_time']}'",
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

        $fields = 'sbr_game_id, sbr_sbs_id, sbr_sbm_id, sbr_home_id, sbr_guest_id, sbr_home_score_1q, sbr_guest_score_1q, 
        sbr_home_score_2q, sbr_guest_score_2q, sbr_home_score_3q, sbr_guest_score_3q, sbr_home_score_4q, sbr_guest_score_4q,
        sbr_home_score_1h, sbr_guest_score_1h, sbr_home_score_2h, sbr_guest_score_2h, sbr_home_score_ot, sbr_guest_score_ot, 
        sbr_home_score, sbr_guest_score, sbr_begin_time, sbr_create_time, sbr_modify_time';

        $updateArr = [
            'sbr_home_score=VALUES(sbr_home_score)',
            'sbr_guest_score=VALUES(sbr_guest_score)',
            'sbr_home_score_1q=VALUES(sbr_home_score_1q)',
            'sbr_guest_score_1q=VALUES(sbr_guest_score_1q)',
            'sbr_home_score_2q=VALUES(sbr_home_score_2q)',
            'sbr_guest_score_2q=VALUES(sbr_guest_score_2q)',
            'sbr_home_score_3q=VALUES(sbr_home_score_3q)',
            'sbr_guest_score_3q=VALUES(sbr_guest_score_3q)',
            'sbr_home_score_4q=VALUES(sbr_home_score_4q)',
            'sbr_guest_score_4q=VALUES(sbr_guest_score_4q)',
            'sbr_home_score_1h=VALUES(sbr_home_score_1h)',
            'sbr_guest_score_1h=VALUES(sbr_guest_score_1h)',
            'sbr_home_score_2h=VALUES(sbr_home_score_2h)',
            'sbr_guest_score_2h=VALUES(sbr_guest_score_2h)',
            'sbr_home_score_ot=VALUES(sbr_home_score_ot)',
            'sbr_guest_score_ot=VALUES(sbr_guest_score_ot)',
            'sbr_begin_time=VALUES(sbr_begin_time)',
            'sbr_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        $resultsModel = Loader::model('SportsBasketballResults');

        //执行sql
        $table = $resultsModel->getTable();
        $sql = "INSERT INTO {$table} ({$fields}) VALUES {$valueStr} ON DUPLICATE KEY UPDATE {$update}";
        $ret = $resultsModel->execute($sql);
        if ($ret === false) {
            return false;
        }
        Log::write("basketball_result_fetch_data:save_result");
        return true;
    }
}