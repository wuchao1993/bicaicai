<?php
/**
 * 代理域名返点业务模型
 * User: jesse
 * Date: 2017/8/7
 * Time: 16:46
 */

namespace app\admin\logic;
use think\Controller;
use think\Loader;

class AgentDomainRebate extends Common
{
    Public $fieldMapConf = [
            'agd_id'    => 'id'
    ];

    public function getRebate($id,$userId){
        if(empty($id)||empty($userId)) return false;

        $condition = ['agd_id' => $id];

        $count = $this->where ( $condition )->count();
        if($count>0){
            $list = $this->where ( $condition )->select();
            $list = $list?collection($list)->toArray():[];
            $list = reindex_array($list,'category_id');
        }


        $lotteryCategoryLogic = Loader::model('LotteryCategory', 'logic');
        $categoryList         = $lotteryCategoryLogic->getCategoryList();
        $categoryList = $categoryList?collection($categoryList)->toArray():[];
        $categoryList = reindex_array($categoryList,'lottery_category_id');


        $agent_rebate = Loader::model ("UserAutoRebateConfig","logic")->getRebate($userId);
        $agent_rebate = $agent_rebate?collection($agent_rebate)->toArray():[];
        if(!empty($agent_rebate)){
            $data = [];
            foreach ($agent_rebate as $info){
                $tmp = [];
                $tmp['id']          = $id;
                $tmp['user_id']     = $info['user_id'];
                $tmp['category_id'] = $info['category_id'];
                $tmp['max_rebate']  = $info['rebate'];
                $tmp['rebate']      = $list[$info['category_id']]['rebate'];
                $tmp['category_name'] = $categoryList[$info['category_id']]['lottery_category_name'];

                $data[] = $tmp;
            }
        }

        return $data;
    }


    public function edit($params){
        $id         = $params['id'];
        $userId     = $params['uid'];
        $rebate_conf= $params['rebate_conf'];

        if(!empty($rebate_conf)){

            $data = [];
            foreach ($rebate_conf as $item){
                $tmp = [];
                $tmp['agd_id']        = $id;
                $tmp['user_id']       = $userId;
                $tmp['category_id']   = $item['categoryId'];
                $tmp['rebate']        = $item['rebate'];

                $data[] = $tmp;
            }

            $res = $this->insertAll($data,true);

            if($res == false && !empty($this->getError())){
                $this->errorCode = EC_AD_UPDATE_ERROR;
                return false;
            }
        }

        return true;
    }


    public function addRebate($id,$userId){

        if(empty($id)||empty($userId)) return false;

        //未设置，默认代理的返点
        $list = Loader::model ("UserAutoRebateConfig","logic")->getRebate($userId);
        $list = $list?collection($list)->toArray():[];
        if(!empty($list)){
            $this->startTrans();
            foreach ($list as &$info){
                $info['agd_id'] = $id;
                if($this->create($info) == false ){
                    $this->rollback();
                    $this->errorCode = EC_AD_ADD_ERROR;
                    return false;

                }
            }
        }

        $this->commit();
        return true;
    }



    public function getListByUserId($userId){

        $condition = [];
        $condition['user_id'] = is_array($userId)?array("IN",$userId):$userId;

        return $this->where($condition)->select();
    }


}