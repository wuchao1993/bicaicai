<?php

namespace app\api\controller;

use think\Loader;
use think\Config;
use think\Request;
use think\Hook;

class UserFavoriteMatches{

    public function __construct() {
        Hook::listen('auth_check');
    }

    /**
     * 添加收藏
     * @param
     * @return
     */
    public function add(Request $request){
        $params['sport_id']   = $request->param('sportId');
        $params['matche_id'] = $request->param('matcheId'); 
        $favoriteLogic = Loader::model('UserFavoriteMatches','logic');
        $favoriteLogic->addFavorite($params);
        return [
                'errorcode' => $favoriteLogic->errorcode,
                'message' => Config::get ('errorcode') [$favoriteLogic->errorcode]
        ];         
    }
    
    /**
     * 取消收藏
     * @param
     * @return 
     */
    public function cancel(Request $request){
        $params['sport_id']  = $request->param('sportId');
        $params['matche_id'] = $request->param('matcheId'); 
        $favoriteLogic = Loader::model('UserFavoriteMatches','logic');
        $favoriteLogic->cancelFavorite($params);
        return [
                'errorcode' => $favoriteLogic->errorcode,
                'message' => Config::get ('errorcode') [$favoriteLogic->errorcode]
        ];  
    }
    
    /**
     * 我收藏的联赛列表
     * @param
     * @return
     */
    public function myFavoriteMatchesList(){
    	$favoriteLogic = Loader::model('UserFavoriteMatches','logic');
    	$list = $favoriteLogic->getFavoriteMatchesList();
        return [
                 'errorcode' =>$favoriteLogic->errorcode,
                 'message'   => Config::get('errorcode') [$favoriteLogic->errorcode], 
                 'data'      => output_format($list)
        ];
    }
}