<?php

namespace app\common\model;

use think\Config;
use think\Model;
use think\Env;

class AgentLink extends Model
{

    public $pk = 'agl_id';

    public function getInfo($code)
    {
        $condition = [
            'agl_code' => $code,
        ];

        return $this->where($condition)->find();
    }


    public function getInfoByCode($code, $type = 0)
    {
        $info = $this->getInfo($code);
        if ($type > 0 && $info['agl_user_type'] != $type) {
            return false;
        }
        if ($info['agl_status'] != Config::get('status.agent_link_status')['enable'] ||
            ($info['agl_endtime'] > 0 && $info['agl_endtime'] < current_datetime()) ||
            $info['agl_use_count'] <= 0) {
            return false;
        }
        return $info;
    }


    public function decreaseUseCount($id)
    {
        $condition = [
            'agl_id' => $id
        ];

        return $this->where($condition)->setDec('agl_use_count');
    }

    /***
     * @desc 转换编辑邀请码字段信息
     * @param $agentLinkParam
     * @return mixed
     */
    public function getEditQrcodeCondition($agentLinkParam)
    {
        $agentLinkData['agl_use_count'] = $agentLinkParam['count'];
        $agentLinkData['agl_endtime']   = $agentLinkParam['endtime'];
        $agentLinkData['agl_status']    = $agentLinkParam['status'];
        return $agentLinkData;
    }

    /***
     * @desc 获取邀请码信息
     * @param $limit
     * @return mixed
     */
    public function buildEnableAgentLinks()
    {
        $field = ["agl_id"=>"id",
                  "agl_code" => "code",
                  "agl_user_type" => "userType",
                  "agl_use_count" => "userCount",
                  "agl_qrcode_url" => "qrcode",
                  "agl_status"  => "status",
            ];
        $where['agl_status'] = Config::get("qrcode.agl_status")['enable'];
        $where['user_id'] = USER_ID;
        $where['agl_user_type'] = Config::get("qrcode.user_play_type")['player'];
        $AgentLinksData = $this->field($field)->where($where)->select();

        foreach ($AgentLinksData as &$value){
            $value['qrcode'] = Env::get('oss.sports_url').$value['qrcode'];
        }
        return $AgentLinksData;
    }


}