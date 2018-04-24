<?php
/**
 * 活动业务逻辑
 * @createTime 2017/4/4 10:20
 */

namespace app\api\logic;

use think\Config;
use think\Loader;
use think\Model;

class Activity extends Model {

    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 获取大厅首页活动列表
     * @return bool
     */
    public function getHomeActivity() {
        $timeNow = date('Y-m-d, H:i:s');
        $where = [
            'activity_is_banner' => 1,
            'activity_status' => 1,
            'activity_lottery_type' => 2,
            'activity_starttime' => ['<=', $timeNow],
            'activity_finishtime' => ['>=', $timeNow],
        ];
        $list = Loader::model('Activity')
            ->where($where)
            ->order('activity_sort desc')
            ->column('activity_name,activity_mobile_image AS activity_image', 'activity_id');
        if (!$list) {
            return false;
        }
        return array_values($list);
    }

    /**
     * 获取活动列表
     * @return bool
     */
    public function getActivity() {
        $where = [
            'activity_status' => 1,
            'activity_lottery_type' => 2,
        ];
        $field = [
            'activity_id',
            'activity_name',
            'activity_mobile_image AS activity_image',
            'activity_starttime AS start_time',
            'activity_finishtime AS finish_time',
        ];
        $list = Loader::model('Activity')
            ->where($where)
            ->order('activity_sort desc')
            ->field($field)
            ->select();
        if (!$list) {
            return false;
        }
        foreach($list as $key => $item) {
            $list[$key]->end = $item->finish_time < date('Y-m-d, H:i:s') ? 'yes' : 'no';
        }
        return $list;
    }

    /**
     * 获取活动详情
     * @param $id
     * @return bool
     */
    public function getActivityInfoById($id) {
        $where = [
            'activity_status' => 1,
            'activity_lottery_type' => 2,
            'activity_id' => $id
        ];
        $info = Loader::model('Activity')->where($where)->find();
        if (!$info) {
            return false;
        }
        $data['activity_id']           = $info->activity_id;
        $data['activity_name']         = $info->activity_name;
        $data['activity_introduction'] = $info->activity_introduction;
        $data['activity_description']  = $info->activity_description;
        $data['start_time']            = $info->activity_starttime;
        $data['finish_time']           = $info->activity_finishtime;
        $data['end'] = $info->activity_finishtime < date('Y-m-d, H:i:s') ? 'yes' : 'no';
        return $data;
    }

    /**
     * PC获取活动列表
     */
    public function getPcActivity($params){
        $activityModel = Loader::model('Activity');
        $condition = [];
        $condition ['activity_status'] = Config::get('status.activity_status')['normal'];
        $condition ['activity_lottery_type'] = Config::get('status.activity_lottery_type')['sports'];
        if (isset($params['category_id']) && !empty($params['category_id'])) {
            $condition['activity_category_id'] = $params['category_id'];
        }
        $count = $activityModel->where($condition)->count();
        $list = [];
        if ($count > 0) {
            $list = $activityModel->where($condition)->order('activity_sort desc')->limit($params['num'])->page($params['page'])->select();
            foreach($list as $key => $item) {
                $list[$key]['end'] = $item['activity_finishtime'] < date('Y-m-d, H:i:s') ? 'yes' : 'no';
            }
        }

        return array ('totalCount' => $count, 'list' => $list);
    }

    public function getPcActivityDetailedById($id){
        $where = [
            'activity_status' => 1,
            'activity_lottery_type' => 2,
            'activity_id' => $id
        ];
        $detailed = Loader::model('Activity')->where($where)->find();
        if(!$detailed){
            return false;
        }
        $detailed['end'] = $detailed['activity_finishtime'] < date('Y-m-d, H:i:s') ? 'yes' : 'no';
        return $detailed;
    }

    /**
     * PC获取活动类目列表
     */
    public function getPcCategoryList(){
        $activityModel = Loader::model('ActivityCategory');
        $list  = $activityModel->order('activity_category_id desc')->select();
        $returnArr = array (
                'totalCount' => $count,
                'list' => $list 
        );
        return $returnArr;
    }
}