<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CustomEncryption{
    protected $key;
    public function __construct()
    {
      $this->key = 'cebudoctorsuniversityhospital10';
    }

    public function encrypt($data){
      // Generate IV
      $iv = random_bytes(openssl_cipher_iv_length('AES-256-CBC'));
      // Encrypt the data
      $encryptedData = openssl_encrypt($data, 'AES-256-CBC', $this->key, 0, $iv);
      // Return base64-encoded IV and encrypted data
      return base64_encode($iv . $encryptedData);
    }


    public function decrypt($encryptedData){
          // Decode the base64-encoded data
          $decodedData = base64_decode($encryptedData);

          // Check if the decoded data is valid
          if ($decodedData === false) {
              throw new Exception('Base64 decoding failed.');
          }
  
          // Extract the IV from the decoded data
          $ivLength = openssl_cipher_iv_length('AES-256-CBC');
          $iv = substr($decodedData, 0, $ivLength);
  
          // Extract the encrypted data
          $encryptedData = substr($decodedData, $ivLength);
  
          // Check if we got the correct lengths
          if (strlen($iv) !== $ivLength || strlen($encryptedData) === 0) {
              throw new Exception('Decryption failed. The payload may be invalid.');
          }
  
          // Decrypt the data using the same key and IV
          $decryptedData = openssl_decrypt($encryptedData, 'AES-256-CBC', $this->key, 0, $iv);
  
          if ($decryptedData === false) {
              throw new Exception('Decryption failed. The payload may be invalid.');
          }
  
          return $decryptedData;
    }
}