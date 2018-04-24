<?php
/**
 * 足球订单处理
 * @createTime 2017/5/27 10:08
 */

namespace app\clearing\football;

use think\Config;
use think\Loader;
use think\Db;
use think\Cache;

class Orders {
    /**
     * 验证足球的滚球订单
     * @param $order
     * @return bool
     */
    public function checkInPlayNowOrder($order) {
        $betTime = strtotime($order['so_create_time']);
        $confirmTime = Config::get('common.dangerous_ball_confirm_time')['football'];

        //还没到危险球的确认时间
        if (time() - $betTime < $confirmTime) {
            return false;
        }

        //获取对阵信息
        $scheduleId = $order['so_source_ids'];
        if (!is_numeric($scheduleId)) {
            return false;
        }
        $scheduleInfo = Loader::model('Schedules', 'football')->getInPlayNowInfoById($scheduleId);
        $betInfo = json_decode($order['so_bet_info'], true)[0];

        if (!isset($betInfo['home_score']) || !isset($betInfo['guest_score']) ||
            !isset($betInfo['home_red']) || !isset($betInfo['guest_red'])) {
            return false;
        }

        //进球数不一致，审核不通过，等待撤票
        if ($betInfo['home_score']  != $scheduleInfo['sfs_home_score'] ||
            $betInfo['guest_score'] != $scheduleInfo['sfs_guest_score']) {

            $checkStatus = Config::get('status.order_check_status')['system_no'];
            $status = Config::get('status.order_status')['wait_cancel'];
            Loader::model('Orders', 'logic')->updateCheckStatusById($order['so_id'], $checkStatus, $status, '进球无效');
            return true;
        }

        //红牌数不一致，审核不通过，等待撤票
        if ($betInfo['home_red'] != $scheduleInfo['sfs_home_red'] ||
            $betInfo['guest_red'] != $scheduleInfo['sfs_guest_red']) {

            $checkStatus = Config::get('status.order_check_status')['system_no'];
            $status = Config::get('status.order_status')['wait_cancel'];
            Loader::model('Orders', 'logic')->updateCheckStatusById($order['so_id'], $checkStatus, $status, '红牌无效');
            return true;
        }

        //审核通过，等待开奖
        $checkStatus = Config::get('status.order_check_status')['yes'];
        $status = Config::get('status.order_status')['wait'];
        Loader::model('Orders', 'logic')->updateCheckStatusById($order['so_id'], $checkStatus, $status);
        return true;
    }
}