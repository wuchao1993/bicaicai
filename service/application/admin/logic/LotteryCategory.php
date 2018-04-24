<?php

/**
 * 数字彩彩种相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class LotteryCategory extends Model {
    
    /**
     * 错误变量
     * 
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;
    
    /**
     * 获取数字彩分类
     * 
     * @param
     *            $params
     * @return array
     */
    public function getCategoryList() {
        $list = Loader::model ( 'LotteryCategory' )->order ( 'lottery_category_sort asc' )->select ();
        
        return $list;
    }
    
    /**
     * 编辑
     * 
     * @param
     *            $params
     * @return array
     */
    public function editCategory($params) {
        // 修改表信息
        $updateData ['lottery_category_name']  = $params ['lottery_category_name'];
        $updateData ['lottery_category_model'] = $params ['lottery_category_model'];
        $updateData ['lottery_default_rebate'] = $params ['lottery_default_rebate'];
        $updateData ['lottery_category_sort']  = $params ['lottery_category_sort'];
        
        Loader::model ( 'LotteryCategory' )->save ( $updateData, [ 
                'lottery_category_id' => $params ['lottery_category_id'] 
        ] );

        Loader::model('LotteryPlay','logic')->initPlayOdds($params ['lottery_category_model'],$params ['lottery_category_id']);
        
        return true;
    }



    public function getDefaultRebateMap(){
        return $this->column('lottery_category_id,lottery_default_rebate');
    }
}