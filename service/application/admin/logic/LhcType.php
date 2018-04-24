<?php

/**
 * 六合彩玩法分类相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class LhcType extends Model {
    
    /**
     * 错误变量
     * 
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;
    
    /**
     * 获取分类
     * 
     * @param
     * @return array
     */
    public function getTypeList()
    {
        
        $list = Loader::model ( 'LhcType' )->order ( 'lhc_type_id asc' )->select ();

        return $list;
    }
    
}