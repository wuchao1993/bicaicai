<?php

namespace app\admin\logic;

use think\Loader;
use think\Model;

class PayAccount extends Model {
    public $errorcode = EC_SUCCESS;
    public function getList($page = 1, $num = 10, $status = '', $ul_id = 0) {
        $condition['pa_status'] = ['EGT',0];
        if ($status != '') {
            $condition ['pa_status'] = $status;
        }
        
        if ($ul_id > 0) {
            $pay_account_ids = Loader::model ( 'PayAccountUserLevelRelation', 'logic' )->getPayAccountIds ( $ul_id );
            if ($pay_account_ids) {
                $condition ['pa_id'] = array (
                        'IN',
                        $pay_account_ids 
                );
            } else {
                $condition ['pa_id'] = 0;
            }
        }
        
        $limit = (($page <= 0) ? 0 : ($page - 1)) * $num . "," . $num;
        $list = Loader::model ( 'PayAccount' )->alias ( 'pa' )->join ( 'Bank b', 'b.bank_id=pa.bank_id', 'LEFT' )->field ( 'pa.*, b.bank_name' )->where ( $condition )->limit ( $limit )->order('pa_createtime desc')->select ();
        $count = Loader::model ( 'PayAccount' )->where ( $condition )->count ();
        
        if (! empty ( $list )) {
            foreach ( $list as &$val ) {
                $condition = [ ];
                $condition ['pau.pay_account_id'] = $val ['pa_id'];
                $levelList = Loader::model ( 'PayAccountUserLevelRelation' )->alias ( 'pau' )->join ( 'UserLevel ul', 'ul.ul_id=pau.user_level_id', 'LEFT' )->field ( 'ul.ul_id as id,ul.ul_name as name' )->where ( $condition )->select ();
                $val ['levelList'] = $levelList;
            }
        }
        
        return [ 
                'list' => $list,
                'count' => $count 
        ];
    }
    public function getInfo($id) {
        $condition ['pa_id'] = $id;
        $info = Loader::model ( 'PayAccount' )->where ( $condition )->find ();
        if ($info) {
            return $info->toArray ();
        } else {
        }
    }
    public function editInfo($info, $ulId) {

        if (isset ( $info ['pa_id'] )) {
            $actionData = $this->getInfo($info ['pa_id']);

            $result = Loader::model ( 'PayAccount' )->where ( [ 
                    'pa_id' => $info ['pa_id'] 
            ] )->update ( $info );
            $resultId = $info ['pa_id'];
        } else {
            $resultId = Loader::model ( 'PayAccount' )->insertGetId ( $info );
            $actionData = $this->getInfo($resultId);
        }
        
        Loader::model ( 'PayAccountUserLevelRelation' )->where ( [ 
                'pay_account_id' => $resultId 
        ] )->delete ();
        
        if (! empty ( $ulId )) {
            
            foreach ( $ulId as $val ) {
                $data = [ ];
                $data ['pay_account_id'] = $resultId;
                $data ['user_level_id'] = $val;
                $result = Loader::model ( 'PayAccountUserLevelRelation' )->insert ( $data, true );
            }
        }

        if(isset($info ['pa_id'])){
            $action_name = "update_pay_account";
        }else{
            $action_name = "add_pay_account";
        }

        $actionData['_change_'] = json_encode($info);
        Loader::model('General', 'logic')->actionLog($action_name, 'PayAccount' ,$resultId , MEMBER_ID, json_encode($actionData));
        
        return $result;
    }
    
    /**
     * 修改状态
     * 
     * @param
     *            $params
     * @return array
     */
    public function changeStatus($params) {

        $updateData ['pa_status'] = $params ['pa_status'];

        $actionData = Loader::model('General', 'logic')->getActionData($params ['pa_id'],$updateData);

        Loader::model ( 'PayAccount' )->save ( $updateData, [ 
                'pa_id' => $params ['pa_id'] 
        ] );

        Loader::model('General', 'logic')->actionLog('change_status_pay_account', 'PayAccount' ,$params ['pa_id'] , MEMBER_ID, json_encode($actionData));
        
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

        $data['pa_status'] = config("status.pay_account_status")['del'];

        $actionData = Loader::model('General', 'logic')->getActionData($params ['pa_id'],$data);

        $ret = Loader::model ( 'PayAccount' )->save($data,['pa_id' => $params ['pa_id']] );

        Loader::model('General', 'logic')->actionLog('delete_pay_account', 'PayAccount' ,$params ['pa_id'] , MEMBER_ID, json_encode($actionData));
        return $ret;
    }
}