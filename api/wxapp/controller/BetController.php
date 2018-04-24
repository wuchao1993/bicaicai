<?php

namespace api\wxapp\controller;

use cmf\controller\RestBaseController;
use api\wxapp\model\BetCoinsGamesModel;
use wxapp\aes\WXBizDataCrypt;
use api\wxapp\model\UserBetResultModel;

class BetController extends RestBaseController
{
    // 获取
    public function getBtcInfo()
    {
        $obj       = new BetCoinsGamesModel();
        $data      = $obj->getNowGame();
        $total = $data['fell_total'] + $data['rise_total'];
        $data['fell_percent'] = sprintf("%.2f", $data['fell_total'] / $total * 100);
        $data['rise_percent'] = sprintf("%.2f", $data['rise_total'] / $total * 100);
		$this->success("获取成功", $data);
    }

    public function getLeaderboard(){
        $type = $this->request->param('type');
        switch ($type) {
            case 'month':
                $time = strtotime('-30day', time());
                break;
            case 'year':
                $time = strtotime('-365day', time());
                break;
            default:
                $time = strtotime('-7day', time());
                break;
        }
        $userBet = new UserBetResultModel();
        $result = $userBet->getLeaderboard($time);

    }

}
