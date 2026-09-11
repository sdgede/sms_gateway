<?php

namespace App\Libraries;

class Encryption_lib
{
    private $options = 0;
    private $method;

    private $iv;
    private $key;

    private $iv_core;
    private $key_core;

    public function __construct(
        string $data = null,
        string $key = null,
        string $size = '128',
        string $mode = 'CBC',
        ?string $encryption_type = 'mobile'
    ) {
        $this->iv = env('config.api.encryptionIV', 'ec0o55S2Z6fdLwJJ');
        $this->key = env('config.api.encryptionKey', '9IBbTNfR6Y6815AK');
        $this->iv_core = env('config.api.encryptionIVCore', 'ec0o55S2Z6fdLwJJ');
        $this->key_core = env('config.api.encryptionKeyCore', '9IBbTNfR6Y6815AK');
        $this->setMethod($size, $mode);
    }

    private function setMethod($size, $mode)
    {
        if ($size == 192 and in_array('', array('CBC-HMAC-SHA1', 'CBC-HMAC-SHA256', 'XTS'))) {
            $this->method = null;
        } else {
            $this->method = 'AES-' . $size . '-' . $mode;
        }
    }

    private function validateParam($data)
    {
        if (($data != null or empty(trim($data))) and $this->method != null) return true;
        return false;
    }

    public function encrypt($str)
    {
        if ($this->validateParam($str) === false) return '';

        return trim(openssl_encrypt($str, $this->method, $this->key, $this->options, $this->iv));
    }

    public function decrypt($str)
    {
        if (empty($str)) {
            return '';
        }
        if ($this->validateParam($str) === false) return '';

        return trim(openssl_decrypt($str, $this->method, $this->key, $this->options, $this->iv));
    }

    public function getHash($str)
    {
        if (empty($str)) return false;

        return password_hash($str, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    public function encrypt_core($str)
    {
        if ($this->validateParam($str) == true) {
            return trim(openssl_encrypt($str, $this->method, $this->key_core, $this->options, $this->iv_core));
        }
        return false;
    }

    public function decrypt_core($str)
    {
        if ($this->validateParam($str) == true) {
            return trim(openssl_decrypt($str, $this->method, $this->key_core, $this->options, $this->iv_core));
        }
        return false;
    }
}
