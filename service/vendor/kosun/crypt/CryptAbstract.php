<?php
/**
 * 加解密抽象类
 * @author Ryan
 * @since  2018-01-09
 */

namespace crypt;

abstract class CryptAbstract implements CryptInterface
{
    public static $cryptName;
    protected $_config;

    public function __construct()
    {
        $config = (array)\think\Config::get('crypts');
        $this->_config = isset($config[static::$cryptName])
            ? $config[static::$cryptName]
            : [];
    }

    /**
     * 加密
     * @param  string $input 明文
     * @return mixed
     */
    abstract public function encrypt($input);

    /**
     * 解密
     * @param  string $encrypted 密文
     * @return mixed
     */
    abstract public function decrypt($encrypted);
}
