<?php
/**
 * 通用加密类
 * @author cary17316@gmail.com
 * @createTime 2017/3/22 11:04
 */
namespace encrypt;

class Encrypt
{
    /**
     * 加密的私钥
     * @var string
     */
    private $key = '0aac19918e8b865210332d891f29cd6f';

    /**
     * 加密的类型
     * @var string
     */
    private $alg = 'des';

    /**
     * 加密的模式，目前只支持ecb;
     * 适合对小数量随机数据的加密，比如加密用户的登录密码之类的;
     * 在ecb模式下,初始向量会被忽略;    
     * 在cfb,cbc和ofb模式下,必须提供初始向量,并且加密和解密必须使用相同的初始向量
     * @var string
     */
    private $mode = 'ecb';

    /**
     * 构造函数
     */
    public function __construct() {}

    /**
     * 外部自己设置key
     * @param string $key 密钥
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * 设置加密类型
     * @param string $alg 类型
     */
    public function setAlg($alg)
    {
        $this->alg = $alg;
    }

    /**
     * 设置加密模式
     * @param string $mode 模式
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * des加密算法，ecb模式，需要mcrypt扩展
     * @param  string $content 需要加密的内容
     * @return string
     */
    public function desEncrypt($content)
    {
        // 获取算法的分组大小
        $size    = mcrypt_get_block_size($this->alg, $this->mode);
        $content = $this->pkcs5Pad($content, $size);
        $td      = mcrypt_module_open($this->alg, '', $this->mode, '');

        // 用随机iv，加密解密可以不一样
        $ivSize = mcrypt_enc_get_iv_size($td);
        $iv     = mcrypt_create_iv($ivSize, MCRYPT_RAND);

        // 获取密钥最大长度
        $ks     = mcrypt_enc_get_key_size($td);
        $newKey = substr(md5($this->key), 0, $ks);

        mcrypt_generic_init($td, $newKey, $iv);

        // 加密
        $encode = mcrypt_generic($td, $content);

        // 释放资源
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        // 加密结果是二进制，返回大写十六进制字符串
        return strtoupper(bin2hex($encode));
    }

    /**
     * des解密算法，ecb模式,需要mcrypt扩展
     * @param  string $content 需要解密的内容
     * @return string
     */
    public function desDecrypt($content)
    {
        $content = $this->hex2bin(strtolower($content));
        $td      = mcrypt_module_open($this->alg, '', $this->mode, '');
        $iv      = $this->key;

        // 获取密钥最大长度
        $ks     = mcrypt_enc_get_key_size($td);
        $newKey = substr(md5($this->key), 0, $ks);

        // 用随机iv，加密解密可以不一样
        $ivSize = mcrypt_enc_get_iv_size($td);
        $iv     = mcrypt_create_iv($ivSize, MCRYPT_RAND);

        mcrypt_generic_init($td, $newKey, $iv);

        // 解密
        $content = mdecrypt_generic($td, $content);

        // 释放资源
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $this->pkcs5Unpad($content);
    }

    /**
     * 十六进制转换成二进制
     * @param  string $hexData 十六进制字符串
     * @return string
     */
    private function hex2bin($hexData)
    {
        $binData = '';
        for ($i = 0; $i < strlen($hexData); $i += 2) {
            $binData .= chr(hexdec(substr($hexData, $i, 2)));
        }

        return $binData;
    }

    /**
     * 重新处理加密字符串
     * @param  string $content   加密内容
     * @param  int    $blocksize
     * @return string
     */
    private function pkcs5Pad($content, $blocksize)
    {
        $pad = $blocksize - (strlen($content) % $blocksize);
        return $content . str_repeat(chr($pad), $pad);
    }

    /**
     * 处理加密字符串
     * @param  string $content
     * @return string
     */
    private function pkcs5Unpad($content)
    {
        $pad = ord($content[strlen($content) - 1]);
        if ($pad > strlen($content)) {
            return false;
        }

        if (strspn($content, chr($pad), strlen($content) - $pad) != $pad) {
            return false;
        }

        return substr($content, 0, - 1 * $pad);
    }

    /**
     *  加密方式A，目前用来密码传输的加密
     *  加密流程
     *  1. base64编码字符串，得到字符串S1
     *  2. 依次取出S1的字符，并将字符转为ASCII值，在该值后面加上一个随机字符，随机字符取值为a-z
     *     A-Z，组合得到一个新的字符串S2，
     *  3. 对S2进行base64编码得到字符串S3
     *  4. 将S3+'/'+md5(s3)得到字符串S4
     *  5. 将S4进行base64编码得到字符串S5
     *  6. 将S5字符串进行反转就为最终加密后的字符串
     *
     * @param  string 所需要加密的字符串
     * @return string
     */
    function encryptA($str)
    {
        // 1. base64编码
        $str = base64_encode($str);
        $len = strlen($str);

        // 2. 组合S2
        $randStr = 'abcdefghigklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $tmp = '';
        for ($i = 0; $i < $len; $i++) {
            $j    = rand(0, 51);
            $chr  = $randStr[$j];
            $tmp .= ord($str[$i]) . $chr;
        }

        // 3. base64编码
        $str = base64_encode($tmp);

        // 4. 签名
        $str = $str . '/' .md5($str);

        // 5. base64编码
        $str = base64_encode($str);

        // 6. 反转
        return strrev($str);
    }

