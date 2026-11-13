<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : protect.php
 * Description :
 * Date        : 27-12-2021
 * Time        : 13:01
 */

namespace FlexForm\Core;

use FlexForm\FlexFormException;
use HTMLPurifier;
use HTMLPurifier_Config;
use MediaWiki\MediaWikiServices;

class Protect {

	protected static string $method = 'aes-256-gcm'; // default cipher method if none supplied
	private static string $key = '';

	protected static function iv_bytes(): int {
		return openssl_cipher_iv_length( self::$method );
	}

	/**
	 * @param string|bool $method
	 * @throws FlexFormException
	 */
	public static function setCrypt( $method = false ): void {
		$key = Config::getConfigVariable( 'sec_key' ) ?? false;

		if ( empty( $key ) ) {
			$mwSecret = MediaWikiServices::getInstance()->getMainConfig()->get( 'SecretKey' );
			if ( !empty( $mwSecret ) ) {
				$key = $mwSecret;
			} else {
				throw new FlexFormException(
					__METHOD__ . ': No encryption key available (configure sec_key or $wgSecretKey).',
					1
				);
			}
		}

		// Altijd hashen naar een vaste 32-byte (256-bit) binary key
		self::$key = openssl_digest( (string)$key, 'SHA256', true );

		if ( $method ) {
			$methodLower = strtolower( $method );
			if ( in_array( $methodLower, openssl_get_cipher_methods(), true ) ) {
				self::$method = $methodLower;
			} else {
				throw new FlexFormException(
					__METHOD__ . ": unrecognised cipher method: {$method}",
					1
				);
			}
		}
	}

	/**
	 * @param string $data
	 * @return string
	 * @throws FlexFormException
	 */
	public static function encrypt( string $data ): string {
		if ( self::$key === '' ) {
			self::setCrypt();
		}

		$iv = random_bytes( self::iv_bytes() );
		$tag = '';

		if ( str_ends_with( self::$method, '-gcm' ) ) {
			$encrypted = openssl_encrypt(
				$data,
				self::$method,
				self::$key,
				OPENSSL_RAW_DATA,
				$iv,
				$tag,
				'',
				16
			);
			return bin2hex( $iv ) . bin2hex( $tag ) . base64_encode( $encrypted );
		}

		// Fallback voor niet-GCM ciphers met HMAC-SHA256
		$encrypted = openssl_encrypt(
			$data,
			self::$method,
			self::$key,
			0,
			$iv
		);
		$payload = bin2hex( $iv ) . $encrypted;
		$hmac = hash_hmac( 'sha256', $payload, self::$key );

		return $hmac . $payload;
	}

	/**
	 * @param mixed $data
	 * @return string|false
	 * @throws FlexFormException
	 */
	public static function decrypt( $data ) {
		if ( !is_string( $data ) || $data === '' ) {
			return false;
		}
		if ( self::$key === '' ) {
			self::setCrypt();
		}

		$iv_strlen = 2 * self::iv_bytes();

		if ( str_ends_with( self::$method, '-gcm' ) ) {
			$tag_strlen = 32; // 16 bytes in hex = 32 chars
			if ( strlen( $data ) <= ( $iv_strlen + $tag_strlen ) ) {
				return false;
			}

			$iv_hex = substr( $data, 0, $iv_strlen );
			$tag_hex = substr( $data, $iv_strlen, $tag_strlen );
			$crypted_string = substr( $data, $iv_strlen + $tag_strlen );

			if ( !ctype_xdigit( $iv_hex ) || !ctype_xdigit( $tag_hex ) ) {
				return false;
			}

			$raw_cipher = base64_decode( $crypted_string, true );
			if ( $raw_cipher === false ) {
				return false;
			}

			return openssl_decrypt(
				$raw_cipher,
				self::$method,
				self::$key,
				OPENSSL_RAW_DATA,
				hex2bin( $iv_hex ),
				hex2bin( $tag_hex )
			);
		}

		// Fallback voor niet-GCM ciphers met HMAC verificatie
		$hmac_strlen = 64;
		if ( strlen( $data ) <= ( $hmac_strlen + $iv_strlen ) ) {
			return false;
		}

		$hmac = substr( $data, 0, $hmac_strlen );
		$payload = substr( $data, $hmac_strlen );
		$expected_hmac = hash_hmac( 'sha256', $payload, self::$key );

		if ( !hash_equals( $expected_hmac, $hmac ) ) {
			return false;
		}

		$iv_hex = substr( $payload, 0, $iv_strlen );
		$crypted_string = substr( $payload, $iv_strlen );

		if ( ctype_xdigit( $iv_hex ) && strlen( $iv_hex ) % 2 === 0 ) {
			return openssl_decrypt(
				$crypted_string,
				self::$method,
				self::$key,
				0,
				hex2bin( $iv_hex )
			);
		}

		return false;
	}

	/**
	 * @param string $value String to purify html
	 * @param array|string $clean type
	 * @param bool $secure
	 *
	 * @return string purified html
	 */
	public static function purify( string $value, $clean = "default", $secure = false ) {
		if ( is_array( $clean ) ) {
			$custom = $clean[1];
			$clean  = $clean[0];
		} else {
			$custom = false;
		}
		if ( $secure === false ) {
			return $value;
		}
		if ( $clean === '' ) {
			$clean = 'default';
		}
		if ( $clean === "all" ) {
			return $value;
		}
		if ( $secure ) {
			$config = HTMLPurifier_Config::createDefault();
			switch ( $clean ) {
				case "nohtml":
					$config->set(
						'HTML.Allowed',
						''
					);
					break;
				case "custom":
					if ( $custom !== false ) {
						$config->set(
							'HTML.Allowed',
							$custom
						); // e.g. 'p,ul[style],ol,li'
					}
					break;
				case "default":
					$config->set(
						'HTML.Allowed',
						'nowiki'
					);
					break;
				default:
					break;
			}
			$def = $config->getHTMLDefinition( true );
			$def->addElement(
				'nowiki',
				'Inline',
				'Inline',
				'Common'
			);

			$purifier = new HTMLPurifier( $config );

			return $purifier->purify( $value );
		} else {
			return $value;
		}
	}

}
