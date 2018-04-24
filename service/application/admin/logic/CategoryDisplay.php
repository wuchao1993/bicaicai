<?php

/**
 * 展示分类相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class CategoryDisplay extends Model {
    
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
    public function getCategoryDisplayList() {
        $list = Loader::model ( 'CategoryDisplay' )->order ( 'category_display_sort asc' )->select ();
        
        return $list;
    }
    
    /**
     * 编辑
     * 
     * @param
     *            $params
     * @return bool
     */
    public function editCategory($params) {
        // 修改表信息
        $updateData ['category_display_name']  = $params ['category_display_name'];
        $updateData ['category_display_image'] = $params ['category_display_image'];
        $updateData ['category_display_sort']  = $params ['category_display_sort'];
        $updateData ['category_display_hot']   = $params ['category_display_hot'];
        $updateData ['category_display_introduction']   = $params ['category_display_introduction'];
        
        Loader::model ( 'CategoryDisplay' )->save ( $updateData, [
                'category_display_id' => $params ['category_display_id'] 
        ] );
        
        return true;
    }
}