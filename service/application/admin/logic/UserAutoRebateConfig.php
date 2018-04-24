<?php
/**
 * 用户返点
 * User: jesse
 * Date: 2017/8/9
 */

namespace app\admin\logic;


class UserAutoRebateConfig extends Common
{

    public function getRebate($userId){

        $agentRebateModel = $this->getModel("UserAutoRebateConfig");

        return $agentRebateModel->where(['user_id'=>$userId,'lottery_category_id'=>['neq',PC_EGG_CATEGORY_ID]])->field("user_id,lottery_category_id as category_id,user_rebate as rebate")->select();
    }


    public function getListByPid($userId){
        $condition = [];
        $condition['user_pid'] = $userId;
        return $this->where($condition)->select();
    }


    public function addUserRebateConfig($rebateConfig,$userId,$userPid){

        $rebateData = [];

        foreach($rebateConfig as $categoryId => $rebate){

            $temp = [];

            $temp['user_id']                =  $userId;
            $temp['user_pid']               =  $userPid;
            $temp['lottery_category_id']    =  $categoryId;
            $temp['user_rebate']            =  $rebate;

            $rebateData[]  = $temp;

        }

        return  $this->insertAll($rebateData,true);

    }

    public function getUserRebateByUserId($userId){

        $condition = [];
        $condition['user_id']               = $userId;
        $condition['lottery_category_id']   = array("not in",array(PC_EGG_CATEGORY_ID,LHC_CATEGORY_ID));

        return $this->where($condition)->column('lottery_category_id,user_rebate');
    }

}