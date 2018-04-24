<?php

/**
 * 日志类
 * @createTime 2017/12/4
 */

namespace logger;

use think\Config;

class Logger {

    /**
     * 写日志
     * @param string $data 数据
     * @param string $path 存放路径，如为空则默认为行为日志存放路径
     * @param string $filename 文件名，如为空则默认为行为日志文件名
     * @return bool
     */
    public function write($data, $path = '', $filename = '') {

        $path     = !empty($path) ? $path : Config::get('action_log_path');
        $filename = !empty($filename) ? $filename : 'action_log.log';

        $filePath = $path . '/' . $filename;

        if(!empty($data)) {

            //行为日志处理
            if(isset($data['record_detail'])) {
                $data['record_detail'] = json_decode($data['record_detail'], true);
                if(isset($data['record_detail']['_change_'])) {
                    $data['record_detail']['_change_'] = json_decode($data['record_detail']['_change_'], true);
                }
            } else {
                $data['record_detail'] = [];
            }

            //重新封装数据
            $result = array(
                'time'    => date('Y-m-d H:i:s', $data['create_time']),
                'ip'      => long2ip($data['action_ip']),
                'user_id' => $data['user_id'],
                'model'   => $data['model'],
                'data'    => $data['record_detail'],
            );

            //记录日记文件给ELK查询
            @file_put_contents($filePath, json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return true;
    }
}