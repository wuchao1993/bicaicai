<?php
/**
 * 赛果业务逻辑
 * @createTime 2017/9/27 10:21
 */

namespace app\collect\tennis;

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
        $data = Loader::model('Tennis', 'service')->collectResults($date);
        if (!$data) {
            return false;
        }

        $flag = 0;
        $valueArr = $scheduleStatus = [];
        $count = count($data);
        $gamesLogic = Loader::model('common/Games', 'tennis');
        $schedulesLogic = Loader::model('common/Schedules', 'tennis');
        foreach($data as $item) {
            ++$flag;

            $gameInfo = $gamesLogic->getInfoByGameId($item['stg_id'], 'stg_sts_id,stg_stm_id,stg_home_id,stg_guest_id');
            if ($gameInfo) {
                //已结算不更新赛果
                $scheduleInfo = $schedulesLogic->getInfoById($gameInfo['stg_sts_id'], 'sts_clearing');
                if ($scheduleInfo['sts_clearing'] == Config::get('status.tennis_schedule_clearing')['yes']) {
                    continue;
                }

                $item['schedule_id'] = $gameInfo['stg_sts_id'];
                $item['match_id']    = $gameInfo['stg_stm_id'];
                $item['home_id']     = $gameInfo['stg_home_id'];
                $item['guest_id']    = $gameInfo['stg_guest_id'];

                //N条数据的value一次insert
                $valueArr[] = $this->getValue($item);

                $scheduleStatus[$gameInfo['stg_sts_id']]['schedule_id'] = $gameInfo['stg_sts_id'];
                $scheduleStatus[$gameInfo['stg_sts_id']]['status'] = $item['sts_status'];
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
            Loader::model('Schedules', 'tennis')->updateStatus($scheduleStatus);
        }
        return true;
    }

    /**
     * 修复一些当天没有出赛果的比赛
     * @return string
     */
    public function repair() {
        $join = [
            ['sports_tennis_results r', 's.sts_id=r.str_sts_id', 'LEFT']
        ];
        $where = [
            'sts_status' => ['IN', [
                Config::get('status.tennis_schedule_status')['not_begin'],
                Config::get('status.tennis_schedule_status')['in_game']
            ]],
            'sts_begin_time' => ['LT', date('Y-m-d') . ' 00:00:00'],
        ];
        $data = Loader::model('SportsTennisSchedules')
            ->alias('s')
            ->where($where)
            ->field('LEFT(sts_begin_time,10) AS begin_time')
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
            $params['stg_id'],
            $params['schedule_id'],
            $params['match_id'],
            $params['home_id'],
            $params['guest_id'],
            "'{$params['sts_1st_home_score']}'",
            "'{$params['sts_1st_guest_score']}'",
            "'{$params['sts_2nd_home_score']}'",
            "'{$params['sts_2nd_guest_score']}'",
            "'{$params['sts_3rd_home_score']}'",
            "'{$params['sts_3rd_guest_score']}'",
            "'{$params['sts_4th_home_score']}'",
            "'{$params['sts_4th_guest_score']}'",
            "'{$params['sts_5th_home_score']}'",
            "'{$params['sts_5th_guest_score']}'",
            "'{$params['sts_gm_home_score']}'",
            "'{$params['sts_gm_guest_score']}'",
            "'{$params['sts_ou_home_score']}'",
            "'{$params['sts_ou_guest_score']}'",
            "'{$params['sts_home_score']}'",
            "'{$params['sts_guest_score']}'",
            "'{$params['sts_begin_time']}'",
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

        $fields = 'str_game_id, str_sts_id, str_stm_id, str_home_id, str_guest_id, str_home_score_1st, str_guest_score_1st, 
        str_home_score_2nd, str_guest_score_2nd, str_home_score_3rd, str_guest_score_3rd, str_home_score_4th, str_guest_score_4th,
        str_home_score_5th, str_guest_score_5th, str_home_score_handicap, str_guest_score_handicap, str_home_score_ou, str_guest_score_ou, 
        str_home_score, str_guest_score, str_begin_time, str_create_time, str_modify_time';

        $updateArr = [
            'str_home_score=VALUES(str_home_score)',
            'str_guest_score=VALUES(str_guest_score)',
            'str_home_score_1st=VALUES(str_home_score_1st)',
            'str_guest_score_1st=VALUES(str_guest_score_1st)',
            'str_home_score_2nd=VALUES(str_home_score_2nd)',
            'str_guest_score_2nd=VALUES(str_guest_score_2nd)',
            'str_home_score_3rd=VALUES(str_home_score_3rd)',
            'str_guest_score_3rd=VALUES(str_guest_score_3rd)',
            'str_home_score_4th=VALUES(str_home_score_4th)',
            'str_guest_score_4th=VALUES(str_guest_score_4th)',
            'str_home_score_5th=VALUES(str_home_score_5th)',
            'str_guest_score_5th=VALUES(str_guest_score_5th)',
            'str_home_score_handicap=VALUES(str_home_score_handicap)',
            'str_guest_score_handicap=VALUES(str_guest_score_handicap)',
            'str_home_score_ou=VALUES(str_home_score_ou)',
            'str_guest_score_ou=VALUES(str_guest_score_ou)',
            'str_begin_time=VALUES(str_begin_time)',
            'str_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        $resultsModel = Loader::model('SportsTennisResults');

        //执行sql
        $table = $resultsModel->getTable();
        $sql = "INSERT INTO {$table} ({$fields}) VALUES {$valueStr} ON DUPLICATE KEY UPDATE {$update}";
        $ret = $resultsModel->execute($sql);
        if ($ret === false) {
            return false;
        }
        return true;
    }
}