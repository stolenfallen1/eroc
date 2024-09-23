<?php
class OpenSSLHelper {

    private $cipher = 'aes-256-cbc'; // AES encryption method
    private $key;                     // Encryption key
    private $ivLength;                // IV length based on cipher

    public function __construct($key) {
        $this->key = hash('sha256', $key, true);  // Hash the key to 256 bits
        $this->ivLength = openssl_cipher_iv_length($this->cipher); // Get the IV length for the selected cipher
    }

    /**
     * Encrypt the given plaintext.
     *
     * @param string $plaintext The data to encrypt.
     * @return string The encrypted data in base64 format.
     */
    public function encrypt($plaintext) {
        $iv = openssl_random_pseudo_bytes($this->ivLength);  // Generate a secure random IV

        // Encrypt the data
        $encrypted = openssl_encrypt($plaintext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        // Combine IV and encrypted data, then base64 encode them
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt the given ciphertext.
     *
     * @param string $ciphertext The encrypted data in base64 format.
     * @return string The decrypted plaintext.
     */
    public function decrypt($ciphertext) {
        $decoded = base64_decode($ciphertext);               // Decode the base64 ciphertext

        // Extract the IV and encrypted data from the decoded string
        $iv = substr($decoded, 0, $this->ivLength);          // Extract the IV
        $encryptedData = substr($decoded, $this->ivLength);  // Extract the encrypted data

        // Decrypt the data
        $decrypted = openssl_decrypt($encryptedData, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        return $decrypted;
    }
}
