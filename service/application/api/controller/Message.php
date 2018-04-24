<?php
/**
 * 用户账户
 * @createTime 2017/4/25 11:26
 */

namespace app\api\controller;

use app\common\model\RechargeTypeGroup;
use think\Config;
use think\Request;
use think\Loader;
use think\Hook;

class Message {
     /**
     * 用户站内信列表
     * @return array
     */
    public function getMessageList(Request $request){
        Hook::listen('auth_check');
        $messageLogic   = Loader::model('Message', 'logic');
        $params['page'] = $request->post('page', 0);
        $messageData    = $messageLogic->getMessageList($params);
        return return_result($messageLogic->errorcode, output_format($messageData));
    }

    /**
     * 用户站内信详情信息
     * @return array
     */
    public function getMessageInfo(Request $request){
        Hook::listen('auth_check');
        $messageLogic = Loader::model('Message','logic');
        $messageId    = $request->post('messageId');
        $messageData  = $messageLogic->getMessageInfo($messageId);
        return return_result($messageLogic->errorcode,output_format($messageData));
    }

    /**
     * 用户删除站内信信息
     */
    public function deleteMessageInfo(Request $request){
        Hook::listen('auth_check');
        $messageLogic = Loader::model('message','logic');
        $messageId    = $request->post('messageId');
        $messageData  = $messageLogic->deleteMessageInfo($messageId);
        return return_result($messageLogic->errorcode,output_format($messageData));
    }
}