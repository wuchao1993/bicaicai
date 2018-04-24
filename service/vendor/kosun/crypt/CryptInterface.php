<?php
/**
 * 加解密接口类
 * @author Ryan
 * @since  2018-01-09
 */

namespace crypt;

interface CryptInterface
{
    /**
     * 加密
     * @param  string $input 明文
     * @return mixed
     */
    public function encrypt($input);

    /**
     * 解密
     * @param  string $encrypted 密文
     * @return mixed
     */
    public function decrypt($encrypted);
}