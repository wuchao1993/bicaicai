<?php

namespace upush;
use Exception;
use think\Log;

abstract class UmengNotification {
	// The host
	protected $host = "http://msg.umeng.com";

	// The upload path
	protected $uploadPath = "/upload";

	// The post path
	protected $postPath = "/api/send";

	// The app master secret
	protected $appMasterSecret = NULL;

	/*
	 * $data is designed to construct the json string for POST request. Note:
	 * 1)The key/value pairs in comments are optional.  
	 * 2)The value for key 'payload' is set in the subclass(AndroidNotification or IOSNotification), as their payload structures are different.
	 */ 
	protected $data = array(
			"appkey"           => NULL,
			"timestamp"        => NULL,
			"type"             => NULL,
			//"device_tokens"  => "xx",
			//"alias"          => "xx",
			//"file_id"        => "xx",
			//"filter"         => "xx",
			//"policy"         => array("start_time" => "xx", "expire_time" => "xx", "max_send_num" => "xx"),
			"production_mode"  => "true",
			//"feedback"       => "xx",
			//"description"    => "xx",
			//"thirdparty_id"  => "xx"
	);

	protected $DATA_KEYS    = array("appkey", "timestamp", "type", "device_tokens", "alias", "alias_type", "file_id", "filter", "production_mode",
								    "feedback", "description", "thirdparty_id");
	protected $POLICY_KEYS  = array("start_time", "expire_time", "max_send_num");

	function __construct() {

	}

	function setAppMasterSecret($secret) {
		$this->appMasterSecret = $secret;
	}
	
	//return TRUE if it's complete, otherwise throw exception with details
	function isComplete() {
		if (is_null($this->appMasterSecret))
			throw new Exception("Please set your app master secret for generating the signature!");
		$this->checkArrayValues($this->data);
		return TRUE;
	}

	private function checkArrayValues($arr) {
		foreach ($arr as $key => $value) {
			if (is_null($value))
				throw new Exception($key . " is NULL!");
			else if (is_array($value)) {
				$this->checkArrayValues($value);
			}
		}
	}

	// Set key/value for $data array, for the keys which can be set please see $DATA_KEYS, $PAYLOAD_KEYS, $BODY_KEYS, $POLICY_KEYS
	abstract function setPredefinedKeyValue($key, $value);

	//send the notification to umeng, return response data if SUCCESS , otherwise throw Exception with details.
	function send() {
    	$this->isComplete();
        $url = $this->host . $this->postPath;
        Log::write("send_url:" . $url, Log::INFO, '', LOG_PATH . "umeng_notification." . date('YmdH') . "log");
        $postBody = json_encode($this->data);
        $sign = md5("POST" . $url . $postBody . $this->appMasterSecret);
        $url = $url . "?sign=" . $sign;
  		$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody );
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($httpCode == "0") {
           	throw new Exception("Curl error number:" . $curlErrNo . " , Curl error details:" . $curlErr . "\r\n");
        } else if ($httpCode != "200") {
            Log::write("result200:".$this->data['appkey'] .":". print_r($result, true), Log::INFO, '', LOG_PATH . "push_log." . date('YmdH') . "log");
            return $result;
        } else {
            Log::write("result:".$this->data['appkey'] .":". print_r($result, true), Log::INFO, '', LOG_PATH . "push_log." . date('YmdH') . "log");
           	return $result;
        }
    }
	
}