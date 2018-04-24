<?php
/**
 * 跟环境有关的配置文件
 * @createTime 2017/3/22 11:04
 */

return [
    // 验证码字符集合
    'codeSet'  => '1234567890',
    // 验证码字体大小(px)
    'fontSize' => 18,
    // 是否画混淆曲线
    'useCurve' => false,
    // 验证码图片高度
    'imageH'   => 40,
    // 验证码图片宽度
    'imageW'   => 120,
    // 验证码位数
    'length'   => 4,
    // 验证成功后是否重置
    'reset'    => true,

    'useImgBg' => false,
    'useNoise' => false,
   // 'storageType' => 'redis'
];