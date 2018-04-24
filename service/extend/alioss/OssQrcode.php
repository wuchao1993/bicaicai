<?php

namespace alioss;

use OSS\OssClient;
use think\Env;

class OssQrcode{

    private  $accessKeyId ;
    private  $accessKeySecret;
    private  $bucket = '';
    private  $endpoint ='';

    private static $_instance;
    /**
     * 构造函数
     * Oss constructor.
     */
    public function __construct() {
        $this->accessKeyId = Env::get('oss.access_id');
        $this->accessKeySecret = Env::get('oss.access_key');
        $this->endpoint = Env::get('oss.endpoint');
        $this->bucket = 'cp-sports';
    }
    /**
     * 克隆
     */
    private function __clone() {
    }
    /**
     * 获取一个OssClient实例
     * @return null|OssClient
     */
    public static function getInstance($accessKeyId,$accessKeySecret,$endpoint) {
        if (!(self::$_instance instanceof OssClient)) {
            try {
                self::$_instance = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false);
            } catch (OssException $e) {
                printf(__FUNCTION__ . "creating OssClient instance: FAILED\n");
                printf($e->getMessage() . "\n");
                return null;
            }
        }
        return self::$_instance;
    }

    /***
     * @desc 将本地图片上传到远程服务器
     * @param $fileName
     * @param $pathName
     * @return mixed|string
     */
    public function uploadQrcodeOss($fileName, $pathName)
    {
        $sitename = Env::get('app.site_name');
        $fileName = $sitename.'/qrcode/' . $fileName;
        $ossClient = OssQrcode::getInstance($this->accessKeyId,$this->accessKeySecret,$this->endpoint);
        $data = $ossClient->uploadFile($this->bucket, $fileName, $pathName);
        if(isset($data['info'])) {
            unlink($pathName);
            return '/'.$fileName;
        }
        else {
            Log::write("上传本地图片到Oss服务器错误信息" . print_r($data));
            return false;
        }
    }
}