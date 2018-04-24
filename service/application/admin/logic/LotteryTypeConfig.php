<?php

/**
 * 数字彩彩种设置相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;

class LotteryTypeConfig extends Model {
    
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
    public function getTypeConfigList($params) {
        $condition = [ ];
        if (isset ( $params ['lottery_category_id'] )) {
            $condition ['lt.lottery_category_id'] = $params ['lottery_category_id'];
        }
        $condition['lt.lottery_type_status'] = Config::get('status.lottery_type_status')['yes'];
        
        $list = Loader::model ( 'LotteryType' )->alias ( 'lt' )->join ( 'Lottery l', 'l.lottery_category_id=lt.lottery_category_id', 'LEFT' )->where ( $condition )->field ( 'lt.*,l.lottery_id,l.lottery_name' )->order ( 'lottery_type_id asc' )->select ();
        
        $typeConfigList = Loader::model ( 'LotteryTypeConfig' )->select ();
        
        foreach ( $list as &$val ) {
            $val ['checked'] = 0;
            foreach ( $typeConfigList as $val2 ) {
                if ($val2 ['lottery_id'] == $val ['lottery_id'] && $val2 ['lottery_type_id'] == $val ['lottery_type_id']) {
                    $val ['checked'] = 1;
                    $val ['lottery_type_sort'] = $val2['ltc_sort'];
                    break;
                }
            }
        }
        
        return $list;
    }
    
    /**
     * 编辑
     *
     * @param
     *            $params
     * @return bool
     */
    public function editTypeConfig($params) {

        $lotteryTypeConifg = Loader::model ( 'LotteryTypeConfig' );
        $lotteryTypeModel = Loader::model ( 'LotteryType' );
        $lottery = Loader::model ( 'Lottery' );
        // 开启关闭对应彩种
        foreach ( $params ['headIds'] as $val ) {
            $lottery->where('lottery_id',$val['lid'])->update(['lottery_status' =>$val['checked']]);
        }
        //获取默认玩法typeid
        foreach ( $params ['defaultIds'] as $val ) {
            if ($val ['df'] == 1) {
                $defaultTypeId = $val ['tid'];
            }
        }
        //彩种
        $lotteryCat = $lotteryTypeModel->where('lottery_type_id',$defaultTypeId)->find();
        $condition['lottery_category_id'] = $lotteryCat ['lottery_category_id'];
        $condition['lottery_status'] = Config::get('status.lottery_status')['yes'];
        $LotteryList = $lottery ->where($condition)->column('lottery_id,lottery_name');

        //判断切换默认
        if(empty($params ['configIds'])){
            //未修改玩法（开启的彩种，玩法未开启，无法切换为默认）
            foreach ($LotteryList as $key => $value){
                $where['lottery_id'] = $key;
                $where['lottery_type_id'] = $defaultTypeId;
                $count = $lotteryTypeConifg->where($where)->count();
                if($count < 1){
                    $this->errorcode = EC_AD_CANNOT_SET_DEFAULT_LOTTERY_TYPE;
                    return false;
                }
            }
        }else{
            foreach ( $params ['configIds'] as $val ) {
                if ($val ['checked'] == 0) {
                    //不给关闭条件 1.（彩种未关闭）为默认
                    $where['lottery_id'] = $val ['lid'];
                    $where['lottery_status'] = Config::get('status.lottery_status')['yes'];
                    $count = $lottery->where($where)->count();
                    if($count >0 && $val ['tid'] == $defaultTypeId){
                            $this->errorcode = EC_AD_CANNOT_SET_DEFAULT_LOTTERY_TYPE;
                            return false;
                    }
                }
            }
        }

        $this->_editTypeConfig($params);

        //同步试玩库

        try_db();

        $this->_editTypeConfig($params,true);

        return true;
    }


    public function _editTypeConfig($params,$con = false){
        if($con){
            $lotteryTypeConfigModel = Loader::db([],true)->name('LotteryTypeConfig');
            $lotteryTypeModel = Loader::db([],true)->name('LotteryType');
            $lottery = Loader::db([],true)->name('Lottery');
            // 开启关闭对应彩种
            foreach ( $params ['headIds'] as $val ) {
                $lottery->where('lottery_id',$val['lid'])->update(['lottery_status' =>$val['checked']]);
            }
        }else{
            $lotteryTypeConfigModel = Loader::model ( 'LotteryTypeConfig' );
            $lotteryTypeModel = Loader::model ( 'LotteryType' );
        }

        foreach ( $params ['configIds'] as $val ) {

            if ($val ['checked'] == 1) {
                $data = [ ];
                $data ['lottery_id'] = $val ['lid'];
                $data ['lottery_type_id'] = $val ['tid'];
                $lotteryTypeConfigModel->insert ( $data, true );
            } elseif ($val ['checked'] == 0) {
                $lotteryTypeConfigModel->where ( [
                    'lottery_id' => $val ['lid'],
                    'lottery_type_id' => $val ['tid']
                ] )->delete ();
            }
        }

        // Type
        foreach ( $params ['defaultIds'] as $val ) {
            $data = [ ];
            $data ['lottery_type_default'] = $val ['df'];
            $lotteryTypeModel->where ( [
                'lottery_type_id' => $val ['tid']
            ] )->update ( $data );
        }

        // Sort
        foreach ( $params ['sortIds'] as $val ) {
            $data = [ ];
            $data ['ltc_sort'] = $val ['s'];
            $lotteryTypeConfigModel->where ( [
                'lottery_id' => $val ['lid'],
                'lottery_type_id' => $val ['tid']
            ] )->update ( $data );
        }
    }


}