<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : Render.php
 * Description :
 * Date        : 30-12-2021
 * Time        : 21:29
 */

namespace WSForm\Processors\Content;

use \ApiMain,
	\DerivativeContext,
	\FauxRequest,
	\DerivativeRequest,
	MWException,
	RequestContext,
	WSForm\Processors\Utilities\General;

class Render {


	public function parseWikiText( string $text ): string {
		$parser = \MediaWiki\MediaWikiServices::getInstance()->getParser();
		$parser = clone $parser;
		$title = \Title::newMainPage();
		$context = \RequestContext::getMain();
		if ( $title->canExist() ) {
			$page_object = \WikiPage::factory( $title );
		} else {
			$article = \Article::newFromTitle( $title, $context );
			$page_object = $article->getPage();
		}
		$parser_options = $page_object->makeParserOptions( $context );
		if ( method_exists( $parser, "setOptions" ) ) {
			$parser->setOptions( $parser_options );
		} else {
			$parser->mOptions = $parser_options;
		}
		if ( method_exists( $parser, "setTitle" ) ) {
			$parser->setTitle( $title );
		} else {
			$parser->mTitle = $title;
		}
		$parser->clearState();
		return $parser->recursiveTagParseFully( $text );
	}

	/**
	 * Make a Derivative request to the API
	 *
	 * @param array $data
	 *
	 * @return mixed
	 * @throws MWException
	 */
	public function makeRequest( array $data ){
		global $wgUser;
		$apiRequest = new FauxRequest( $data, true, null );
		$context = new DerivativeContext( new RequestContext() );
		$context->setRequest( $apiRequest );
		$context->setUser( $wgUser );
		$api = new ApiMain( $context, true );
		$api->execute();
		return $api->getResult()->getResultData();
	}

	// NOT USED, ONLY FOR REFERENCE
	public function makeRequest2( $data ){
		global $wgUser, $wgRequest;

		$api = new ApiMain(
			new DerivativeRequest( $wgRequest,
								   $data,
								   true ),
			false
		);
		$api->execute();
		$data = $api->getResult()->getResultData();
		//$data = $data['parse']['text'];
		//$data = wsUtilities::get_string_between_until_last( $data, '<div class="mw-parser-output"><p>','</p></div>');
		echo "<pre>";
		var_dump($data);


	}

	/**
	 * @return array|false
	 */
	public function renderWikiTxt() {

		$wikiTxt = wsUtilities::getPostString( 'wikitxt' );
		if( $wikiTxt === false ) {
			return false;
		}
		try {
			$result =  $this->parseWikiText( $wikiTxt );
			return trim( wsUtilities::get_string_between_until_last( $result, '<p>','</p>') );
		} catch ( MWException $e ) {
			return $e->getText();
		}
		/* OLD METHOD HERE FOR REFERENCE
		$postdata = [
			"action" => "parse",
			"format" => "json",
			"text" => $wikiTxt,
			"contentmodel" => "wikitext",
			"disablelimitreport" => "1"
		];
		$ret = array();
		$result = $this->makeRequest( $postdata );
		$result = wsUtilities::get_string_between_until_last( $result['parse']['text'], '<div class="mw-parser-output"><p>','</p></div>');
		if( $result !== "" ) {
			return wbHandleResponses::createMsg( $result, 'ok' );
		} else {
			$ret['status'] = 'error';
			$ret['error'] = wfMessage( 'wsform-renderwiki-no-result' )->plain();
		}
		return $ret;
		 **/
	}

}