    /**
     *  解密方式A
     *  解密流程
     *  1. 反转字符串
     *  2. base64反编码
     *  3. 取签名前后的值,并验证签名
     *  4. base64反编码
     *  5. 去除随机字符,将ASCII转换为字符
     *  6. base64反编码
     *
     * @param  string 所需要解密的字符串
     * @return string
     */
    function decryptA($str)
    {
        // 1. 反转字符串，base64反编码
        $str = strrev($str);
        $str = base64_decode($str);

        // 2. 签名验证
        $pos = strrpos($str, '/');
        $tmp = substr($str, 0, $pos);
        $md5 = subStr($str, $pos+1);

        if (md5($tmp) != strtolower($md5)) {
            return false;
        }

        // 3. 反编码
        $str = base64_decode($tmp);
        $len = strlen($str);

        // 4. 去除随机字符
        $tmp = '';
        $chr = '';
        for ($i = 0; $i < $len; $i++) {
            if (is_numeric($str[$i])) {
                $chr .= $str[$i];
            } else {
                $tmp .= chr($chr);
                $chr  = '';
            }
        }

        // 5. 反编码
        return base64_decode($tmp);
    }

    /**
     *  加密方式rsa，非对称加密
     *
     * @param  string 所需要加密的字符串
     * @param  string public or private 公钥加密还是私钥加密
     * @param  string 密钥
     * @param  int rsa 申请密钥长度，默认是1024位
     * @return string
     */
    function encryptRsa($str, $type = 'public', $key = '', $len = 1024)
    {
        if($type == 'public') {
            $defaultKey = "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCw4T0CWMQ+g5kuvrTqErvIwf/4\nOpa1UdiEgQZ7xQPYkMcSKCxqDYw0keFcERXtbK64RmuuIFlpjbY0k1yYnRPSVsNF\nkf5ncTvC9Aw5Y5r76P+SO+qpxNv6KjGwQajmfN7vM+k8buJub8Vcv1RoLg83K7tm\nEcwnLDN+ke7B3GgpEQIDAQAB\n-----END PUBLIC KEY-----\n";
            $defaultKey = $key ? $key : $defaultKey;
            $defaultId  = openssl_pkey_get_public($defaultKey);
        } else {
            $defaultKey = "-----BEGIN RSA PRIVATE KEY-----\nMIICXQIBAAKBgQCw4T0CWMQ+g5kuvrTqErvIwf/4Opa1UdiEgQZ7xQPYkMcSKCxq\nDYw0keFcERXtbK64RmuuIFlpjbY0k1yYnRPSVsNFkf5ncTvC9Aw5Y5r76P+SO+qp\nxNv6KjGwQajmfN7vM+k8buJub8Vcv1RoLg83K7tmEcwnLDN+ke7B3GgpEQIDAQAB\nAoGACbt69eQYeDAZf57yqWZ6eqNiCDCBFlz4guHuj2TZv1LIAVHAj91K96GHOt+d\ny0CNpIoLZrtU8B/iBKaPE76g1nyPout7O13C/DPD7gNHYRSY6hFAzJzLw7PMyi35\nTVYbaiPKNww0p8l5tblact0TAHqWy7yL9c9jzpA/qyfoG6ECQQDZHH4JMfFhd1nU\nuOSRSwhsidJjcP2mkntZo47cgbRAJRavkIfMs5Te+/7qyfJlaUcAGNFasjIhYcDU\nUpKJhqE9AkEA0I/04/FRN3+M2XWQy66hRzc6gl7YuwMtWIrdwXsERpyUR0R784/T\nl8eT2eyOheytwA++pcz5xiAe2te4FgV8ZQJBAIRc9Y3/j8y/Kdohmt/loc4iPEz5\n7vplpaQhrhBLVywgMHN6pwAqn+FOOrzDv+8JvwqVFtW3fA6T/S605LfJh3UCQFCI\nZc1mry+45tBJX0HnCouPPd59dT6xOV9JL9u3/qytZWwne51O2itvex3ZBCeefnD9\nI2auQXxJhuCGD6UhNSkCQQC66VZwONggWd5IXH2rsS7s+nQeXW4XH8xKvubJEwZA\nbgwNRk7XE7OFF6jeNt14amRjmk1cSjAdT2pP5QHrkeP9\n-----END RSA PRIVATE KEY-----\n";
            $defaultKey = $key ? $key : $defaultKey;
            $defaultId  = openssl_pkey_get_private($defaultKey);
        }

        // 1.获取所能加密的明文长度字节
        $pad = $len/8;
        $pad = $pad-11;

        // 2.获取明文长度
        $strLen = strlen($str);
        $mod    = ceil($strLen/$pad);
        $return = '';
        for ($i=0; $i<$mod; $i++) {
            $pos = $i*$pad;
            $tmp = substr($str, $pos, $pad);
            if($type == 'public') {
                openssl_public_encrypt($tmp, $rs, $defaultId);
            } else {
                openssl_private_encrypt($tmp, $rs, $defaultId);
            }
            $return .= $rs;
        }

        return $return;
    }

