<?php
/**
 * 获取冠军赛果数据
 * @createTime 2017/5/03 11:16
 */

namespace app\collect\football;

use think\Loader;

class OutrightResults {

    /**
     * 采集数据入库
     * @param string $date
     * @return bool
     */
    public function collect($date = '') {
        //获取采集数据
        $data = Loader::model('Football', 'service')->collectResultsOutright($date);
        if (!$data) {
            return false;
        }

        $this->updateResult($data);
    }

    /**
     * 更新
     * @param $data
     * @return mixed
     */
    public function updateResult($data) {
        $outrightModel = Loader::model('SportsFootballOutright');
        $table = $outrightModel->getTable();
        $sql = "UPDATE {$table} SET `sfo_result`= CASE `sfo_game_id` ";
        $gameIdStr = '';
        foreach($data as $item) {
            $item['sfc_result'] = json_encode($item['sfc_result'], JSON_UNESCAPED_UNICODE);
            $sql .= "WHEN {$item['sfc_id']} THEN '{$item['sfc_result']}' ";
            $gameIdStr .= $item['sfc_id'] . ',';
        }
        $gameIdStr = trim($gameIdStr, ',');
        $sql .= "END WHERE `sfo_game_id` IN ({$gameIdStr})";

        $ret = $outrightModel->execute($sql);
        if ($ret === false) {
            return false;
        }
        return true;
    }

    /**
     * 修复一些当天没有出赛果的比赛
     * @return string
     */
    public function repair() {
        $where = [
            'sfo_result' => '',
            'sfo_end_time' => ['LT', date('Y-m-d') . ' 00:00:00'],
        ];
        $data = Loader::model('SportsFootballOutright')
            ->where($where)
            ->field('LEFT(sfo_end_time,10) AS end_time')
            ->group('end_time')
            ->select();

        if ($data) {
            foreach($data as $key => $item) {
                $this->collect($item->end_time);
            }
        }
        return 'success';
    }
}