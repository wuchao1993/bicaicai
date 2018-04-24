<?php

/**
 * 数字彩玩法相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class LotteryPlay extends Model {
    
    /**
     * 错误变量
     * 
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;
    
    /**
     * 获取玩法列表
     * 
     * @param
     *            $params
     * @return array
     */
    public function getPlayList($params)
    {
        $lotteryPlayModel = Loader::model('LotteryPlay');
        
        $condition = [];
        if(isset($params['lottery_category_id'])) {
            $condition['lc.lottery_category_id'] = $params['lottery_category_id'];
        }
        
        //获取总条数
        $count = $lotteryPlayModel
        ->alias('lp')
        ->join('LotteryType lt','lp.lottery_type_id=lt.lottery_type_id', 'LEFT')
        ->join('LotteryCategory lc','lt.lottery_category_id=lc.lottery_category_id', 'LEFT')
        ->where($condition)
        ->count();
        
        $list = $lotteryPlayModel
        ->alias('lp')
        ->join('LotteryType lt','lp.lottery_type_id=lt.lottery_type_id', 'LEFT')
        ->join('LotteryCategory lc','lt.lottery_category_id=lc.lottery_category_id', 'LEFT')
        ->field('lp.*,lt.lottery_type_name,lc.lottery_category_name')
        ->where($condition)
        ->order('lp.play_id asc')
        ->limit($params['num'])
        ->page($params['page'])
        ->select();
        
        $returnArr = array('totalCount'=>$count, 'list'=>$list);
        
        return $returnArr;
    }
    
    /**
     * 编辑
     * 
     * @param
     *            $params
     * @return bool
     */
    public function editPlay($params) 
    {
        // 修改表信息
        $updateData ['play_help']  = $params ['play_help'];
        $updateData ['play_example'] = $params ['play_example'];
        $updateData ['play_tips'] = $params ['play_tips'];

        $updateData= array_filter($updateData);
        
        Loader::model ( 'LotteryPlay' )->save ( $updateData, [ 
                'play_id' => $params ['play_id'] 
        ] );
        
        return true;
    }
    
    /**
     * 获取限额设置列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getPlayConfigList($params)
    {
        $lotteryPlayModel = Loader::model('LotteryPlay');
        
        $condition = [];
        if(isset($params['lottery_category_id'])) {
            $condition['lc.lottery_category_id'] = $params['lottery_category_id'];
        }
                
        $list = $lotteryPlayModel
        ->alias('lp')
        ->join('LotteryType lt','lp.lottery_type_id=lt.lottery_type_id', 'LEFT')
        ->join('LotteryCategory lc','lt.lottery_category_id=lc.lottery_category_id', 'LEFT')
        ->field('lp.*,lt.lottery_type_name,lc.lottery_category_max_rebate')
        ->where($condition)
        ->order('lp.play_id asc')
        ->limit($params['num'])
        ->page($params['page'])
        ->select();
          
        return $list;
    }
    
    /**
     * 编辑限额设置
     *
     * @param
     *            $params
     * @return bool
     */
    public function editPlayConfig($params)
    {
        $lotteryPlayModel = Loader::model('LotteryPlay');

        if(is_array($params['configIds'])) {
            foreach($params['configIds'] as $val) {
                $data                             = [];
                $data['play_stake_bet_min_money'] = $val['si'];
                $data['play_stake_bet_max_money'] = $val['sa'];
                $data['play_item_bet_max_money']  = $val['ia'];
                $lotteryPlayModel->where(['play_id' => $val['id']])->update($data);
            }
        }

        return true;
    }


    /**
     * 初始玩法赔率
     * @param int $model
     * @param int $categoryId
     * @return bool
     * 旧平台地址：\Application\Home\Controller\InitController.class.php
     */
    public function initPlayOdds($model = 1970, $categoryId = 1){
        $model = intval($model);
        if($model < 1700 || $model > 2000){
           $this->errorcode = EC_AD_INIT_LOTTERY_PLAY_ODDS_ERROR;
           return false;
        }
        bcscale(4);
        if($model<=2000){
            $base = 2000;
            $minProportion = bcdiv(1700, $base);
            $maxProportion = bcdiv($model, $base);

            $initConfig = array(
                1 => array( 1 => 100000,2 => 100000, 3 => '100000,10000,1000,100,10',4 => 833.333, 5 => 1666.666,
                    6 => 3333.333,7 => 5000,8 => 10000,9 => 20000,10 => 10000,
                    11 => 10000,12 => '10000,1000,100,10',13 => 416.666,14 => 833.333,15 => 1666.666,
                    16 => 2500,17 => 10000,18 => 10000,19 => '10000,1000,100,10',20 => 416.666,
                    21 => 833.333,22 => 1666.666,23 => 2500,24 => 1000,25 => 1000,
                    26 => 1000,27 => 333.333,28 => 166.666,29 => '333.333,166.666',30 => 1000,
                    31 => 1000,32 => 1000,33 => 333.333,34 => 166.666,35 => '333.333,166.666',
                    36 => 1000,37 => 1000,38 => 1000,39 => 333.333,40 => 166.666,
                    41 => '333.333,166.666',42 => 100,43 => 100,44 => 100,45 => 4,
                    46 => 50,47 => 50,48 => 100,49 => 100,50 => 100,
                    51 => 4,52 => 50,53 => 50,54 => 10,55 => 3.69,
                    56 => 3.69,57 => 3.69,58 => 18.518,59 => 18.518,60 => 18.518,
                    61 => 100,62 => 100,63 => 50,64 => 1000,65 => 1000,
                    66 => 333.333,67 => 166.666,68 => '333.333,166.666',69 => 10000,
                    70 => 10000,71 => 1000,72 => 1000,73 => 1000,74 => 100,75 => 100,
                    76 => 2.442,77 => 12.278,78 => 116.823,79 => 2173.913,80 => '10,2.223',
                    81 => '10,2.223',82 => '10,2.223',83 => '10,2.223',84 => '10,2.223',85 => '10,2.223',
                    86 => '10,2.223',87 => '10,2.223',88 => '10,2.223',89 => '10,2.223'
                ),

                2 => array(
                    90 => 7.2 ,91 => 7.2,92 => 7.2, 93 => 72,94 => 72, 95 => 13.5,
                    96 => 36,97 => 36,98 => 216,99 => 36,100 => 9,
                    101 => '216,72,36,21.6,14.4,10.29,8.64,8'
                ),
                3 => array(
                    102 => 990,103 => 990,104 => 165,105 => 165,106 => 110,
                    107 => 110,108 => 55,109 => 55,110 => 3.666,111 => 11,
                    114 => 2.2,115 => 5.5,116 => 16.5,117 => 66,118 => 462,
                    119 => 77,120 => 22,121 => 8.2494,122 => 2.2,123 => 5.5,
                    124 => 16.5,125 => 66,126 => 462,127 => 77,128 => 22,129 => 8.2494
                ),
                4 => array(
                    130 => 1000,
                    131 => 1000,
                    132 => 1000,
                    133 => 333.333,
                    134 => 166.666,
                    135 => '333.333,166.666',
                    136 => 100,
                    137 => 100,
                    138 => 100,
                    139 => 50,
                    140 => 50,
                    141 => 100,
                    142 => 100,
                    143 => 100,
                    144 => 50,
                    145 => 50,
                    146 => 10,
                    147 => 3.69
                ),
                5 => array(
                    148 => 10,
                    149 => 90,
                    150 => 90,
                    151 => 720,
                    152 => 720,
                    153 => 10,
                    154 => 10,
                    155 => 2,
                    156 => 2,
                    157 => 2,
                    158 => 2,
                    159 => 2,
                    160 => 2
                )

            );

            $config = $initConfig[$categoryId];

            $oddsList = [];
            foreach ($config as $playId => $conf){
                if(is_string($conf)){
                    $subOddsList = explode(',', $conf);
                    $newMinSubOdds = [];
                    $newMaxSubOdds = [];
                    foreach ($subOddsList as $subOdds){
                        $newMinSubOdds[] = round(bcmul($subOdds, $minProportion), 3);
                        $newMaxSubOdds[] = round(bcmul($subOdds, $maxProportion), 3);
                    }
                    $minOdds = implode(',', $newMinSubOdds);
                    $maxOdds = implode(',', $newMaxSubOdds);

                }else{
                    $minOdds = round(bcmul($conf, $minProportion), 3);
                    $maxOdds = round(bcmul($conf, $maxProportion), 3);

                }
                $oddsList[$playId]['play_min_odds'] = $minOdds;
                $oddsList[$playId]['play_max_odds'] = $maxOdds;
            }

            foreach ($oddsList as $pId => $odds){
                $data = [];
                $data['play_min_odds'] = $odds['play_min_odds'];
                $data['play_max_odds'] = $odds['play_max_odds'];
                $this->update($data,['play_id'=>$pId]);
            }

            if($this->getError()){
                return false;
            }else{
                return true;
            }

        }

    }




}