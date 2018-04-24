<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use cmf\controller\RestBaseController;
use wxapp\aes\WXBizDataCrypt;
use api\wxapp\model\UserBetResultModel;

class UserController extends RestBaseController
{
    // 获取用户信息
    public function getUserInfo()
    {



    }

    /**
     * 用户竞猜
     * @return [type] [description]
     */
    public function userBet(){
    	$params['bet_id'] = $this->request->param('id');
    	$params['uid'] = $this->getUserId();
    	$params['user_bet'] = $this->request->param('bet');
    	$params['time'] = time();
        $userBet = new UserBetResultModel();
        $result = $userBet->bet($params);
        if($result){
        	$this->success('竞猜成功');
        }else{
            $this->error('竞猜失败');
        }
    }

    /**
     * 获取用户竞猜列表
     * @return [type] [description]
     */
    public function userBetList(){
       
    }

}