    /**
     *  解密方式rsa，非对称加密
     *
     * @param  string 所需要解密的字符串
     * @param  string public or private 公钥解密还是私钥解密
     * @param  string 密钥
     * @param  int rsa 申请密钥长度，默认是1024位
     * @return string
     */
    function decryptRsa($str, $type = 'private', $key = '', $len = 1024)
    {
        if($type == 'public') {
            $defaultKey = "-----BEGIN PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCw4T0CWMQ+g5kuvrTqErvIwf/4\nOpa1UdiEgQZ7xQPYkMcSKCxqDYw0keFcERXtbK64RmuuIFlpjbY0k1yYnRPSVsNF\nkf5ncTvC9Aw5Y5r76P+SO+qpxNv6KjGwQajmfN7vM+k8buJub8Vcv1RoLg83K7tm\nEcwnLDN+ke7B3GgpEQIDAQAB\n-----END PUBLIC KEY-----\n";
            $defaultKey = $key ? $key : $defaultKey;
            $defaultId  = openssl_pkey_get_public($defaultKey);
        } else {
            $defaultKey = "-----BEGIN RSA PRIVATE KEY-----\nMIICXQIBAAKBgQCw4T0CWMQ+g5kuvrTqErvIwf/4Opa1UdiEgQZ7xQPYkMcSKCxq\nDYw0keFcERXtbK64RmuuIFlpjbY0k1yYnRPSVsNFkf5ncTvC9Aw5Y5r76P+SO+qp\nxNv6KjGwQajmfN7vM+k8buJub8Vcv1RoLg83K7tmEcwnLDN+ke7B3GgpEQIDAQAB\nAoGACbt69eQYeDAZf57yqWZ6eqNiCDCBFlz4guHuj2TZv1LIAVHAj91K96GHOt+d\ny0CNpIoLZrtU8B/iBKaPE76g1nyPout7O13C/DPD7gNHYRSY6hFAzJzLw7PMyi35\nTVYbaiPKNww0p8l5tblact0TAHqWy7yL9c9jzpA/qyfoG6ECQQDZHH4JMfFhd1nU\nuOSRSwhsidJjcP2mkntZo47cgbRAJRavkIfMs5Te+/7qyfJlaUcAGNFasjIhYcDU\nUpKJhqE9AkEA0I/04/FRN3+M2XWQy66hRzc6gl7YuwMtWIrdwXsERpyUR0R784/T\nl8eT2eyOheytwA++pcz5xiAe2te4FgV8ZQJBAIRc9Y3/j8y/Kdohmt/loc4iPEz5\n7vplpaQhrhBLVywgMHN6pwAqn+FOOrzDv+8JvwqVFtW3fA6T/S605LfJh3UCQFCI\nZc1mry+45tBJX0HnCouPPd59dT6xOV9JL9u3/qytZWwne51O2itvex3ZBCeefnD9\nI2auQXxJhuCGD6UhNSkCQQC66VZwONggWd5IXH2rsS7s+nQeXW4XH8xKvubJEwZA\nbgwNRk7XE7OFF6jeNt14amRjmk1cSjAdT2pP5QHrkeP9\n-----END RSA PRIVATE KEY-----\n";
            $defaultKey = $key ? $key : $defaultKey;
            $defaultId  = openssl_pkey_get_private($defaultKey);
        }

        // 1.获取所能加密的明文长度字节
        $pad = $len/8;

        // 2.获取明文长度
        $strLen = strlen($str);
        $mod    = ceil($strLen/$pad);
        $return = '';
        for ($i=0; $i<$mod; $i++) {
            $pos = $i*$pad;
            $tmp = substr($str, $pos, $pad);
            if($type == 'public') {
                openssl_public_decrypt($tmp, $rs, $defaultId);
            } else {
                openssl_private_decrypt($tmp, $rs, $defaultId);
            }
            $return .= $rs;
        }

        return $return;
    }

    /**
     *  加密方式AES，对称加密
     *
     * @param  string 所需要加密的字符串
     * @param  string 加密的密钥32字节
     * @return string
     */
    function encryptAes($str, $key)
    {
        // 1.随机种子
        $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
        $iv   = mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);

        // 2.加密
        $str = mcrypt_encrypt('rijndael-256', $key, $str, 'ecb', $iv);
        return $str;
    }

    /**
     *  解密方式AES，对称加密
     *
     * @param  string 所需要解密的字符串
     * @param  string 解密的密钥32字节
     * @return string
     */
    function decryptAes($str, $key)
    {
        // 1.随机种子
        $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
        $iv   = mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);

        // 2.解密
        $str = mcrypt_decrypt('rijndael-256', $key, $str, 'ecb', $iv);
        return $str;
    }
}