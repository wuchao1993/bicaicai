<?php

namespace qrcode;

use Endroid\QrCode\QrCode;
use think\Config;
use think\Env;

class QrcodeUpload
{

    /***
     * @desc 生成图片二维码
     * @param $userId
     * @param $agl_code
     * @param $ext
     * @return string
     */
    public static function generateQrCode($agl_code)
    {
            $qrCode = new QrCode();
            $url     = Env::get('app.domain');
            $parames = '/invite?invitationCode='.$agl_code;
            $qrCode->setText($url.$parames)
                   ->setSize(Config::get("qrcode.qrcode_img_param")['size'])
                   ->setPadding(Config::get("qrcode.qrcode_img_param")['padding'])
                ->setErrorCorrection(Config::get("qrcode.qrcode_img_param")['ErrorCorrection'])
                ->setForegroundColor(['r' => Config::get("qrcode.qrcode_img_param")['ForegroundColor'],
                    'g' => Config::get("qrcode.qrcode_img_param")['ForegroundColor'],
                    'b' => Config::get("qrcode.qrcode_img_param")['ForegroundColor'],
                    'a' => Config::get("qrcode.qrcode_img_param")['ForegroundColor']])
                ->setBackgroundColor(['r' => Config::get("qrcode.qrcode_img_param")['BackgroundColor'],
                    'g' => Config::get("qrcode.qrcode_img_param")['BackgroundColor'],
                    'b' => Config::get("qrcode.qrcode_img_param")['BackgroundColor'],
                    'a' => Config::get("qrcode.qrcode_img_param")['BackgroundColorEnd']])
                ->setLabel(Config::get("qrcode.qrcode_img_param")['Label'])//二维码下面的描述信息
                ->setLabelFontSize(Config::get("qrcode.qrcode_img_param")['LabelFontSize'])
                ->setImageType(QrCode::IMAGE_TYPE_PNG);
            $path = self::getDir() . self::getQrcodeFileName($agl_code);
            $qrCode->save($path);
            return $path;
    }

    /***
     * @desc 递归创建目录，并返回目录路径
     * @return string
     */
    public static function getDir()
    {
        $root = ROOT_PATH."runtime/";
        return $root;
    }

    /***
     * @desc 创建邀请码二维码图片名称
     * @param $userId
     * @param $qrcode
     * @param $ext
     * @return string
     */
    public static function getQrcodeFileName($qrcode)
    {
        $userId = USER_ID;
        return $userId . '-' . $qrcode . "." . Config::get("qrcode.make_image_ext");
    }


}