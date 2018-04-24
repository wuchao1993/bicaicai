<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;

class ChannelReport extends Model
{
    public $errorcode = EC_SUCCESS;

    public function getChannelReportList($params)
    {
        // $limit = (($params['page']<=0) ? 0 : ($params['page']-1))*$params['num'] . "," .$params['num'];
        $condition = [];

        // if($params['channelName'] != '') {
        //     $condition['c.channel_name'] = ['LIKE','%'.$params['channelName'].'%'];
        // }
        
        $condition['u.user_createtime'] = ['between time',[$params['start_date'], $params['end_date'] ] ];

        $count = Loader::model('Channel')->alias('c')->join('User u', 'c.id=u.channel_id', 'LEFT')->where($condition)->group('c.id')->count();
        $list = array();
        if ($count > 0) {
            $list = Loader::model('Channel')->alias('c')->join('User u', 'c.id=u.channel_id', 'LEFT')->where($condition)->group('c.id')->column('c.id, c.channel_name,c.create_time, u.user_createtime,count(u.user_name) as reg_persons');
            $list = array_merge($list);
        }

        //没有注册人数的渠道也要展示
        $allChannel = Loader::model('Channel')->column('create_time, channel_name');
        $allChannel = array_merge($allChannel);

        $nowChannel = array_column( $list, 'channel_name');
        $diffChannel = array_diff($allChannel, $nowChannel);
        if ($diffChannel) {
            foreach ($diffChannel as $key => $value) {
               $list[] = ['id'=>'', 'channel_name'=>$value, 'create_time'=>$key, 'reg_persons'=> 0 ]; 
            }
        }
        
        return [
            'list' =>  $list,
            'count' => $count
        ];
    }

}