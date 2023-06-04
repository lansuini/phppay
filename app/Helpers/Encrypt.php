<?php
namespace App\Helpers;

/**
 * vegas2.0
 * 对称加密算法类
 */
class Encrypt
{
    const ENCRYPT = 1;

    const DECRYPT = 2;

    protected $key;
    
    protected $iv;

    /**
     * @var string AES-128-CBC AES-128-CFB AES-256-CBC AES-256-CFB ..
     */
    protected $cipher = 'AES-256-CBC';

    /**
     * Encrypt constructor.
     * 可以通过 openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher)) 生成key与iv
     * 位数应该与$cipher对应
     *
     * @param string $key
     * @param string $cipher
     */
    public function __construct(string $key, $cipher = 'AES-256-CBC')
    {
        $this->key = $key;
        $ciphers = openssl_get_cipher_methods();
        $iv = openssl_cipher_iv_length($cipher);

        if (in_array($cipher, $ciphers) && $iv > 0) {
            $this->cipher = $cipher;
        } else {
            $this->cipher = 'AES-256-CBC';
        }
        $this->iv = strrev($key);
//        echo $key.PHP_EOL;
//        echo $this->iv;
//        exit;
    }

    public function length()
    {
        return openssl_cipher_iv_length($this->cipher);
    }

    public function random()
    {
        $bytes = openssl_random_pseudo_bytes($this->length());

        return bin2hex($bytes);
    }

    public function encrypt($plain)
    {
        $encrypted = openssl_encrypt($plain, $this->cipher, $this->key, OPENSSL_RAW_DATA, $this->iv);
        if (false === $encrypted) {
            //print_var(openssl_error_string());

            return $plain;
        }

        return base64_encode($encrypted);
    }

    public function decrypt($encrypted)
    {
        $_encrypted = base64_decode($encrypted);
        if (!$_encrypted) {
            return $encrypted;
        }
        $decrypted = openssl_decrypt($_encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $this->iv);
        if (false === $decrypted) {
            //print_var(openssl_error_string());

            return $encrypted;
        }

        return $decrypted;
    }

}
