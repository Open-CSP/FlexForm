<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : Mail.php
 * Description :
 * Date        : 28-1-2022
 * Time        : 20:34
 */

namespace FlexForm\Processors\Content;

use Exception;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\WikiMap\WikiMap;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\FlexFormException;
use Title;

/**
 * Class for mailings
 */
class Mail {

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

	/**
	 * @var array
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
	 * @throws Exception
	 */
	public function parseWikiText( string $content ): string {
		$render = new Render();
		$postdata = [
			"action" => "parse",
			"format" => "json",
			"text" => $content,
			"contentmodel" => "wikitext",
			"disablelimitreport" => "1",
			"disablestylededuplication" => "1",
			"disabletoc" => "1",
			"disableeditsection" => "1",
			"wrapoutputclass" => '',
		];
		$result = $render->makeRequest( $postdata );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'textparse result ',
				$result
			);
		}
		if ( isset( $result['error'] ) ) {
			throw new FlexFormException(
				$result['error']['info'], 0
			);
		}

		return $result['parse']['text'];
	}

	/**
	 * @param string $title
	 *
	 * @return string
	 * @throws FlexFormException
	 * @throws Exception
	 */
	private function parseWikiPageByTitle( string $title ): string {
		$debugTitle = '<b>::' . __CLASS__ . '::</b> ';
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				$debugTitle . 'ParseWikiPage ',
				$title
			);
		}
		$render = new Render();
		$postdata = [
			"action" => "parse",
			"format" => "json",
			"page" => $title,
			"disablelimitreport" => "1",
			"wrapoutputclass" => '',
			"disablestylededuplication" => true,
			"disabletoc" => true,

		];
		$result = $render->makeRequest( $postdata );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Parse result ',
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
				"Error getting mail template($title):" . $result['error']['info'], 0
			);
		}

		return $result['parse']['text'];
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	private function placeValuesInTemplate( string $content ): string {
		// Get all form elements and replace in Template
		foreach ( $_POST as $k => $v ) {
			if ( !Definitions::isFlexFormSystemField( $k ) ) {
				if ( is_array( $v ) ) {
					$tmpArray = wsSecurity::cleanBraces(
						implode(
							", ",
							$v
						)
					);
					$content = str_replace(
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
	private function getTemplateValueAndDelete( string $template ): string {
		$fieldToGetAndReplace = array_keys( $this->fields );
		foreach ( $fieldToGetAndReplace as $field ) {
			$regex = '#%_' . $field . '=(.*?)%#';
			preg_match(
				$regex,
				$template,
				$regexResult
			);
			if ( isset( $regexResult[1] ) ) {
				$tmp = $regexResult[1];
			} else {
				$tmp = "";
				$this->fields[$field] = false;
			}
			if ( $tmp !== "" ) {
				$this->fields[$field] = $tmp;
				$template = str_replace(
					'%_' . $field . '=' . $tmp . '%',
					'',
					$template
				);
			} else {
				if ( isset( $regexResult[1] ) ) {
					$template = str_replace(
						'%_' . $field . '=' . $tmp . '%',
						'',
						$template
					);
				}
				$this->fields[$field] = false;
			}
		}

		return trim( $template );
	}

	/**
	 * @param array $additonalFields
	 *
	 * @return void
	 * @throws FlexFormException
	 * @throws Exception
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
		if ( !$this->isBot ) {
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
			$tpl = $render->getSlotContent( $this->getTemplate() );
			$tpl = $tpl['content'];
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

		$to = false;
		$header = false;
		$footer = false;
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
		if ( $this->isBot ) {
			if ( !empty( $additonalFields ) ) {
				foreach ( $additonalFields as $key => $value ) {
					$this->fields[$key] = $value;
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
			if (
				strpos(
					$to,
					'user:'
				)
			) {
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

	/**
	 * Create a value suitable for the MessageId Header
	 *
	 * @return string
	 */
	private function makeMsgId(): string {
		$services = MediaWikiServices::getInstance();

		$smtp = $services->getMainConfig()->get( 'SMTP' );
		$server = $services->getMainConfig()->get( 'Server' );
		$domainId = WikiMap::getCurrentWikiDbDomain()->getId();
		$msgid = uniqid( $domainId . ".", true /** for cygwin */ );

		if ( is_array( $smtp ) && isset( $smtp['IDHost'] ) && $smtp['IDHost'] ) {
			$domain = $smtp['IDHost'];
		} else {
			$domain = parse_url( $server, PHP_URL_HOST ) ?? '';
		}
		return "<$msgid@$domain>";
	}

	/**
	 * @param PHPMailer $mail
	 *
	 * @return PHPMailer
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	private function setCustomHeaders( PHPMailer $mail ): PHPMailer {
		$mail->addCustomHeader( 'Message-ID', self::makeMsgId() );
		$mail->addCustomHeader( 'X-Mailer', 'MediaWiki mailer' );
		return $mail;
	}

	/**
	 * @return PHPMailer
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	private function getPHPMailer(): PHPMailer {
		$mail = new PHPMailer( true );
		if ( Config::getConfigVariable( 'use_smtp' ) === true ) {
			$mail->isSMTP();
			$mail->Host = Config::getConfigVariable( 'smtp_host' );
			$mail->SMTPAuth = Config::getConfigVariable( 'smtp_authentication' );
			$mail->Username = Config::getConfigVariable( 'smtp_username' );
			$mail->Password = Config::getConfigVariable( 'smtp_password' );
			$mail->SMTPSecure = Config::getConfigVariable( 'smtp_secure' );
			$mail->Port = Config::getConfigVariable( 'smtp_port' );
			$mail = $this->setCustomHeaders( $mail );

		} elseif ( Config::getConfigVariable( 'use_mediawiki_mail_settings' ) === true ) {
			$config = MediaWikiServices::getInstance()->getMainConfig();
			$smtpSettings = $config->get( 'SMTP' );
			if ( is_array( $smtpSettings ) ) {
				$mail->isSMTP();
				$mail->Host = $smtpSettings['host'] ?? 'localhost';
				$mail->Port = $smtpSettings['port'] ?? 587;
				if ( !empty( $smtpSettings['auth'] ) ) {
					$mail->SMTPAuth = true;
					$mail->Username = $smtpSettings['username'] ?? '';
					$mail->Password = $smtpSettings['password'] ?? '';
				} else {
					$mail->SMTPAuth = false;
				}
				$mail->SMTPSecure = $smtpSettings['secure'] ?? 'tls';
				$mail = $this->setCustomHeaders( $mail );
			}
		} else {
			$mail->isMail();
		}
		return $mail;
	}

	/**
	 * @param string $to
	 * @param string $name
	 * @param string $subject
	 * @param string $body
	 *
	 * @return bool
	 * @throws FlexFormException
	 */
	public function sendMailTo( string $to, string $name, string $subject, string $body ): bool {
		global $wgPasswordSender;
		$from = $wgPasswordSender;
		try {
			$mail = $this->getPHPMailer();
			$mail->CharSet = 'UTF-8';

			$mail->setFrom( $from, wfMessage( 'emailsender' )->inContentLanguage()->text() );

			$mail->addAddress( $to, $name );

			$mail->isHTML( true );
			$mail->Subject = $subject;
			$mail->Body = $body;
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
				$e->getMessage(), 0
			);
		}

		return true;
	}

	/**
	 * @return void
	 * @throws FlexFormException|Exception
	 */
	private function sendMail() {
		$mail = $this->getPHPMailer();
		$this->fields['to'] = $this->createEmailArray(
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
			$mail->Body = $this->fields['content'];
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
				$e->getMessage(), 0
			);
		}
	}

	/**
	 * @param PHPMailer $mail
	 *
	 * @return PHPMailer
	 * @throws Exception
	 */
	private function checkForAttachment( PHPMailer $mail ): PHPMailer {
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
				$fileRepo = MediaWikiServices::getInstance()->getRepoGroup();
				$fTitle = Title::newFromText( substr( $this->fields['attachment'], 5 ) );
				$user = RequestContext::getMain()->getUser();
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
						[
							"exists" => $searchedFile->exists(),
							"canon url" => $canonicalURL
						]
					);
				}
			} else {
				if (
					strpos(
						$this->fields['attachment'],
						'http'
					) === false
				) {
					$fileAttachedContent = file_get_contents( $protocol . $this->fields['attachment'] );
				} else {
					$fileAttachedContent = file_get_contents( $this->fields['attachment'] );
				}
			}
		} else {
			$fileAttachedContent = false;
		}
		if ( $fileAttachedContent !== false ) {
			$pInfo = pathinfo( $this->fields['attachment'] );
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
	private function createEmailArray( string $email, PHPMailer $mail ): array {
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
	private function checkFieldsNeeded(): void {
		if ( $this->fields['to'] === false ) {
			throw new FlexFormException(
				wfMessage( 'flexform-mail-no-to' )->text(), 0
			);
		}
		if ( $this->fields['from'] === false ) {
			throw new FlexFormException(
				wfMessage( 'flexform-mail-no-from' )->text(), 0
			);
		}
		if ( $this->fields['subject'] === false ) {
			throw new FlexFormException(
				wfMessage( 'flexform-mail-no-subject' )->text(), 0
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
			} catch ( FlexFormException | Exception $e ) {
				$headerContent = '';
			}
		} else {
			$headerContent = '';
		}
		if ( $this->fields['footer'] !== false ) {
			try {
				$footerContent = $this->parseWikiPageByTitle( $this->fields['footer'] );
			} catch ( FlexFormException | Exception $e ) {
				$footerContent = '';
			}
		} else {
			$footerContent = '';
		}
		$this->fields['content'] = $headerContent . $this->fields['content'] . $footerContent;
	}

}
