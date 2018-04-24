<?php

namespace app\common\model;

use think\Model;
use think\Log;

class Channel extends Model
{

    public $pk = 'id';

    public function getChannelId($channelName)
    {
        $channelInfo = $this->getInfoByChannelName($channelName);
        if ($channelInfo) {
            $channelId = $channelInfo['id'];
        } else {
            $channelId = $this->save(['channel_name' => $channelName, 'create_time' => current_datetime()]);
        }
        Log::write('channel_id:'. $channelId);
        return $channelId;
    }


    public function getInfoByChannelName($channelName)
    {
        $condition = [
            'channel_name' => $channelName,
        ];

        return $this->where($condition)->find();
    }

}