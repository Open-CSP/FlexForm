<?php
/**
 * Created by  : OpenCSP
 * Project     : MWFlexForm
 * Filename    : Mail.php
 * Description :
 * Date        : 28-1-2022
 * Time        : 20:34
 */

namespace FlexForm\Processors\Content;

use MailAddress;
use MediaWiki\MediaWikiServices;
use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\FlexFormException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Title;

/**
 * Class for mailings
 */
class Mail {

	/**
	 * @var array
	 */
	/*
	 * 		'to'         => General::getPostString( 'mwmailto' ),
			'content'    => General::getPostString( 'mwmailcontent' ),
			'header'     => General::getPostString( 'mwmailheader' ),
			'footer'     => General::getPostString( 'mwmailfooter' ),
			'mtemplate'  => General::getPostString( 'mwmailtemplate' ),
			'mjob'       => General::getPostString( 'mwmailjob' ),
			'html'       => General::getPostString( 'mwmailhtml' ),
			'attachment' => General::getPostString( 'mwmailattachment' )
	 */
	private $fields = [];

	/**
	 * @var false|string
	 */
	private $template = false;

	/**
	 * @var bool
	 */
	private $isBot = false;

	/**
	 * @return false|mixed|string
	 */
	public function getTemplate() {
		return $this->template;
	}

	/**
	 * @param string|bool $template
	 */
	public function __construct( $template = false ) {
		$this->fields = Definitions::mailFields();
		$this->template = $this->fields['mtemplate'];
		if ( $template !== false ) {
			$this->isBot = true;
			$this->template = $template;
		}
	}

