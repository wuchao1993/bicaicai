<?php
/**
 * Rsa加解密类
 * @author Ryan
 * @since  2018-01-09
 */

namespace crypt;

class CryptRsa extends CryptAbstract
{
    public static $cryptName = 'Rsa';
    private $_privateKey;
    private $_publicKey;

    /**
     * RSA加密
     * @param string $input 明文
     * @return string|null
     */
    public function encrypt($input)
    {
        if (!is_string($input)) {
            return null;
        }
        $this->getPublicKey();
        $r = openssl_public_encrypt($input, $encrypted, $this->_publicKey);
        if ($r) {
            return base64_encode($encrypted);
        }
        return null;
    }

    /**
     * RSA解密
     * @param string $encrypted 密文
     * @param string $extend
     * @return string|null
     */
    public function decrypt($encrypted)
    {
        if (!is_string($encrypted)) {
            return null;
        }
        $this->getPrivateKey();
        $r = openssl_private_decrypt(base64_decode($encrypted), $decrypted, $this->_privateKey);

        if ($r) {
            return $decrypted;
        }
        return null;
    }

    /**
     * 获取私钥
     * @return bool
     */
    public function getPrivateKey()
    {
        if (is_resource($this->_privateKey)) {
            return true;
        }
        $privateKey = file_get_contents($this->_config['privateKey']);
        $this->_privateKey = openssl_pkey_get_private($privateKey);
        return true;
    }

    /**
     * 获取公钥
     * @return bool
     */
    public function getPublicKey()
    {
        if (is_resource($this->_publicKey)) {
            return true;
        }
        $publicKey = file_get_contents($this->_config['publicKey']);
        $this->_publicKey = openssl_pkey_get_public($publicKey);
        return true;
    }

    public function __destruct()
    {
        @openssl_free_key($this->_privateKey);
        @openssl_free_key($this->_publicKey);
    }
}
