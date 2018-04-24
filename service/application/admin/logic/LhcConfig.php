<?php

/**
 * 六合彩配置相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class LhcConfig extends Model {
    
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
     *
     * @return array
     */
    public function getConfigList() {
        $list = Loader::model ( 'LhcConfig' )->order ( 'lhc_config_id asc' )->select ();
        
        return $list;
    }
    
    /**
     * 编辑配置
     *
     * @param
     *            $params
     * @return array
     */
    public function editLhcTypeConfig($params) {
        // 配置
        $lhcConfigModel = Loader::model ( 'LhcConfig' );
        
        foreach ( $params ['configIds'] as $val ) {
            $data = [ ];
            $data ['lhc_config_value'] = $val ['value'];
            $lhcConfigModel->where ( [ 
                    'lhc_config_id' => $val ['id'] 
            ] )->update ( $data );
        }
        
        // 类型
        $lhcTypeModel = Loader::model ( 'LhcType' );
        
        foreach ( $params ['typeIds'] as $val ) {
            $data = [ ];
            $data ['play_stake_bet_min_money'] = $val ['bi'];
            $data ['play_stake_bet_max_money'] = $val ['ba'];
            $data ['play_item_bet_max_money'] = $val ['ia'];
            $data ['lhc_type_status'] = $val ['s'];
            
            $lhcTypeModel->where ( [ 
                    'lhc_type_id' => $val ['id'] 
            ] )->update ( $data );
        }
        
        return true;
    }
}