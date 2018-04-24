<?php
/**
 * 封装curl请求
 * @author cary17316@gmail.com
 * @createTime 2017/3/22 11:04
 */

namespace curl;

class Curlrequest
{

    /**
     * get请求
     * @param $url
     * @param int $timeOut
     * @param array $header
     * @return mixed
     */
    public static function get($url, $header = array(), $timeOut = 10)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if ($header) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        if (1 == strpos('$' . $url, 'https://')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    /**
     * post请求
     * @param $url
     * @param $params
     * @param array $header
     * @param int $timeOut
     * @return mixed
     */
    public static function post($url, $params = [], $header = array(), $timeOut = 10)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if ($header) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        if (1 == strpos('$' . $url, 'https://')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }


    public static function curlJsonPost($url, $data, $header = [])
    {
        $referer = $_SERVER['HTTP_HOST'];
        $user_agent = explode('.',$_SERVER['HTTP_HOST'])[0];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_REFERER,$referer);
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 2);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if (1 == strpos('$' . $url, 'https://')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (!$content = curl_exec($curl)) {
            dump(curl_error($curl));
        }
        curl_close($curl);
        return $content;
    }
}