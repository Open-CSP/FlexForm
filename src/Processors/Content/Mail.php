<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWWSForm
 * Filename    : Mail.php
 * Description :
 * Date        : 28-1-2022
 * Time        : 20:34
 */

namespace WSForm\Processors\Content;

use WSForm\Core\Config;
use WSForm\Core\Debug;
use WSForm\Processors\Definitions;
use WSForm\Processors\Security\wsSecurity;
use WSForm\WSFormException;

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
	 * @return false|mixed|string
	 */
	public function getTemplate() {
		return $this->template;
	}

	public function __construct() {
		$this->fields   = Definitions::mailFields();
		$this->template = $this->fields['mtemplate'];
	}

	/**
	 * @param string $content
	 *
	 * @return mixed
	 * @throws WSFormException
	 * @throws \MWException
	 */
	private function parseWikiText( string $content ) : string {
		$render   = new Render();
		$postdata = [
			"action"             => "parse",
			"format"             => "json",
			"text"               => $content,
			"contentmodel"       => "wikitext",
			"disablelimitreport" => "1"
		];
		$result   = $render->makeRequest( $postdata );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'textparse result ' . time(),
				$result
			);
		}
		if ( isset( $result['error'] ) ) {
			throw new WSFormException(
				$result['error']['info'],
				0
			);
		}

		return $result['parse']['text']['*'];
	}

	/**
	 * @param string $title
	 *
	 * @return string
	 * @throws WSFormException
	 * @throws \MWException
	 */
	private function parseWikiPageByTitle( string $title ) : string {
		$render   = new Render();
		$postdata = [
			"action"             => "parse",
			"format"             => "json",
			"page"               => $title,
			"disablelimitreport" => "1"
		];
		$result   = $render->makeRequest( $postdata );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Parse result ' . time(),
				$result
			);
		}
		if ( isset( $result['error'] ) ) {
			throw new WSFormException(
				$result['error']['info'],
				0
			);
		}

		return $result['parse']['text']['*'];
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	private function placeValuesInTemplate( string $content ) {
		// Get all form elements and replace in Template
		foreach ( $_POST as $k => $v ) {
			if ( Definitions::isWSFormSystemField( $k ) ) {
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
						cleanBraces( $v ),
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
		$fieldToGetAndReplace = [
			'to',
			'from',
			'cc',
			'bcc',
			'reply-to',
			'header',
			'footer',
			'html',
			'subject',
			'content'
		];
		foreach ( $fieldToGetAndReplace as $field ) {
			$regex = '#%ws_' . $field . '=(.*?)%#';
			preg_match(
				$regex,
				$template,
				$tmp
			);
			//echo "<pre>";
			//print_r($tmp);
			//echo "</pre>";
			if ( isset( $tmp[1] ) ) {
				$tmp = $tmp[1];
			} else {
				$this->fields[$field] = false;
			}
			// $tmp = $this->get_string_between( $template, '%ws_' . $name . '=' , '%' );
			// echo "<p>found : $tmp</p>";
			if ( $tmp !== "" ) {
				$this->fields[$field] = $tmp;
				$template             = str_replace(
					'%ws_' . $field . '=' . $tmp . '%',
					'',
					$template
				);
			} else {
				$this->fields[$field] = false;
			}
		}

		return $template;
	}

	/**
	 * @return void
	 * @throws WSFormException
	 * @throws \MWException
	 */
	public function handleTemplate() {
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
		$fields = ContentCore::getFields();
		if ( $fields['parseLast'] === false ) {
			$tpl = $this->parseWikiPageByTitle( $this->getTemplate() );
		} else {
			$render = new Render();
			$tpl    = $render->getSlotContent( $this->getTemplate() );
		}
		$tpl = $this->placeValuesInTemplate( $tpl );
		if ( $fields['parseLast'] !== false ) {
			$tpl = $this->parseWikiText( $tpl );
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
		$tpl = $this->getTemplateValueAndDelete( $tpl );
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
			$this->fields['to'] = $to;
		}
		// END Always overrule form fields over template values



	}

	/**
	 * @return void
	 */
	private function createEmailBody() {
		if ( $this->fields['header'] !== false ) {
			try {
				$headerContent = $this->parseWikiPageByTitle( $this->fields['header'] );
			} catch ( WSFormException | \MWException $e ) {
				$headerContent = '';
			}
		} else {
			$headerContent = '';
		}
		if ( $this->fields['footer'] !== false ) {
			try {
				$footerContent = $this->parseWikiPageByTitle( $this->fields['footer'] );
			} catch ( WSFormException | \MWException $e ) {
				$footerContent = '';
			}
		} else {
			$footerContent = '';
		}
		$this->fields['content'] = $headerContent . $this->fields['content'] . $footerContent;
	}

}
