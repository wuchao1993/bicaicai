<?php

namespace app\common\behavior;

use think\Controller;
use think\Exception;
use think\Response;
use think\exception\HttpResponseException;

class Cross extends Controller {

    public function run(&$dispatch) {
        $hostName = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "*";
        $headers = [
            "Access-Control-Allow-Origin"      => $hostName,
            "Access-Control-Allow-Credentials" => 'true',
            "Access-Control-Allow-Headers"     => "x-token,x-uid,x-token-check,x-requested-with,content-type,Host,Auth-Token,Auth-Identity,auth-token,auth-identity,Sign,Authorization",
            "Access-Control-Expose-Headers"    => 'auth-token,auth-identity'
        ];
        if($dispatch instanceof Response) {
            $dispatch->header($headers);
        } else if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $type = $this->getResponseType();
            $response = Response::create('', '', 200, $headers);
            throw new HttpResponseException($response);
        }
    }
}