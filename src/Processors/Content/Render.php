<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : MWFlexForm
 * Filename    : Render.php
 * Description :
 * Date        : 30-12-2021
 * Time        : 21:29
 */

namespace FlexForm\Processors\Content;

use \ApiMain, \DerivativeContext, \FauxRequest, \DerivativeRequest, MWException, RequestContext, FlexForm\Processors\Utilities\General;
use ContentHandler;
use FlexForm\Core\Config;
use FlexForm\Core\Debug;
use MediaWiki\Content\ContentHandlerFactory;
use Title;
use User;
use WikiPage;
use FlexForm\FlexFormException;

class Render {


	/**
	 * @param int|string $id
	 * @param string $slotName
	 *
	 * @return array
	 */
	public function getSlotContent( $id, string $slotName = 'main' ) : array {
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Getting Content for ' . time(),
				[ 'id' => $id,
					'slotname' => $slotName ]
			);
		}
		$ret = [];
		if ( is_int( $id ) ) {
			$title = Title::newFromID( $id );
			$page = WikiPage::newFromId( $title->getId() );
		} elseif ( is_string( $id ) ) {
			$titleObject = Title::newFromText( $id );
			try {
				$page = WikiPage::factory( $titleObject );
			} catch ( MWException $e ) {
				throw new FlexFormException(
					"Could not create a WikiPage Object from title " . $titleObject->getText(
					) . '. Message ' . $e->getMessage(),
					0,
					$e
				);
			}
		}

		$ret['content'] = '';
		$ret['title']   = '';

		if ( $page === false || $page === null ) {
			return $ret;
		}

		$ret['title']    = $page->getTitle()->getFullText();
		$latest_revision = $page->getRevisionRecord();
		if ( $latest_revision === null ) {
			return $ret;
		}
		if ( $latest_revision->hasSlot( $slotName ) ) {
			$content_object = $latest_revision->getContent( $slotName );
			if ( $content_object === null ) {
				return $ret;
			}

			$content_handler = $content_object->getContentHandler( $content_object );
			//$content_handler = ContentHandlerFactory::getContentHandler();

			$ret['content'] = $content_handler->serializeContent( $content_object );
			$ret['title']   = $page->getTitle()->getFullText();

			return $ret;
		} else {
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'no slot for ' . $id,
					[ 'id' => $id,
					  'slotname' => $slotName ]
				);
			}
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
		global $wgRequest;
		$wgUser = RequestContext::getMain()->getUser();
		// TODO: Add switch to only allow this when it is safe.
		if ( $wgUser->isAnon() ) {
			$wgUser = User::newSystemUser( 'FlexForm', [ 'steal' => true ] );
		}
		$apiRequest = new FauxRequest(
			$data,
			true,
			$wgRequest->getSession()
		);
		$context    = new DerivativeContext( new RequestContext() );
		$context->setRequest( $apiRequest );
		$context->setUser( $wgUser );
		$api = new ApiMain(
			$context,
			true
		);
		$api->execute();

		$result = $api->getResult()->getResultData();
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'FauxRequest ' . time(),
				[ 'result' => $result,
				 'post-data' => $data ]

			);
		}
		return $result;
	}
}