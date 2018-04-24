<?php

namespace app\admin\service;

use curl\Curlrequest;

class LotteryHalper {

    // const LOTTERY_CENTER_API_URL = 'http://www.kosun.cc/Home/Api/checkPrizeNumber';

    // public static function checkPrizeNumber($lotteryId, $prizeNumber) {

    //     $requestData = [
    //         'lottery_id'   => $lotteryId,
    //         'prize_number' => $prizeNumber,
    //     ];

    //     $result = Curlrequest::post(self::LOTTERY_CENTER_API_URL, $requestData);

    //     return $result;
    // }

    //新的验证方式
    public static function checkPrizeNumberNew($lotteryId, $prizeNum)
    {
        $apiUrl = 'http://digital.lotterycenter.kosun.cc/api/Index/checkPrizeNumber';
        //外网
        // $apiUrl = 'http://e-api.kosun.cc/api/Index/checkPrizeNumber';
        $request_data = array(
            'lottery_id' => $lotteryId,
            'prize_number' => $prizeNum,
        );

        $response = Curlrequest::post($apiUrl, $request_data);

        return $response;
    }

}
