<?php
class Endecryptor {
    private $_secret;
    private $_method;
    private $_temp_iv;
    public $temp_hmac;
    public $temp_encrypted;
    public $temp_decrypted;
    public $valid_TS_interval;

    /**
     * Sets object properties that will be used for decryption/encryption.
     * @param string $encryption_secret String made of 32 characters that will be used for decryption/encryption.
     * @param string $encryption_method Method for decryption/encryption.
     * @param int    $valid_TS_interval Time in seconds to check if message is too old.
     * @return void
     */
    public function __construct($encryption_secret, $encryption_method, $valid_TS_interval) {
        if (!is_string($encryption_secret) || strlen($encryption_secret) != 32) {
            throw  new Exception( 'Secret key must be string of 32 characters!\n - Provided secret key does not meet criteria.' );
        } else if (!is_string($encryption_method)) {
            throw  new Exception( 'Encryption method must be string!\n - Provided method does not meet criteria.' );
        } else {
            $this->_secret = $encryption_secret;
            $this->_method = $encryption_method;
            $this->_temp_iv = substr(bin2hex(openssl_random_pseudo_bytes(16)), 0, 16);
        }
    }

    /**
     * Encrypts message and sets temp_encrypted, temp_hmac properties and new random temp_iv property for next encryption-
     * @param string $message Plain text.
     * @return void
     */
    public function encrypt($message) {
        $this->temp_encrypted = base64_encode($this->_temp_iv) . openssl_encrypt($message, $this->_method, $this->_secret, 0, $this->_temp_iv);
        $this->temp_hmac = hash_hmac('md5', $this->temp_encrypted, $this->_secret);
        $this->_temp_iv = substr(bin2hex(openssl_random_pseudo_bytes(16)), 0, 16);
    }

    /**
     * Encrypts message text with current time added at the beginning.
     * @param string $message Message content that will be encrypted.
     * @return void
     */
    public function encryptWithTS($message) {
        $message = substr(gmdate('c'), 0, 19) . $message;
        $this->encrypt($message);
        $this->temp_hmac = hash_hmac('md5', $this->temp_encrypted, $this->_secret);
    }

    /**
     * Decrypts encrypted message and checks if authorisation signature is valid.
     * @param string $message Encrypted message text.
     * @param string $hmac Used for signature check.
     * @return boolean True if successful, false if not.
     */
    public function decrypt($message, $hmac) {
        if (hash_hmac('md5', $message, $this->_secret) == $hmac) {
            $iv = base64_decode(substr($message, 0, 24));
            $this->temp_decrypted = openssl_decrypt(substr($message, 24), $this->_method, $this->_secret, 0, $iv);
            return true;
        }
        #Bad signature.
        return false;
    }

    /**
     * Decrypts encrypted message and checks signature & time when it was sent.
     * @param string $message Encrypted message text.
     * @param string $hmac Used for signature check.
     * @return boolean True if successful, false if not.
     */
    public function decryptAndValidateTS($message, $hmac) {
        if ($this->decrypt($message, $hmac)) {
            $currentTS = time();
            $messageTS = new DateTime(substr($this->temp_decrypted, 0, 19));
            if (($currentTS - $messageTS->format('U')) <= $this->valid_TS_interval) {
                $this->temp_decrypted = substr($this->temp_decrypted, 19);
                return true;
            }
            #Old timestamp.
        }
        return false;
    }
}