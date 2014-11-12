<?php

require_once(dirname(__FILE__).'/../libs/phpseclib/crypt/base.php');
require_once(dirname(__FILE__).'/../libs/phpseclib/crypt/rijndael.php');
require_once(dirname(__FILE__).'/../libs/phpseclib/crypt/aes.php');
require_once(dirname(__FILE__).'/nostotagging-security.php');

/**
 * Helper class for encrypting/decrypting strings.
 */
class NostoTaggingCipher
{
	/**
	 * @var CryptBase
	 */
	private $crypt;

	/**
	 * Constructor.
	 *
	 * @param string $secret the secret key to encrypt with.
	 */
	public function __construct($secret)
	{
		$this->crypt = new CryptAES(CRYPT_AES_MODE_CBC);
		$this->crypt->setKey($secret);
		// AES has a fixed block size of 128 bytes
		$this->crypt->setIV(NostoTaggingSecurity::rand(16));
	}

	/**
	 * Encrypts the string an returns iv.encrypted.
	 *
	 * @param string $plain_text the string to encrypt.
	 * @return string the encrypted string.
	 */
	public function encrypt($plain_text)
	{
		$iv = $this->crypt->getIV();
		$cipher_text = $this->crypt->encrypt($plain_text);
		return $iv.$cipher_text;
	}

	/**
	 * Decrypts the string and returns the plain text.
	 *
	 * @param string $cipher_text the encrypted cipher.
	 * @return string the decrypted plain text string.
	 */
	public function decrypt($cipher_text)
	{
		// Assume the first 16 chars is the IV.
		$iv = substr($cipher_text, 0, 16);
		$this->crypt->setIV($iv);
		$plain_text = $this->crypt->decrypt(substr($cipher_text, 16));
		return $plain_text;
	}
}