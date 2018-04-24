<?php
/**
 * Aes加解密类
 * @author Ryan
 * @since  2018-01-09
 */

namespace crypt;

class CryptAes extends CryptAbstract
{
    /**
     * 算法模式
     */
    const CIPHER = 'AES-128-CBC';

    public static $cryptName = 'Aes';

    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->_config = $config;
        } else {
            parent::__construct();
        }
    }

    /**
     * AES加密
     * @param  string $input 明文
     * @return string
     */
    public function encrypt($input)
    {
        return base64_encode(openssl_encrypt(
            $input,
            self::CIPHER,
            $this->_config['key'],
            OPENSSL_RAW_DATA,
            $this->_config['iv']
        ));
    }

    /**
     * AES解密
     * @param  string $encrypted 密文
     * @return string
     */
    public function decrypt($encrypted)
    {
        return openssl_decrypt(
            base64_decode($encrypted),
            self::CIPHER,
            $this->_config['key'],
            OPENSSL_RAW_DATA,
            $this->_config['iv']
        );
    }
}
