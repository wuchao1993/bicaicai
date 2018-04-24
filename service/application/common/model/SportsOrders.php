<?php
/**
 * 订单模型
 * @createTime 2017/4/18 17:10
 */

namespace app\common\model;

use think\Loader;
use think\Model;
use think\Config;

class SportsOrders extends Model {
    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'so_id';


    public function statisticsOrdersInfo($condition) {
        $fields = [
            'sum(so_bet_amount)'         => 'so_bet_amount',
            'sum(so_bonus)'              => 'so_bonus',
            'sum(so_bonus_no_principal)' => 'so_bonus_no_principal',
            'sum(so_to_win)'             => 'so_to_win',
        ];
        $result = $this->where($condition)->field($fields)->find();

        return is_object($result) ? $result->toArray() : $result;
    }

    //计算有效投注
    public function getSportsBetAmount($user_id, $startDate, $endDate){

        $condition = [
            "so_user_id"         => $user_id,
            "so_calculate_time"  => ['between', [$startDate, $endDate]],
            "so_status"          => Config::get('status.order_status')['distribute'],
        ];

        $result = $this->where($condition)->select();

        $returnArr['bet'] = 0;

        if(!empty($result)) {
            //读取配置
            $siteConfig = Loader::model('SiteConfig')->getConfig('sports', 'common', ['ignore_traffic_amount_odds']);

            foreach($result as $val) {
                //是否和局
                if($val['so_bet_status'] == Config::get('status.order_bet_status')['back']) {
                    continue;
                }

                //是否小赔率
                $actualOdds = bcdiv($val['so_to_win'], $val['so_bet_amount']);
                if(bccomp($actualOdds, $siteConfig['ignore_traffic_amount_odds']) <= 0) {
                    continue;
                }

                //是否赢一半或者输一半
                if($val['so_bet_status'] == Config::get('status.order_bet_status')['win_half'] || $val['so_bet_status'] == Config::get('status.order_bet_status')['lose_half']) {
                    $val['so_bet_amount'] = $val['so_bet_amount']/2;
                }
                
                $returnArr['bet'] += $val['so_bet_amount'];
            }
        }

        return $returnArr;
    }
}