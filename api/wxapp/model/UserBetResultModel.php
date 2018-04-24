<?php
// +----------------------------------------------------------------------
// | 文件说明：用户-幻灯片
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Date: 2017-5-25
// +----------------------------------------------------------------------

namespace api\wxapp\model;

use think\Model;

class UserBetResultModel extends Model
{

    public function bet($params){
        $map['uid'] = $params['uid'];
        $map['bet_id'] = $params['bet_id'];
        $result = $this->where($map)->find();
        if($result){
            return false;
        }
        $status = $this->insert($params);
        if($status){
            if($params['user_bet'] ==1) {
                $res = 'rise_total';
            }else{
                $res = 'fell_total';
            }
            $conis = new BetCoinsGamesModel();
            $condition['id'] = $params['bet_id'];
            $conis->where($condition)->setInc($res);
            return true;
        }else{
            return false;
        }		
    }
    public function getLeaderboard($param){
        $result = $this->query("SELECT `uid`,count(id) as total,count(if(is_winning=1,true,null)) as win_total FROM `bicaicai_user_bet_result` WHERE `time` >= $param AND `result` > 0 GROUP BY `uid` ORDER BY");
        echo $this->getLastSql();
        dump($result);
    }

}
