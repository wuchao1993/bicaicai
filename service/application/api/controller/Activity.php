<?php
/**
 * 大厅控制器
 * @createTime 2017/4/1 16:06
 */

namespace app\api\controller;

use think\Loader;
use think\Config;
use think\Request;

class Activity {

    /**
     * PC获取活动列表接口
     * @param Request $request
     * @return array
     */
    public function getPcActivityList(Request $request){
        $params['page'] = $request->param('page', 1);
        $params['num']  = $request->param('num', 10);
        $params['category_id'] = $request->param('categoryId');
        $actLogic = Loader::model('Activity', 'logic');
        $data = $actLogic->getPcActivity($params);
        return [
            'errorcode' => $actLogic->errorcode,
            'message'   => Config::get('errorcode')[$actLogic->errorcode],
            'data'      => output_format($data),
        ];
    }

    public function getPcActivityDetailed(Request $request){
        $id = $request->param('id');
        $activity = Loader::model('Activity', 'logic');
        $result = $activity->getPcActivityDetailedById($id);
        return [
            'errorcode' => $activity->errorcode,
            'message'   => Config::get('errorcode')[$activity->errorcode],
            'data'      => output_format($result),
        ];
    }

    public function getPcActivityCategory(){
        $activityCategory = Loader::model('Activity', 'logic');
        $categoryList     = $activityCategory->getPcCategoryList(); 
        return [
                'errorcode' => $activityCategory->errorcode,
                'message' => Config::get ('errorcode') [$activityCategory->errorcode],
                'data' => output_format($categoryList) 
        ];
    }
}