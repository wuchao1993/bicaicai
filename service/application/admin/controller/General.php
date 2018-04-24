<?php
/**
 * 公共接口
 * @createTime 2017/3/22 11:04
 */

namespace app\admin\controller;

use think\Config;
use think\Loader;
use think\Request;
use alioss\Oss;
use think\Session;

class General {

    /**
     * 刷新Token
     * @return array
     */
    public function refreshToken(Request $request) {
        $token        = $request->param('Token');
        $generalLogic = Loader::model('General', 'logic');
        $token        = $generalLogic->refreshToken($token);
        if(false === $token) {
            return [
                'errorcode' => $generalLogic->errorcode,
                'message'   => Config::get('errorcode')[$generalLogic->errorcode],
            ];
        }

        return json([
            'errorcode' => EC_AD_SUCCESS,
            'message'   => Config::get('errorcode')[EC_AD_SUCCESS],
        ], 200, ['Auth-Token' => $token]);
    }

    /**
     * 获取验证码
     * @return array
     */
    public function captcha() {
        return captcha('', Config::get('captcha'));
    }

    /**
     * 上传图片
     * @return string
     */
    public function upload() {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');

        if(!$file) {
            return [
                'errorcode' => EC_AD_UPLOAD_ILLEGAL,
                'message'   => Config::get('errorcode')[EC_AD_UPLOAD_ILLEGAL],
            ];
        }

        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate([
            'size' => 10442600,
            'ext'  => 'jpg,png,gif,jpeg,pfx',
        ])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if($info) {
            // 成功上传后 获取上传信息
            // 输出 jpg
            //echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getSaveName();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getFilename();

            $fileName  = 'uploads' . DS . $info->getSaveName();
            $ossClient = Oss::getInstance();
            $bucket    = Oss::getBucketName();
            $data      = $ossClient->uploadFile($bucket, $fileName, $info->getPathname());

            if(isset($data['info'])) {
                $imageUrl = $data['info']['url'];
                $imageUrl = str_replace(\oss_config::OSS_BUCKET.".".\oss_config::OSS_ENDPOINT, \oss_config::CDN_HOST_NAME, $imageUrl);
                $imageUrl = str_replace('http://', 'https://', $imageUrl);
            } else {
                $imageUrl = $_SERVER['HTTP_HOST'] . '/uploads' . DS . $info->getSaveName();
            }

            return [
                'errorcode' => EC_AD_SUCCESS,
                'message'   => Config::get('errorcode')[EC_AD_SUCCESS],
                'data'      => $imageUrl,
            ];
        } else {
            // 上传失败返回错误信息
            return [
                'errorcode' => EC_AD_UPLOAD_ERROR,
                'message'   => $file->getError(),
            ];
        }
    }

    /**
     * 获取推送服务器信息
     * @return array
     */
    public function getPushHostInfo(Request $request) {

        $data = [
            'host' => $_SERVER['HTTP_HOST'],
            'port' => 9501,
        ];

        return [
            'errorcode' => EC_AD_SUCCESS,
            'message'   => Config::get('errorcode')[EC_AD_SUCCESS],
            'data'      => $data,
        ];
    }

    /**
     * 获取网站基本设置列表
     *
     * @param Request $request
     * @return array
     */
    public function getSiteConfigList(Request $request) {

        $configLogic = Loader::model ( 'ConfigManagement', 'logic' );
        $groupList = $configLogic->getSiteConfigList ();

        return [
            'errorcode' => $configLogic->errorcode,
            'message' => Config::get ( 'errorcode' ) [$configLogic->errorcode],
            'data' => output_format ( $groupList )
        ];
    }

     /**
      * 获取 subscribeName
      * @param Request $request
      * @return mixed
      */
     public function serviceInit(Request $request){
        $subscribeName = $request->param('subscribeName');
        $RequestSwoole = Loader::model('RequestSwoole', 'logic');
        $RequestSwoole->serviceInit($subscribeName);

        return [
            'errorcode' => $RequestSwoole->errorcode,
            'message'   => Config::get('errorcode')[$RequestSwoole->errorcode],

        ];
     }

}
