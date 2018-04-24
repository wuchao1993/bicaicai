<?php

namespace app\common\model;

use think\Loader;
use think\Model;

class AgentLinkRebate extends Model
{

    public function getLinkRebate($agentLinkId)
    {
        $condition = [
            'agl_id' => $agentLinkId,
        ];

        return $this->where($condition)->column('rebate', 'category_id');
    }



    /***
     * @desc 获取邀请码信息数据
     * @param $result
     * @return mixed
     */
    public function getRebateResult($result)
    {
        $rebateIds = extract_array($result, 'id');
        $rebateArr = $this->_rebateCategoryId($rebateIds);
        foreach($result as $key => &$value) {
            $value['rebate'] = $rebateArr[$value['id']];
        }
        return $result;
    }

    /***
     * @desc 获取rebate的属性名称
     * @param $rebateIds
     * @return mixed
     */
    private function _rebateCategoryId($rebateIds)
    {
        $condition['agl_id']    = ['in', $rebateIds];
        $rebateField            = "agl_id,category_id,rebate";
        $rebateData             = Loader::model("AgentLinkRebate")
                                        ->field($rebateField)
                                        ->where($condition)
                                        ->select();
        $categoryDisplayData    = Loader::model("categoryDisplay")->getCategoryNameMap();

        //获取rebate的属性和rebate
        $rebateArr = [];
        foreach($rebateData as $k => $v) {
            $rebateArr[$v['agl_id']][] = [
                'categoryId' => $v['category_id'],
                'userRebate' => $v['rebate'],
                'categoryName' => $categoryDisplayData[$v['category_id']]
            ];
        }
        return $rebateArr;
    }


}