	/**
	 * @param string $content
	 *
	 * @return mixed
	 * @throws FlexFormException
	 * @throws \MWException
	 */
	public function parseWikiText( string $content ) : string {
		$render   = new Render();
		$postdata = [
			"action"             => "parse",
			"format"             => "json",
			"text"               => $content,
			"contentmodel"       => "wikitext",
			"disablelimitreport" => "1",
			"disablestylededuplication" => "1",
			"disabletoc" => "1",
			"disableeditsection" => "1",
			"wrapoutputclass" => '',



		];
		$result   = $render->makeRequest( $postdata );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'textparse result ',
				$result
			);
		}
		if ( isset( $result['error'] ) ) {
			throw new FlexFormException(
				$result['error']['info'],
				0
			);
		}
		return $result['parse']['text'];
	}

	/**
	 * @param string $title
	 *
	 * @return string
	 * @throws FlexFormException
	 * @throws \MWException
	 */
	private function parseWikiPageByTitle( string $title ) : string {
		$debugTitle = '<b>::' . get_class() . '::</b> ';
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				$debugTitle . 'ParseWikiPage ',
				$title
			);
		}
		$render   = new Render();
		$postdata = [
			"action"                    => "parse",
			"format"                    => "json",
			"page"                      => $title,
			"disablelimitreport"        => "1",
			"wrapoutputclass"           => '',
			"disablestylededuplication" => true,
			"disabletoc"                => true,

		];
		$result   = $render->makeRequest( $postdata );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				$debugTitle . 'Parse result ',
				$result
			);
		}
		if ( isset( $result['error'] ) ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					$debugTitle . 'ParseWikitextErrorException ',
					$result
				);
			}
			throw new FlexFormException(
				"Error getting mail template($title):" . $result['error']['info'],
				0
			);
		}

		return $result['parse']['text'];
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	private function placeValuesInTemplate( string $content ) : string {
		// Get all form elements and replace in Template
		foreach ( $_POST as $k => $v ) {
			if ( ! Definitions::isFlexFormSystemField( $k ) ) {
				if ( is_array( $v ) ) {
					$tmpArray = wsSecurity::cleanBraces(
						implode(
							", ",
							$v
						)
					);
					$content  = str_replace(
						'$' . $k,
						$tmpArray,
						$content
					);
				} else {
					$content = str_replace(
						'$' . $k,
						wsSecurity::cleanBraces( $v ),
						$content
					);
				}
			}
		}

		return preg_replace(
			'/\$([\S]+)/',
			'',
			$content
		);
	}

	/**
	 * @param string $template
	 *
	 * @return string
	 */
	private function getTemplateValueAndDelete( string $template ) : string {
		// echo "searching for $name";
		$fieldToGetAndReplace = array_keys( $this->fields );
		foreach ( $fieldToGetAndReplace as $field ) {
			//echo "<p>$field</p>";
			$regex = '#%_' . $field . '=(.*?)%#';
			preg_match(
				$regex,
				$template,
				$regexResult
			);
			//echo "<pre>";
			//var_dump($regexResult);
			//echo "</pre>";
			if ( isset( $regexResult[1] ) ) {
				$tmp = $regexResult[1];
			} else {
				$tmp                  = "";
				$this->fields[$field] = false;
			}
			// $tmp = $this->get_string_between( $template, '%ws_' . $name . '=' , '%' );
			// echo "<p>found : $tmp</p>";
			if ( $tmp !== "" ) {
				$this->fields[$field] = $tmp;
				$template             = str_replace(
					'%_' . $field . '=' . $tmp . '%',
					'',
					$template
				);
			} else {
				if( isset( $regexResult[1] ) ) {
					$template               = str_replace(
						'%_' . $field . '=' . $tmp . '%',
						'',
						$template
					);

				}
				$this->fields[ $field ] = false;
			}
		}

		return trim( $template );
	}

	/**
	 * @return void
	 * @throws FlexFormException
	 * @throws \MWException
	 */
	public function handleTemplate( $additonalFields = [] ) {
		/*
		 *  'to'         => General::getPostString( 'mwmailto' ),
			'content'    => General::getPostString( 'mwmailcontent' ),
			'header'     => General::getPostString( 'mwmailheader' ),
			'footer'     => General::getPostString( 'mwmailfooter' ),
			'mtemplate'  => General::getPostString( 'mwmailtemplate' ),
			'mjob'       => General::getPostString( 'mwmailjob' ),
			'html'       => General::getPostString( 'mwmailhtml' ),
			'attachment' => General::getPostString( 'mwmailattachment' )
		 */
		if( ! $this->isBot ) {
			$fields = ContentCore::getFields();
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Mail start fields',
					$this->fields
				);
			}
		} else {
			$fields['parseLast'] = false;
		}

		if ( $fields['parseLast'] === false ) {
			$tpl = $this->parseWikiPageByTitle( $this->getTemplate() );
		} else {
			$render = new Render();
			$tpl    = $render->getSlotContent( $this->getTemplate() );
			$tpl    =  $tpl['content'];
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Mail start template start',
				$tpl
			);
		}

		$tpl = $this->placeValuesInTemplate( $tpl );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Mail start template values places',
				$tpl
			);
		}
		if ( $fields['parseLast'] !== false ) {
			$tpl = $this->parseWikiText( $tpl );
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Mail start template values places 2',
				$tpl
			);
		}

		$to      = false;
		$header  = false;
		$footer  = false;
		$content = false;
		if ( $this->fields['to'] !== false ) {
			$to = $this->fields['to'];
		}
		if ( $this->fields['header'] !== false ) {
			$header = $this->fields['header'];
		}
		if ( $this->fields['footer'] !== false ) {
			$footer = $this->fields['footer'];
		}
		if ( $this->fields['content'] !== false ) {
			$content = $this->fields['content'];
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Mail start template values places 3',
				$tpl
			);
		}

		$tpl = $this->getTemplateValueAndDelete( $tpl );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Mail start template values places 4',
				$tpl
			);
		}
		if( $this->isBot ) {
			if ( ! empty( $additonalFields ) ) {
				foreach ( $additonalFields as $key => $value ) {
					$this->fields[ $key ] = $value;
				}
			}
		}

		// BEGIN Always overrule form fields over template values
		if ( $content !== false ) {
			$this->fields['content'] = '<div class="wsform-mail-content">' . base64_decode( $content ) . '</div>';
		} else {
			$this->fields['content'] = $tpl;
		}
		if ( $footer !== false ) {
			$this->fields['footer'] = $footer;
		}
		if ( $header !== false ) {
			$this->fields['header'] = $header;
		}
		if ( $to !== false ) {
			if ( strpos(
				$to,
				'user:'
			) ) {
				$to = str_replace(
					'user:',
					'',
					$to
				);
			}
			$this->fields['to'] = $to;
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Mail start fields completed 1',
				$this->fields
			);
		}
		// END Always overrule form fields over template values

		$this->createEmailBody();
		if ( $this->fields['html'] === false || $this->fields['html'] === 'yes' ) {
			$this->fields['html'] = true;
		} else {
			$this->fields['html'] = false;
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Mail start template completed 2',
				$this->fields
			);
		}

		$this->checkFieldsNeeded();
		$this->sendMail();
	}

	public function sendMailTo( string $to, string $name, string $subject, string $body ): bool {
		global $wgPasswordSender;
		$from = $wgPasswordSender;
		$mail = new PHPMailer( true );

		try {
			if ( Config::getConfigVariable( 'use_smtp' ) === true ) {
				$mail->isSMTP();
				$mail->Host       = Config::getConfigVariable( 'smtp_host' );
				$mail->SMTPAuth   = Config::getConfigVariable( 'smtp_authentication' );
				$mail->Username   = Config::getConfigVariable( 'smtp_username' );
				$mail->Password   = Config::getConfigVariable( 'smtp_password' );
				$mail->SMTPSecure = Config::getConfigVariable( 'smtp_secure' );
				$mail->Port       = Config::getConfigVariable( 'smtp_port' );
			} else {
				$mail->isMail();
			}
			$mail->CharSet = 'UTF-8';

			$mail->setFrom(	$from, wfMessage( 'emailsender' )->inContentLanguage()->text() );

			$mail->addAddress( $to, $name );


			$mail->isHTML( true );
			$mail->Subject = $subject;
			$mail->Body    = $body;
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Debug on, not sending mail',
					$this->fields
				);
			} else {
				$mail->send();
			}
		} catch ( Exception $e ) {
			throw new FlexFormException(
				$e->getMessage(),
				0
			);
		}
		return true;
	}

	/**
	 * @return void
	 * @throws FlexFormException|Exception
	 */
	private function sendMail() {
		global $wgSMTP;
		//var_dump( $wgSMTP );
		//die();
		$mail                 = new PHPMailer( true );
		$this->fields['to']   = $this->createEmailArray(
			$this->fields['to'],
			$mail
		);
		$this->fields['from'] = $this->createEmailArray(
			$this->fields['from'],
			$mail
		);
		if ( $this->fields['reply-to'] ) {
			$this->fields['reply-to'] = $this->createEmailArray(
				$this->fields['reply-to'],
				$mail
			);
		}
		if ( $this->fields['cc'] ) {
			$this->fields['cc'] = $this->createEmailArray(
				$this->fields['cc'],
				$mail
			);
		}
		if ( $this->fields['bcc'] ) {
			$this->fields['bcc'] = $this->createEmailArray(
				$this->fields['bcc'],
				$mail
			);
		}
		try {
			if ( Config::getConfigVariable( 'use_smtp' ) === true ) {
				$mail->isSMTP();
				$mail->Host       = Config::getConfigVariable( 'smtp_host' );
				$mail->SMTPAuth   = Config::getConfigVariable( 'smtp_authentication' );
				$mail->Username   = Config::getConfigVariable( 'smtp_username' );
				$mail->Password   = Config::getConfigVariable( 'smtp_password' );
				$mail->SMTPSecure = Config::getConfigVariable( 'smtp_secure' );
				$mail->Port       = Config::getConfigVariable( 'smtp_port' );
			} else {
				$mail->isMail();
			}
			$mail->CharSet = 'UTF-8';
			foreach ( $this->fields['from'] as $single ) {
				$mail->setFrom(
					$single['address'],
					$single['name']
				);
			}
			foreach ( $this->fields['to'] as $single ) {
				$mail->addAddress(
					$single['address'],
					$single['name']
				);
			}
			if ( $this->fields['cc'] !== false ) {
				foreach ( $this->fields['cc'] as $single ) {
					$mail->addCC(
						$single['address'],
						$single['name']
					);
				}
			}
			if ( $this->fields['bcc'] !== false ) {
				foreach ( $this->fields['bcc'] as $single ) {
					$mail->addBCC(
						$single['address'],
						$single['name']
					);
				}
			}
			if ( $this->fields['reply-to'] !== false ) {
				foreach ( $this->fields['reply-to'] as $single ) {
					$mail->addReplyTo(
						$single['address'],
						$single['name']
					);
				}
			}
			$mail = $this->checkForAttachment( $mail );
			$mail->isHTML( $this->fields['html'] );
			$mail->Subject = $this->fields['subject'];
			$mail->Body    = $this->fields['content'];
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Debug on, not sending mail',
					$this->fields
				);
			} else {
				$mail->send();
			}
		} catch ( Exception $e ) {
			throw new FlexFormException(
				$e->getMessage(),
				0
			);
		}
	}

	/**
	 * @param PHPMailer $mail
	 *
	 * @return PHPMailer
	 * @throws Exception
	 */
	private function checkForAttachment( PHPMailer $mail ) : PHPMailer {

		$protocol = stripos(
						$_SERVER['SERVER_PROTOCOL'],
						'https'
					) === 0 ? 'https:' : 'http:';
		if ( $this->fields['attachment'] !== false ) {
			if ( substr( strtolower( $this->fields['attachment'] ), 0, 5 ) === 'file:' ) {
				// We have a wiki file
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'Looking for wiki upload file : ' . substr( $this->fields['attachment'], 5 ),
						''
					);
				}
				//die ( substr($this->fields['attachment'], 5 ) );
				$fileRepo = MediaWikiServices::getInstance()->getRepoGroup();
				$fTitle = Title::newFromText( substr( $this->fields['attachment'], 5 ) );
				global $wgUser;
				$user = $wgUser;
				if ( !MediaWikiServices::getInstance()->getPermissionManager()->userCan( "read", $user, $fTitle ) ) {
					if ( Config::isDebug() ) {
						Debug::addToDebug(
							'User is not allowed to read this file : ' . substr( $this->fields['attachment'], 5 ),
							''
						);
					}
					return $mail;
				}
				$searchedFile = $fileRepo->findFile( substr( $this->fields['attachment'], 5 ) );
				if ( $searchedFile === false ) {
					if ( Config::isDebug() ) {
						Debug::addToDebug(
							"File does not exists",
							substr( $this->fields['attachment'], 5 )
						);
					}
					return $mail;
				}
				$canonicalURL = $searchedFile->getLocalRefPath();
				if ( $canonicalURL === false ) {
					$canonicalURL = $searchedFile->getCanonicalUrl();
				}
				$fileAttachedContent = file_get_contents( $canonicalURL );
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						"File info : " . substr( $this->fields['attachment'], 4 ),
						[ "exists" => $searchedFile->exists(), "canon url" => $canonicalURL ]
					);
				}

			} else {
				if ( strpos(
						 $this->fields['attachment'],
						 'http'
					 ) === false ) {
					$fileAttachedContent = file_get_contents( $protocol . $this->fields['attachment'] );
				} else {
					$fileAttachedContent = file_get_contents( $this->fields['attachment'] );
				}
			}
		} else {
			$fileAttachedContent = false;
		}
		if ( $fileAttachedContent !== false ) {
			$pInfo            = pathinfo( $this->fields['attachment'] );
			$fileAttachedName = $pInfo['basename'];
			$mail->addStringAttachment(
				$fileAttachedContent,
				$fileAttachedName
			);
		}

		return $mail;
	}

	/**
	 * @param string $email
	 * @param PHPMailer $mail
	 *
	 * @return array
	 */
	private function createEmailArray( string $email, PHPMailer $mail ) : array {
		$tmp = str_replace(
			[
				'[',
				']'
			],
			[
				'<',
				'>'
			],
			$email
		);

		return $mail->parseAddresses( $tmp );
	}

	/**
	 * @throws FlexFormException
	 */
	private function checkFieldsNeeded() {
		if ( $this->fields['to'] === false ) {
			throw new FlexFormException(
				wfMessage( 'flexform-mail-no-to' )->text(),
				0
			);
		}
		if ( $this->fields['from'] === false ) {
			throw new FlexFormException(
				wfMessage( 'flexform-mail-no-from' )->text(),
				0
			);
		}
		if ( $this->fields['subject'] === false ) {
			throw new FlexFormException(
				wfMessage( 'flexform-mail-no-subject' )->text(),
				0
			);
		}
	}

	/**
	 * @return void
	 */
	private function createEmailBody() {
		if ( $this->fields['header'] !== false ) {
			try {
				$headerContent = $this->parseWikiPageByTitle( $this->fields['header'] );
			} catch ( FlexFormException|\MWException $e ) {
				$headerContent = '';
			}
		} else {
			$headerContent = '';
		}
		if ( $this->fields['footer'] !== false ) {
			try {
				$footerContent = $this->parseWikiPageByTitle( $this->fields['footer'] );
			} catch ( FlexFormException|\MWException $e ) {
				$footerContent = '';
			}
		} else {
			$footerContent = '';
		}
		$this->fields['content'] = $headerContent . $this->fields['content'] . $footerContent;
	}

}
