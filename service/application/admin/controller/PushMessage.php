<?php

/**
 * 推送内容控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class PushMessage {

    /**
     * 获取推送内容列表
     * @param Request $request
     * @return array
     */
    public function getPushMessageList(Request $request) {
        $params ['page'] = $request->param('page',1);
        $params ['num']  = $request->param('num',10);

        $pushMessageLogic = Loader::model('PushMessage', 'logic');
        $pushMessageList  = $pushMessageLogic->getList($params);

        foreach($pushMessageList ['list'] as &$info) {
            $info = $this->_packPushMessageInfo($info);
        }

        return [
            'errorcode' => $pushMessageLogic->errorcode,
            'message'   => Config::get('errorcode') [$pushMessageLogic->errorcode],
            'data'      => output_format($pushMessageList),
        ];
    }


    /**
     * 获取推送内容信息
     * @param Request $request
     * @return array
     */
    public function getPushMessageInfo(Request $request) {
        $id = $request->param('id');

        $pushMessageLogic = Loader::model('pushMessage', 'logic');
        $pushMessageInfo  = $pushMessageLogic->getInfo($id);
        $pushMessageInfo  = $this->_packPushMessageInfo($pushMessageInfo);

        return [
            'errorcode' => $pushMessageLogic->errorcode,
            'message'   => Config::get('errorcode') [$pushMessageLogic->errorcode],
            'data'      => output_format($pushMessageInfo),
        ];
    }


    /**
     * 新增推送内容
     * @param Request $request
     * @return array
     */
    public function addPushMessage(Request $request) {
        $params ['pm_title']   = $request->param('title');
        $params ['pm_type']    = $request->param('type');
        $params ['pm_content'] = $request->param('content');
        $params ['add_type']   = $request->param('addType');
        $params ['username']   = $request->param('username');

        if(!empty($request->param('appKey'))) {
            $params ['push_channel_id'] = $request->param('appKey');
        }
        if(!empty( $params ['username'] ) ) {
            $params ['pm_extra'] = json_encode([
                'user_name' => $params ['username'],
            ]);
        }

        $pushMessageLogic = Loader::model('pushMessage', 'logic');
        $pushMessageInfo  = $pushMessageLogic->addPushMessage($params);

        return [
            'errorcode' => $pushMessageLogic->errorcode,
            'message'   => Config::get('errorcode') [$pushMessageLogic->errorcode],
            'data'      => output_format($pushMessageInfo),
        ];
    }


    /**
     * 编辑推送内容
     * @param Request $request
     * @return array
     */
    public function editPushMessage(Request $request) {
        $params ['id']         = $request->param('id');
        $params ['pm_title']   = $request->param('title');
        $params ['pm_type']    = $request->param('type');
        $params ['pm_content'] = $request->param('content');
        $params ['add_type']   = $request->param('addType');
        $params ['username']   = $request->param('username');

        if(!empty($request->param('appKey'))) {
            $params ['push_channel_id'] = $request->param('appKey');
        }

        if(!empty( $params ['username'] ) ) {
            $params ['pm_extra'] = json_encode([
                'user_name' => $params ['username'],
            ]);
        }

        $pushMessageLogic = Loader::model('pushMessage', 'logic');
        $result           = $pushMessageLogic->editPushMessage($params);

        return [
            'errorcode' => $pushMessageLogic->errorcode,
            'message'   => Config::get('errorcode') [$pushMessageLogic->errorcode],
            'data'      => $result,
        ];
    }


    /**
     * 删除推送内容
     * @param Request $request
     * @return array
     */
    public function delPushMessage(Request $request) {
        $params ['id'] = $request->param('id');

        $pushMessageLogic = Loader::model('pushMessage', 'logic');
        $result           = $pushMessageLogic->del($params);

        return [
            'errorcode' => $pushMessageLogic->errorcode,
            'message'   => Config::get('errorcode') [$pushMessageLogic->errorcode],
            'data'      => $result,
        ];
    }


    /**
     * 获取推送内容类型
     * @param Request $request
     * @return array
     */
    public function getTypeList(Request $request) {
        $noteLogic = Loader::model('pushMessage', 'logic');

        $data = [];
        $i    = 0;
        foreach(Config::get('status.pushmessage_type_name') as $key => $val) {
            $data [$i] = [
                'id'   => $key,
                'name' => $val,
            ];
            $i++;
        }

        return [
            'errorcode' => $noteLogic->errorcode,
            'message'   => Config::get('errorcode') [$noteLogic->errorcode],
            'data'      => output_format($data),
        ];
    }


    private function _packPushMessageInfo($info) {
        $pcId = array_key_exists('pc_id', $info) ? $info ['pc_id'] : '';
        return [
            'id'         => $info ['pm_id'],
            'type'       => $info ['pm_type'],
            'channel'    => $info ['pm_channel'],
            'title'      => $info ['pm_title'],
            'content'    => $info ['pm_content'],
            'extra'      => $info ['pm_extra'],
            'createtime' => $info ['pm_createtime'],
            'modifytime' => $info ['pm_modifytime'],
            'status'     => $info ['pm_status'],
            'channelId'  => $pcId,
        ];
    }
}
