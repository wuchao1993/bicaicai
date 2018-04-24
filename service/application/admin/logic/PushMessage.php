<?php

/**
 * 推送内容相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Loader;
use think\Model;
use upush\UPush;
use think\Config;
class PushMessage extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取咨询列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {
        $pushMessageModel = Loader::model('PushMessage');
        $condition = [];
        // 获取总条数
        $count = $pushMessageModel->where($condition)->count();

        $list = $pushMessageModel->where($condition)->order('pm_id desc')->limit($params ['num'])->page($params ['page'])->select();

        //获取推送渠道
        $pushChannel = collection(Loader::model('PushChannel')->select())->toArray();
        $pushChannel = reindex_array($pushChannel,'pc_app_key');

        $list = collection($list)->toArray();
        foreach ($list as $key => $val){
            $list[$key]['pm_type'] = Config::get('status.pm_type')[$val['pm_type']];
            $list[$key]['pm_channel'] = $val['pm_type'] == 1 ? Config::get('status.pm_type2')[$val['pm_type']].$pushChannel[$val['pm_app_key']]['pc_app_name'] :
                Config::get('status.pm_type2')[$val['pm_type']].json_decode($val['pm_extra'],true)['user_name'];
        }

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 获取咨询信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($id) {
        $condition = [
            'pm_id' => $id,
        ];
        $info      = Loader::model('PushMessage')->where($condition)->find()->toArray();
        
        //获取推送渠道
        $pushChannel = collection(Loader::model('PushChannel')->select())->toArray();
        $pushChannel = reindex_array($pushChannel,'pc_app_key');

        $info['pm_channel'] = $info['pm_type'] == 1 ? Config::get('status.pm_type2')[$info['pm_type']].$pushChannel[$info['pm_app_key']]['pc_app_name'] :
            Config::get('status.pm_type2')[$info['pm_type']].json_decode($info['pm_extra'],true)['user_name'];
      
        $info['pc_id'] = $info['pm_type'] == 1 ? $pushChannel[$info['pm_app_key']]['pc_id'] : '';

        return $info;
    }

    /**
     * 新增
     *
     * @param
     *            $params
     * @return bool
     */
    public function addPushMessage($params) {

        // 入库
        $data ['pm_title']      = $params ['pm_title'];
        $data ['pm_type']       = $params ['pm_type'];
        $data ['pm_content']    = $params ['pm_content'];
        $data ['pm_createtime'] = date('Y-m-d H:i:s');

        if(isset ($params ['push_channel_id'])) {
            if($params ['push_channel_id'] == 'all') {
                $appKeyArray = collection(Loader::model('PushChannel')->select())->toArray();
            }else {
                $appKeyArray[0]['pc_app_key'] = Loader::model('PushChannel', 'logic')->getAppKey($params ['push_channel_id']);
            }
        }else {
            $appKeyArray = [];
        }

        if(isset ($params ['pm_extra'])) {
            $data ['pm_extra'] = $params ['pm_extra'];
        }

        $pushMessageModel = Loader::model('PushMessage');

        if(!empty($appKeyArray)) {
            //失败的渠道名称
            $channelPushError = [];

            foreach($appKeyArray as $val) {
                $data ['pm_app_key'] = $val['pc_app_key'];

                $ret = $pushMessageModel->insert($data);

                if($ret) {
                    $id = $pushMessageModel->getLastInsID();

                    // 是否推送
                    if($params ['add_type'] == 2) {
                        $result = $this->report($id);

                        if(!$result) {
                            if ( $params ['push_channel_id'] == 'all' ) {
                                $channelPushError[$val['pc_id']] = $val['pc_app_name'];
                            }else{
                                $this->errorcode = EC_AD_PUSH_ERROR;
                            }
                        }
                    }
                }
            }

            if (empty($channelPushError) ) {
                return;
            }elseif ( count($appKeyArray) != count($channelPushError) ) {
                $this->errorcode = EC_AD_PUSH_PART_ERROR;
                return $channelPushError;
            }else{
                $this->errorcode = EC_AD_PUSH_ERROR;
                return $channelPushError;
            }
        
        }elseif (isset ($params ['pm_extra']) ) {
            $ret = $pushMessageModel->insert($data);

            if($ret) {
                $id = $pushMessageModel->getLastInsID();

                // 是否推送
                if($params ['add_type'] == 2) {
                    $result = $this->report($id);

                    if(!$result) {
                        $this->errorcode = EC_AD_PUSH_ERROR;

                        return false;
                    }
                }
            }

            if($ret) {
                $pushMessageInfo = [
                    'id' => $id,
                ];

                return $pushMessageInfo;
            }else {
                $this->errorcode = EC_AD_ADD_PUSH_MESSAGE_ERROR;

                return false;
            }
        }else {
            $this->errorcode = EC_AD_ADD_PUSH_MESSAGE_ERROR;

            return false;
        }

    }

    /**
     * 编辑
     *
     * @param
     *            $params
     * @return array
     */
    public function editPushMessage($params) {

        // 修改咨询信息
        $data ['pm_title']      = $params ['pm_title'];
        $data ['pm_type']       = $params ['pm_type'];
        $data ['pm_content']    = $params ['pm_content'];
        $data ['pm_modifytime'] = date('Y-m-d H:i:s');

        if(isset ($params ['push_channel_id'])) {
            $data ['pm_app_key'] = Loader::model('PushChannel', 'logic')->getAppKey($params ['push_channel_id']);
        }
        if(isset ($params ['pm_extra'])) {
            $data ['pm_extra'] = $params ['pm_extra'];
        }

        Loader::model('PushMessage')->save($data, [
            'pm_id' => $params ['id'],
        ]);

        // 是否推送
        if($params ['add_type'] == 2) {
            $result = $this->report($params ['id']);

            if(!$result) {
                $this->errorcode = EC_AD_PUSH_ERROR;

                return false;
            }
        }

        return true;
    }

    /**
     * 删除
     *
     * @param
     *            $params
     * @return array
     */
    public function del($params) {
        $ret = Loader::model('PushMessage')->where([
            'pm_id' => $params ['id'],
        ])->delete();

        return $ret;
    }


    /**
     * 消息推送
     * @param $pm_id
     * @return bool
     */
    public function report($pm_id) {
        if($pm_id) {
            $pm_info = $this->getInfo($pm_id);
            unset($pm_info['pm_channel']);
            unset($pm_info['pc_id']);
            $type             = $pm_info ['pm_type'];
            $push_device_info = null;
            if($type == 2) {
                // 个推
                $pm_extra  = json_decode($pm_info ['pm_extra'], true);
                $user_name = $pm_extra ['user_name'];
                if($user_name) {
                    $push_device_info  = Loader::model('PushDevice', 'logic')->getInfoByUserName($user_name);
                    $push_channel_info = Loader::model('PushChannel', 'logic')->getInfoByAppKey($push_device_info ['pd_app_key']);
                    $platform = $push_channel_info ['pc_platform'];
                    if (empty($platform) ) {
                        return false;
                    }
                    $UPush    = new UPush ($push_channel_info ['pc_app_key'], $push_channel_info ['pc_app_master_secret']);
                    $result   = $this->_sendUnitCast($UPush, $pm_info, $push_device_info ['pd_token'], $platform);
                    if($result ['ret'] == "FAIL") {
                        return false;
                    }
                }
            } else {
                // 群推
                $pm_app_key        = $pm_info ['pm_app_key'];
                $push_channel_info = Loader::model('PushChannel', 'logic')->getInfoByAppKey($pm_app_key);
                $platform = $push_channel_info ['pc_platform'];
                $UPush    = new UPush ($push_channel_info ['pc_app_key'], $push_channel_info ['pc_app_master_secret']);
                if($platform == 1) {
                    $result = $UPush->sendIOSBroadcast($pm_info);
                } else {
                    $result = $UPush->sendAndroidBroadcast($pm_info);
                }

                $result = json_decode($result, true);
                if($result ['ret'] == 'FAIL') {
                    return false;
                    // $has_token = true;
                    // $offset    = 0;
                    // $count     = 500;
                    // while($has_token) {
                    //     $push_device_list = Loader::model('PushDevice', 'logic')->getListByAppKey($pm_app_key, $offset, $count);
                    //     $tokens           = array_filter(extract_array($push_device_list, 'pd_token'));
                    //     if($tokens) {
                    //         $result = $this->_sendListCast($UPush, $pm_info, $tokens, $platform);
                    //         if($result === false) {
                    //             return false;
                    //         }
                    //         $offset = $count;
                    //         $count  += 500;
                    //     } else {
                    //         $has_token = false;
                    //     }
                    // }
                }
            }

            $data['pm_status'] = 1;
            $data['pm_modifytime'] = current_datetime();
            Loader::model('PushMessage')->where('pm_id', $pm_info['pm_id'])->update($data);

            return true;
        }
    }

    private function _sendListCast(UPush & $UPush, $pm_info, $tokens, $platform) {
        if($platform == 1) {
            $result = $UPush->sendIOSListcast($pm_info, $tokens);
        } else if($platform == 2) {
            $result = $UPush->sendAndroidListcast($pm_info, $tokens);
        }
        $result = json_decode($result, true);
        if($result ['ret'] == 'FAIL' || $result === false) {
            return false;
        }
    }

    private function _sendUnitCast(UPush & $UPush, $pm_info, $token, $platform) {
        if($platform == 1) {
            $result = $UPush->sendIOSUnicast($pm_info, $token);
        } else if($platform == 2) {
            $result = $UPush->sendAndroidUnicast($pm_info, $token);
        }
        $result = json_decode($result, true);

        return $result;
    }
}