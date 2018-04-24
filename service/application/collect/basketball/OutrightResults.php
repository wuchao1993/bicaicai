<?php
/**
 * 获取冠军赛果数据
 * @createTime 2017/5/03 11:16
 */

namespace app\collect\basketball;

use think\Loader;
use think\Model;

class OutrightResults extends Model {

    /**
     * 采集数据入库
     * @param string $date
     * @return bool
     */
    public function collect($date = '') {
        //获取采集数据
        $data = Loader::model('Basketball', 'service')->collectResultsOutright($date);
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
        $outrightModel = Loader::model('SportsBasketballOutright');
        $table = $outrightModel->getTable();
        $sql = "UPDATE {$table} SET `sbo_result`= CASE `sbo_game_id` ";
        $gameIdStr = '';
        foreach($data as $item) {
            $item['sbc_result'] = json_encode($item['sbc_result'], JSON_UNESCAPED_UNICODE);
            $sql .= "WHEN {$item['sbc_id']} THEN '{$item['sbc_result']}' ";
            $gameIdStr .= $item['sbc_id'] . ',';
        }
        $gameIdStr = trim($gameIdStr, ',');
        $sql .= "END WHERE `sbo_game_id` IN ({$gameIdStr})";

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
            'sbo_result' => '',
            'sbo_end_time' => ['LT', date('Y-m-d') . ' 00:00:00'],
        ];
        $data = Loader::model('SportsBasketballOutright')
            ->where($where)
            ->field('LEFT(sbo_end_time,10) AS end_time')
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