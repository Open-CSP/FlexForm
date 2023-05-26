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
use MediaWiki\MediaWikiServices;
use MWUnknownContentModelException;
use Title;
use User;
use WikiPage;
use FlexForm\FlexFormException;

class Render {

	/**
	 * @param int $id
	 *
	 * @return array|false
	 */
	public function getSlotNamesForPageAndRevision( int $id ) {
		$page = WikiPage::newFromId( $id );
		if ( $page === false || $page === null ) {
			return false;
		}
		$latest_revision = $page->getRevisionRecord();
		if ( $latest_revision === null ) {
			return false;
		}

		return [
			"slots"           => $latest_revision->getSlotRoles(),
			"latest_revision" => $latest_revision
		];
	}

	/**
	 * @param int $id
	 *
	 * @return array|false
	 */
	public function getSlotsContentForPage( int $id ) {
		$slot_result = $this->getSlotNamesForPageAndRevision( $id );
		if ( $slot_result === false ) {
			return false;
		}
		$slot_roles      = $slot_result['slots'];
		$latest_revision = $slot_result['latest_revision'];

		$slot_contents = [];

		foreach ( $slot_roles as $slot_role ) {
			if ( !$latest_revision->hasSlot( $slot_role ) ) {
				continue;
			}

			$content_object = $latest_revision->getContent( $slot_role );

			if ( $content_object === null ) {
				continue;
			}
			$content_handler = MediaWikiServices::getInstance()->getContentHandlerFactory()->getContentHandler(
				$content_object->getModel()
			);

			$contentOfSLot = $content_handler->serializeContent( $content_object );

			if ( empty( $contentOfSLot ) && $slot_role !== 'main' ) {
				continue;
			}

			$slot_contents[$slot_role] = $contentOfSLot;
		}

		return $slot_contents;
	}

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
			$page = WikiPage::newFromId( $id );
			if ( $page === null ) {
				throw new FlexFormException(
					"Could not create a WikiPage Object from id: " . $id . '. Message ',
					0,
					null
				);
			}
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

		return $ret;
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
		try {
			$api->execute();
			$result = $api->getResult()->getResultData();
		} catch ( MWException $e ) {
			$result['error']['info'] = $e->getMessage();
		}
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