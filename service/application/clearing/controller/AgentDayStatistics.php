<?php
namespace app\clearing\controller;

use think\Loader;
use think\Request;

class AgentDayStatistics{

    public function statistics(Request $request){
        $date = $request->post('date');
        if(strtotime($date) >= strtotime(date('Y-m-d'))){
            return false;
        }
        $startDate = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d', strtotime('-1 day'));
        $params['startDate'] = "$startDate 00:00:00";
        $params['endDate']   = "$startDate 23:59:59";
        $result = Loader::model('AgentDayStatistics', 'logic')->statisticsUserDayInfos($params);
        if($result){
            Loader::model('AgentDayStatistics', 'logic')->statisticsAgentDayInfos($params);
        }
        echo $startDate;
    }

}