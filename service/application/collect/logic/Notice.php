<?php
/**
 * 足球公告业务逻辑
 * @createTime 2017/5/1 10:21
 */

namespace app\collect\logic;

use think\Config;
use think\Loader;

class Notice {

    /**
     * 采集数据入库
     * @param $date
     * @return bool
     */
    public function collect($date) {
        //获取采集数据
        $data = Loader::model('Notice', 'service')->collect($date);

        if (!$data) {
            return false;
        }
        
        $collectNoticeModel = Loader::model('SportsCollectNotice');
        $noticeModel = Loader::model('Notice');
        foreach($data as $item) {
            $count = $collectNoticeModel->where(['scn_id' => $item['sfn_id']])->count();

            if ($count > 0) {
                $update = [
                    'scn_content'     => $item['sfn_notice'],
                    'scn_date'        => $item['sfn_date'],
                    'scn_modify_time' => date('Y-m-d H:i:s'),
                ];
                $collectNoticeModel->where(['scn_id' => $item['sfn_id']])->update($update);
            } else {
                //插入采集公告表
                $insert = [
                    'scn_id'          => $item['sfn_id'],
                    'scn_content'     => $item['sfn_notice'],
                    'scn_date'        => $item['sfn_date'],
                    'scn_create_time' => date('Y-m-d H:i:s'),
                    'scn_modify_time' => date('Y-m-d H:i:s'),
                ];
                $collectNoticeModel->insert($insert);

                //插入公告表
                $insert = [
                    'notice_lottery_type' => Config::get('status.notice_lottery_type')['sports'],
                    'notice_type'         => Config::get('status.notice_type')['sports_event'],
                    'notice_content'      => $item['sfn_notice'],
                    'notice_createtime'   => date('Y-m-d H:i:s'),
                ];
                $noticeModel->insert($insert);
            }
        }

        return true;
    }
}