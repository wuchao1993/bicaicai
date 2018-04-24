<?php

/**
 * 数字彩相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Model;

class Lottery extends Model {

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
    public function getList() {
        $list = Loader::model('Lottery')->field('lottery_id as id, lottery_name as name')->order('lottery_id asc')->select();

        return $list;
    }

    /**
     * 获取数字彩游戏列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getGameList($params) {
        $lotteryModel = Loader::model('Lottery');

        $condition = [];
        if(isset ($params ['lottery_name'])) {
            $condition ['l.lottery_name'] = [
                'LIKE',
                '%' . $params ['lottery_name'] . '%',
            ];
        }

        // 获取总条数
        $count = $lotteryModel->alias('l')->where($condition)->count();

        $list = $lotteryModel->alias('l')
            ->join('LotteryCategory lc', 'lc.lottery_category_id=l.lottery_category_id', 'LEFT')
            ->join('CategoryDisplay cd', 'cd.category_display_id=l.category_display_id', 'LEFT')
            ->field('l.*,lc.lottery_category_name,cd.category_display_name')
            ->where($condition)->order('l.lottery_sort')->limit($params ['num'])->page($params ['page'])->select();

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 添加彩种
     *
     * @param
     *            $params
     * @return array
     */
    public function addGame($params) {
        $lotteryModel = Loader::model('Lottery');

        // 判断彩种是否已经存在
        $ret = $lotteryModel->where([
            'lottery_name' => $params ['lottery_name'],
        ])->count();
        if($ret > 0) {
            $this->errorcode = EC_AD_REG_LOTTERY_GAME_EXISTING;

            return false;
        }

        // 入库
        $data ['lottery_name']            = $params ['lottery_name'];
        $data ['lottery_category_id']     = $params ['lottery_category_id'];
        $data ['lottery_group_id']        = 1;
        $data ['category_display_id']     = $params ['category_display_id'];
        $data ['lottery_image_url']       = $params ['lottery_image_url'];
        $data ['lottery_introduction']    = $params ['lottery_introduction'];
        $data ['lottery_description']     = $params ['lottery_description'];
        $data ['lottery_message_explain'] = $params ['lottery_message_explain'];
        $data ['lottery_is_hot']          = $params ['lottery_is_hot'];
        $data ['lottery_status']          = $params ['lottery_status'];
        $data ['lottery_sort']            = $params ['lottery_sort'];
        $data ['lottery_createtime']      = date('Y-m-d H:i:s');
        $data ['lottery_day_frequency']   = '5分';

        $data = array_filter($data);

        $ret = $lotteryModel->save($data);
        if($ret) {
            $gameInfo = [
                'id' => $lotteryModel->lottery_id,
            ];

            return $gameInfo;
        }
        $this->errorcode = EC_AD_ADD_LOTTERY_GAME_ERROR;

        return false;
    }

    /**
     * 编辑彩种
     *
     * @param
     *            $params
     * @return array
     */
    public function editGame($params) {
        // 入库
        $data ['category_display_id']     = $params ['category_display_id'];
        $data ['lottery_image_url']       = $params ['lottery_image_url'];
        $data ['lottery_introduction']    = $params ['lottery_introduction'];
        $data ['lottery_description']     = $params ['lottery_description'];
        $data ['lottery_message_explain'] = $params ['lottery_message_explain'];
        $data ['lottery_is_hot']          = $params ['lottery_is_hot'];
        $data ['lottery_status']          = $params ['lottery_status'];
        $data ['lottery_sort']            = $params ['lottery_sort'];

        $this->_editTypeConfig($params,$data);

        //同步试玩库

        try_db();

        $this->_editTypeConfig($params,$data,true);

        return true;
    }

    public function _editTypeConfig($params,$data,$con = false){
        if($con){
            $lotteryTypeConfigModel = Loader::db([],true)->name('LotteryTypeConfig');
            $lotteryTypeModel = Loader::db([],true)->name('LotteryType');
            $lottery = Loader::db([],true)->name('Lottery');
        }else{
            $lotteryTypeConfigModel = Loader::model ( 'LotteryTypeConfig' );
            $lotteryTypeModel = Loader::model ( 'LotteryType' );
            $lottery = Loader::model ( 'Lottery' );
        }

        //如果启用、停用状态改变,修改对应玩法
        $lotteryStatus = $lottery->where('lottery_id',$params ['lottery_id'])->column('lottery_status');
        if($params ['lottery_category_id'] != Config::get('lottery.LHC_CATEGORY_ID')){

            if($lotteryStatus != $params ['lottery_status']){
                //开启
                if($params ['lottery_status'] == Config::get('status.lottery_status')['yes']){
                    $where['lottery_category_id'] = $params ['lottery_category_id'];
                    $where['lottery_type_status'] = Config::get('status.lottery_type_status')['yes'];
                    $lotteryTypeList = $lotteryTypeModel->where($where)->column('lottery_type_id');
                    $lotteryTypeConfigModel->where('lottery_id',$params ['lottery_id'])->delete();
                    foreach ($lotteryTypeList as $key => $value){
                        $lotteryTypeConfigList[$key]['lottery_id']  = $params ['lottery_id'];
                        $lotteryTypeConfigList[$key]['lottery_type_id']  = $value;
                        $lotteryTypeConfigList[$key]['ltc_sort']  = 0;
                    }
                    $lotteryTypeConfigModel->insertAll($lotteryTypeConfigList);
                }else{
                    $lotteryTypeConfigModel->where('lottery_id',$params ['lottery_id'])->delete();
                }
            }
        }

        $lottery->where ( [
            'lottery_id' => $params ['lottery_id'],
        ] )->update ( $data );
    }
    /**
     * 获取PC蛋蛋配置
     *
     * @return array
     */
    public function getPc28Config() {
        $condition ['lt.lottery_category_id'] = Config::get('lottery.PC28_CATEGROY_ID');
        $list                                 = Loader::model('LotteryPlay')->alias('lp')->join('LotteryType lt', 'lt.lottery_type_id=lp.lottery_type_id', 'LEFT')->field('lp.*')->where($condition)->order('lp.play_id asc')->select();

        return $list;
    }

    /**
     * 编辑PC蛋蛋配置
     *
     * @param
     *            $params
     * @return array
     */
    public function editPc28Config($params) {
        $lotteryPlayModel = Loader::model('LotteryPlay');

        foreach($params ['configIds'] as $val) {
            $data                   = [];
            $data ['play_min_odds'] = $val ['value'];
            $lotteryPlayModel->where([
                'play_id' => $val ['id'],
            ])->update($data);
        }

        return true;
    }
}