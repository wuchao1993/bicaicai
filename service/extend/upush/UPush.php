<?php

namespace upush;

use upush\android\AndroidBroadcast;
use upush\android\AndroidFilecast;
use upush\android\AndroidGroupcast;
use upush\android\AndroidUnicast;
use upush\android\AndroidCustomizedcast;
use upush\ios\IOSBroadcast;
use upush\ios\IOSFilecast;
use upush\ios\IOSGroupcast;
use upush\ios\IOSListcast;
use upush\ios\IOSUnicast;
use upush\ios\IOSCustomizedcast;
use think\Log;

class UPush
{
    protected $appkey = NULL;
    protected $appMasterSecret = NULL;
    protected $timestamp = NULL;
    protected $validation_token = NULL;

    function __construct($key, $secret)
    {
        $this->appkey = $key;
        $this->appMasterSecret = $secret;
        $this->timestamp = strval(time());
    }

    function sendAndroidBroadcast($message_info)
    {
        try {
            
            $filter = array(
                "where" => array(
                    "and" => array(
                        "not" => array(
                            "country" => "美国"
                         )
                    )
                )
            );

            $brocast = new AndroidBroadcast();
            $brocast->setAppMasterSecret($this->appMasterSecret);
            $brocast->setPredefinedKeyValue("appkey", $this->appkey);
            $brocast->setPredefinedKeyValue("timestamp", $this->timestamp);
            $brocast->setPredefinedKeyValue("filter", $filter);
            $brocast->setPredefinedKeyValue("ticker", "Android broadcast ticker");
            $brocast->setPredefinedKeyValue("title", $message_info['pm_title']);
            $brocast->setPredefinedKeyValue("text", $message_info['pm_content']);
            $brocast->setPredefinedKeyValue("after_open", "go_app");
            $brocast->setPredefinedKeyValue("production_mode", "true");
//             $brocast->setExtraField("test", "helloworld");
            return $brocast->send();
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendAndroidUnicast($message_info, $device_token)
    {
        try {
            $unicast = new AndroidUnicast();
            $unicast->setAppMasterSecret($this->appMasterSecret);
            $unicast->setPredefinedKeyValue("appkey", $this->appkey);
            $unicast->setPredefinedKeyValue("timestamp", $this->timestamp);
            $unicast->setPredefinedKeyValue("device_tokens", $device_token);
            $unicast->setPredefinedKeyValue("ticker", $message_info['pm_title']);
            $unicast->setPredefinedKeyValue("title", $message_info['pm_title']);
            $unicast->setPredefinedKeyValue("text", $message_info['pm_content']);
            $unicast->setPredefinedKeyValue("after_open", "go_app");
            // Set 'production_mode' to 'false' if it's a test device.
            // For how to register a test device, please see the developer doc.
            $unicast->setPredefinedKeyValue("production_mode", "true");
            // Set extra fields
//             $unicast->setExtraField("test", "helloworld");
            return $unicast->send();
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendAndroidFilecast()
    {
        try {
            $filecast = new AndroidFilecast();
            $filecast->setAppMasterSecret($this->appMasterSecret);
            $filecast->setPredefinedKeyValue("appkey", $this->appkey);
            $filecast->setPredefinedKeyValue("timestamp", $this->timestamp);
            $filecast->setPredefinedKeyValue("ticker", "Android filecast ticker");
            $filecast->setPredefinedKeyValue("title", "Android filecast title");
            $filecast->setPredefinedKeyValue("text", "Android filecast text");
            $filecast->setPredefinedKeyValue("after_open", "go_app");  //go to app
            print("Uploading file contents, please wait...\r\n");
            // Upload your device tokens, and use '\n' to split them if there are multiple tokens
            $filecast->uploadContents("aa" . "\n" . "bb");
            print("Sending filecast notification, please wait...\r\n");
            $filecast->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendAndroidGroupcast()
    {
        try {
            /*
              *  Construct the filter condition:
              *  "where":
              *	{
              *		"and":
              *		[
                *			{"tag":"test"},
                *			{"tag":"Test"}
              *		]
              *	}
              */
            $filter = array(
                "where" => array(
                    "and" => array(
                        array(
                            "tag" => "test"
                        ),
                        array(
                            "tag" => "Test"
                        )
                    )
                )
            );

            $groupcast = new AndroidGroupcast();
            $groupcast->setAppMasterSecret($this->appMasterSecret);
            $groupcast->setPredefinedKeyValue("appkey", $this->appkey);
            $groupcast->setPredefinedKeyValue("timestamp", $this->timestamp);
            // Set the filter condition
            $groupcast->setPredefinedKeyValue("filter", $filter);
            $groupcast->setPredefinedKeyValue("ticker", "Android groupcast ticker");
            $groupcast->setPredefinedKeyValue("title", "Android groupcast title");
            $groupcast->setPredefinedKeyValue("text", "Android groupcast text");
            $groupcast->setPredefinedKeyValue("after_open", "go_app");
            // Set 'production_mode' to 'false' if it's a test device.
            // For how to register a test device, please see the developer doc.
            $groupcast->setPredefinedKeyValue("production_mode", "true");
            print("Sending groupcast notification, please wait...\r\n");
            $groupcast->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendAndroidCustomizedcast()
    {
        try {
            $customizedcast = new AndroidCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey", $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp", $this->timestamp);
            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias", "xx");
            // Set your alias_type here
            $customizedcast->setPredefinedKeyValue("alias_type", "xx");
            $customizedcast->setPredefinedKeyValue("ticker", "Android customizedcast ticker");
            $customizedcast->setPredefinedKeyValue("title", "Android customizedcast title");
            $customizedcast->setPredefinedKeyValue("text", "Android customizedcast text");
            $customizedcast->setPredefinedKeyValue("after_open", "go_app");
            print("Sending customizedcast notification, please wait...\r\n");
            $customizedcast->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendAndroidCustomizedcastFileId()
    {
        try {
            $customizedcast = new AndroidCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey", $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp", $this->timestamp);
            // if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->uploadContents("aa" . "\n" . "bb");
            // Set your alias_type here
            $customizedcast->setPredefinedKeyValue("alias_type", "xx");
            $customizedcast->setPredefinedKeyValue("ticker", "Android customizedcast ticker");
            $customizedcast->setPredefinedKeyValue("title", "Android customizedcast title");
            $customizedcast->setPredefinedKeyValue("text", "Android customizedcast text");
            $customizedcast->setPredefinedKeyValue("after_open", "go_app");
            print("Sending customizedcast notification, please wait...\r\n");
            $customizedcast->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }


    function sendAndroidListcast($message_info, $device_tokens)
    {
        try {
            $unicast = new AndroidUnicast();
            $unicast->setAppMasterSecret($this->appMasterSecret);
            $unicast->setPredefinedKeyValue("appkey", $this->appkey);
            $unicast->setPredefinedKeyValue("timestamp", $this->timestamp);
            $unicast->setPredefinedKeyValue("device_tokens", join(',', $device_tokens));
            $unicast->setPredefinedKeyValue("ticker", $message_info['pm_title']);
            $unicast->setPredefinedKeyValue("title", $message_info['pm_title']);
            $unicast->setPredefinedKeyValue("text", $message_info['pm_content']);
            $unicast->setPredefinedKeyValue("after_open", "go_app");
            $unicast->setPredefinedKeyValue("production_mode", "true");
//             $unicast->setExtraField("test", "helloworld");
            $unicast->send();
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }


    function sendIOSBroadcast($message_info)
    {
        Log::write("sendIOSBroadcast:".print_r($message_info,true), Log::INFO, '', LOG_PATH."send_push.log");
        try {

            $filter = array(
                "where" => array(
                    "and" => array(
                        "not" => array(
                            "country" => "美国"
                        )
                    )
                )
            );

            $brocast = new IOSBroadcast();
            $brocast->setAppMasterSecret($this->appMasterSecret);
            $brocast->setPredefinedKeyValue("appkey", $this->appkey);
            $brocast->setPredefinedKeyValue("timestamp", $this->timestamp);
            $brocast->setPredefinedKeyValue("filter", $filter);
            $brocast->setPredefinedKeyValue("alert", $message_info['pm_title']);
            $brocast->setPredefinedKeyValue("badge", 0);
            $brocast->setPredefinedKeyValue("sound", "chime");
            $brocast->setPredefinedKeyValue("production_mode", "true");
            $brocast->setCustomizedField("content", $message_info['pm_content']);
            return $brocast->send();
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendIOSUnicast($message_info, $device_token)
    {
        try {
            $unicast = new IOSUnicast();
            $unicast->setAppMasterSecret($this->appMasterSecret);
            $unicast->setPredefinedKeyValue("appkey", $this->appkey);
            $unicast->setPredefinedKeyValue("timestamp", $this->timestamp);
            $unicast->setPredefinedKeyValue("device_tokens", $device_token);
            $unicast->setPredefinedKeyValue("alert", $message_info['pm_title']);
            $unicast->setPredefinedKeyValue("content-available", $message_info['pm_content']);
            $unicast->setPredefinedKeyValue("badge", 0);
            $unicast->setPredefinedKeyValue("sound", "chime");
            $unicast->setPredefinedKeyValue("production_mode", "true");
            $unicast->setCustomizedField("content", $message_info['pm_content']);
            return $unicast->send();
        } catch (Exception $e) {
            return false;
//            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendIOSFilecast()
    {
        try {
            $filecast = new IOSFilecast();
            $filecast->setAppMasterSecret($this->appMasterSecret);
            $filecast->setPredefinedKeyValue("appkey", $this->appkey);
            $filecast->setPredefinedKeyValue("timestamp", $this->timestamp);

            $filecast->setPredefinedKeyValue("alert", "IOS 文件播测试");
            $filecast->setPredefinedKeyValue("badge", 0);
            $filecast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $filecast->setPredefinedKeyValue("production_mode", "false");
            print("Uploading file contents, please wait...\r\n");
            // Upload your device tokens, and use '\n' to split them if there are multiple tokens
            $filecast->uploadContents("aa" . "\n" . "bb");
            print("Sending filecast notification, please wait...\r\n");
            $filecast->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendIOSGroupcast()
    {
        try {
            /*
              *  Construct the filter condition:
              *  "where":
              *	{
              *		"and":
              *		[
                *			{"tag":"iostest"}
              *		]
              *	}
              */
            $filter = array(
                "where" => array(
                    "and" => array(
                        array(
                            "tag" => "iostest"
                        )
                    )
                )
            );

            $groupcast = new IOSGroupcast();
            $groupcast->setAppMasterSecret($this->appMasterSecret);
            $groupcast->setPredefinedKeyValue("appkey", $this->appkey);
            $groupcast->setPredefinedKeyValue("timestamp", $this->timestamp);
            // Set the filter condition
            $groupcast->setPredefinedKeyValue("filter", $filter);
            $groupcast->setPredefinedKeyValue("alert", "IOS 组播测试");
            $groupcast->setPredefinedKeyValue("badge", 0);
            $groupcast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $groupcast->setPredefinedKeyValue("production_mode", "false");
            print("Sending groupcast notification, please wait...\r\n");
            $groupcast->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendIOSCustomizedcast()
    {
        try {
            $customizedcast = new IOSCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey", $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp", $this->timestamp);

            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias", "xx");
            // Set your alias_type here
            $customizedcast->setPredefinedKeyValue("alias_type", "xx");
            $customizedcast->setPredefinedKeyValue("alert", "IOS 个性化测试");
            $customizedcast->setPredefinedKeyValue("badge", 0);
            $customizedcast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $customizedcast->setPredefinedKeyValue("production_mode", "false");
            print("Sending customizedcast notification, please wait...\r\n");
            $customizedcast->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    function sendIOSListcast($message_info, $device_tokens){
        Log::write("sendIOSListcast:".print_r($message_info,true), Log::INFO, '', LOG_PATH."report_device_api.log");
        try {
            $customizedcast = new IOSListcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey", $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp", $this->timestamp);
            $customizedcast->setPredefinedKeyValue("device_tokens", join(',', $device_tokens));
//            $customizedcast->setPredefinedKeyValue("alias", "xx");
//            $customizedcast->setPredefinedKeyValue("alias_type", "xx");
            $customizedcast->setPredefinedKeyValue("content-available", $message_info['pm_content']);
            $customizedcast->setPredefinedKeyValue("alert", $message_info['pm_title']);
            $customizedcast->setPredefinedKeyValue("badge", 0);
            $customizedcast->setPredefinedKeyValue("sound", "chime");
            $customizedcast->setPredefinedKeyValue("production_mode", "true");
            $customizedcast->setCustomizedField("content", $message_info['pm_content']);
            $customizedcast->send();
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }
}
