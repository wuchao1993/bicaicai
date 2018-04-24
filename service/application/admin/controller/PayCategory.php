<?php
namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Config;

class PayCategory
{

    /**
     * @param Request $request
     * @return array
     * 获取支付类型/入款类型列表
     */
    public function getPayCategoryList(Request $request)
    {
        $page = $request->param('page');
        $num = $request->param('num');
        $name = $request->param('name');
        $sort = $request->param('sort');
        $payCategoryLogic = Loader::model('PayCategory', 'logic');
        $data = $payCategoryLogic->getList($name, $page, $num, $sort);

        $type_group = Loader::model('RechargeTypeGroup', 'logic')->getAll();
        $type_group = reindex_array($type_group,'rtg_id');

        if(!empty($data) && !empty($type_group)){
            $responseList = [];
            foreach ($data['list'] as $key => $info) {
                $responseList[$key] = $this->_packPayCategoryInfo($info,$type_group);
            }
        }

        return [
            'errorcode' => $payCategoryLogic->errorcode,
            'message' => Config::get('errorcode')[$payCategoryLogic->errorcode],
            'data' => [
                'list' => $responseList,
                'totalCount' => $data['count']
            ]
        ];
    }


    /**
     * @param Request $request
     * @return array
     * 获取支付类型详情
     */
    public function getPayCategoryInfo(Request $request){
        $id = $request->param('id');
        $payCategoryLogic = Loader::model('PayCategory', 'logic');
        $data = $payCategoryLogic->getInfo($id);

        return [
            'errorcode' => $payCategoryLogic->errorcode,
            'message' => Config::get('errorcode')[$payCategoryLogic->errorcode],
            'data' => $this->_packPayCategoryInfo($data),
        ];
    }

    /**
     * 修改状态
     * @param $params
     * @return array
     */
    public function changeStatus(Request $request)
    {
        $params['recharge_type_id']		= $request->param('id');
        $params['recharge_type_status']	= $request->param('status');
        
        $payCategoryLogic = Loader::model('PayCategory', 'logic');
        $result  = $payCategoryLogic->changeStatus($params);
        
        return [
                'errorcode' => $payCategoryLogic->errorcode,
                'message'   => Config::get('errorcode')[$payCategoryLogic->errorcode],
                'data'      => $result,
        ];
    }
    
    private function _packPayCategoryInfo($info,$type_group)
    {
        return [
            'id' => $info['recharge_type_id'],
            'name' => $info['recharge_type_name'],
            'image' => $info['recharge_type_image'],
            'introduction' => $info['recharge_type_introduction'],
            'sort' => $info['recharge_type_sort'],
            'status' => $info['recharge_type_status'],
            'group' => !empty($type_group[$info['rtg_id']]['rtg_name'])?$type_group[$info['rtg_id']]['rtg_name']:'',
            'group_id' => $info['rtg_id'],
            'shortName' => $info['recharge_type_short_name'],
            'actionType' => $info['recharge_type_action_type'],
            'scheme' => $info['recharge_type_scheme'],
            'code' => $info['recharge_type_code']
        ];
    }


    public function editPayCategoryInfo(Request $request){
        $id = $request->param('id');
        $info = [
            'recharge_type_name' => $request->param('name'),
            'recharge_type_short_name' => $request->param('shortName'),
            'recharge_type_image' => $request->param('image'),
            'recharge_type_introduction' => $request->param('introduction'),
            'recharge_type_scheme' => $request->param('scheme'),
            'recharge_type_action_type' => $request->param('actionType'),
            'recharge_type_sort' => $request->param('sort'),
            'recharge_type_status' => $request->param('status'),
            'rtg_id'            => $request->param('group_id'),
            'recharge_type_code' => $request->param('code'),
        ];
        $payCategoryLogic = Loader::model('PayCategory', 'logic');
        $payCategoryLogic->editInfo($id, $info);
        return [
            'errorcode' => $payCategoryLogic->errorcode,
            'message' => Config::get('errorcode')[$payCategoryLogic->errorcode]
        ];
    }

}