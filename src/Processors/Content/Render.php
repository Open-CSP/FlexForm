<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWWSForm
 * Filename    : Render.php
 * Description :
 * Date        : 30-12-2021
 * Time        : 21:29
 */

namespace WSForm\Processors\Content;

use \ApiMain, \DerivativeContext, \FauxRequest, \DerivativeRequest, MWException, RequestContext, WSForm\Processors\Utilities\General;
use ContentHandler;
use MediaWiki\Content\ContentHandlerFactory;
use WikiPage;
use WSForm\WSFormException;

class Render {


	/**
	 * @param int|string $id
	 * @param string $slotName
	 *
	 * @return array
	 */
	public function getSlotContent( $id, string $slotName = 'main' ) : array {
		$ret            = array();
		$id             = (int) ( $id );
		$page           = WikiPage::newFromId( $id );
		$ret['content'] = '';
		$ret['title']   = '';

		if ( $page === false || $page === null ) {
			return $ret;;
		}

		$ret['title']    = $page->getTitle()->getFullText();
		$latest_revision = $page->getRevisionRecord();
		if ( $latest_revision === null ) {
			return $ret;;
		}
		if ( $latest_revision->hasSlot( $slotName ) ) {
			$content_object = $latest_revision->getContent( $slotName );
			if ( $content_object === null ) {
				return $ret;;
			}

			$content_handler = $content_object->getContentHandler( $content_object );
			//$content_handler = ContentHandlerFactory::getContentHandler();

			$ret['content'] = $content_handler->serializeContent( $content_object );
			$ret['title']   = $page->getTitle()->getFullText();

			return $ret;
		}

		return $ret;;
	}

	/**
	 * Make a Derivative request to the API
	 *
	 * @param array $data
	 *
	 * @return mixed
	 * @throws MWException
	 */
	public function makeRequest( array $data ) {
		global $wgUser;
		$apiRequest = new FauxRequest(
			$data,
			true,
			null
		);
		$context    = new DerivativeContext( new RequestContext() );
		$context->setRequest( $apiRequest );
		$context->setUser( $wgUser );
		$api = new ApiMain(
			$context,
			true
		);
		$api->execute();

		return $api->getResult()->getResultData();
	}


}