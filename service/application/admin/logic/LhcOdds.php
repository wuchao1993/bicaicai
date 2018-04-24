<?php

/**
 * 六合彩赔率相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Loader;
use think\Model;

class LhcOdds extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取列表
     *
     * @param
     *
     * @return array
     */
    public function getOddsList($params) {

        $condition ['lottery_id'] = $params ['lottery_id'];

        //对六合彩的特码做特殊处理

        if($params['lhc_type_id'] == 1 || $params['lhc_type_id'] == 2) {
            $condition ['lhc_type_id'] = ['IN',[$params ['lhc_type_id'],24]];
        }else{
            $condition ['lhc_type_id'] = $params ['lhc_type_id'];
        }

        $list = Loader::model('LhcOdds')->where($condition)->order('lhc_odds_id asc')->select();

        return $list;
    }

    /**
     * 编辑限额设置
     *
     * @param
     *            $params
     * @return array
     */
    public function editLhcOdds($params) {
        $lotteryOddsModel = Loader::model('LhcOdds');

        foreach($params ['configIds'] as $val) {
            $data                    = [];
            $data ['lhc_odds_value'] = $val ['value'];
            $lotteryOddsModel->where([
                'lhc_odds_id' => $val ['id'],
                'lottery_id' => $params['lottery_id']
            ])->update($data);
        }

        return true;
    }
}