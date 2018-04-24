<?php

namespace  app\index\controller;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use think\Request;

class Recharge{
    
    public function test(){
        $tfa = new TwoFactorAuth('myapp', 6, 30, 'sha1', new QRServerProvider());
        $secret = $tfa->createSecret();
        dump($secret);
        $code = $tfa->getCode($secret);
        $imgurl = $tfa->getQRCodeImageAsDataUri('my label', $secret);
        dump($imgurl);
        dump($code);
        if(($tfa->verifyCode($secret, $code) === true)){
            dump('ok');
        }else{
            dump('fail');
        }
    }
    
    public function image(){
        $tfa = new TwoFactorAuth('myapp', 6, 30, 'sha1', new QRServerProvider());
        echo '<li>First create a secret and associate it with a user';
        $secret = $tfa->createSecret(160);  // Though the default is an 80 bits secret (for backwards compatibility reasons) we recommend creating 160+ bits secrets (see RFC 4226 - Algorithm Requirements)
        echo '<li>Next create a QR code and let the user scan it:<br><img src="' . $tfa->getQRCodeImageAsDataUri('My label', $secret) . '"><br>...or display the secret to the user for manual entry: ' . chunk_split($secret, 4, ' ');
        $code = $tfa->getCode($secret);
        echo '<li>Next, have the user verify the code; at this time the code displayed by a 2FA-app would be: <span style="color:#00c">' . $code . '</span> (but that changes periodically)';
        echo '<li>When the code checks out, 2FA can be / is enabled; store (encrypted?) secret with user and have the user verify a code each time a new session is started.';
        echo '<li>When aforementioned code (' . $code . ') was entered, the result would be: ' . (($tfa->verifyCode($secret, $code) === true) ? '<span style="color:#0c0">OK</span>' : '<span style="color:#c00">FAIL</span>');
    }
 
    
}