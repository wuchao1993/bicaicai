<?php
/**
 * 获取冠军赛果数据
 * @createTime 2017/5/03 11:16
 */

namespace app\collect\tennis;

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
        $data = Loader::model('Tennis', 'service')->collectResultsOutright($date);
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
        $outrightModel = Loader::model('SportsTennisOutright');
        $table = $outrightModel->getTable();
        $sql = "UPDATE {$table} SET `sto_result`= CASE `sto_game_id` ";
        $gameIdStr = '';
        foreach($data as $item) {
            $item['stc_result'] = json_encode($item['stc_result'], JSON_UNESCAPED_UNICODE);
            $sql .= "WHEN {$item['stc_id']} THEN '{$item['stc_result']}' ";
            $gameIdStr .= $item['stc_id'] . ',';
        }
        $gameIdStr = trim($gameIdStr, ',');
        $sql .= "END WHERE `sto_game_id` IN ({$gameIdStr})";

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
            'sto_result' => '',
            'sto_end_time' => ['LT', date('Y-m-d') . ' 00:00:00'],
        ];
        $data = Loader::model('SportsTennisOutright')
            ->where($where)
            ->field('LEFT(sto_end_time,10) AS end_time')
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