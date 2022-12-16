<?php

namespace FlexForm\Core;

use FlexForm\FlexFormException;
use FlexForm\Processors\Content\Render;
use FlexForm\Processors\Utilities\General;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\SlotRecord;
use MediaWiki\User\UserIdentity;
use WikiPage;

class Sql {

	private const DBTABLE = 'FLEXFORM';

	/**
	 * @param $updater
	 *
	 * @return bool
	 * @throws \MWException
	 */
	public static function addTables( $updater ) {
		$dbt = $updater->getDB()->getType();
		// If using SQLite, just use the MySQL/MariaDB schema, it's compatible
		// anyway. Only PGSQL and some more exotic variants need a totally
		// different schema.
		if ( $dbt === 'sqlite' ) {
			$dbt = 'sql';
		}
		$tables = __DIR__ . "/../../sql/FlexForm.$dbt";

		if ( file_exists( $tables ) ) {
			$updater->addExtensionUpdate( array(
											  'addTable',
											  self::DBTABLE,
											  $tables,
											  true
										  ) );
		} else {
			throw new \MWException(
				wfMessage(
					'flexform-unsupported-database',
					$dbt
				)
			);
		}

		return true;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public static function createHash( string $content ): string {
		return hash( 'md5', $content );
	}

	public static function getAllFormTags( $content ) {
		preg_match_all( '/<form(.|\n)*?<\/form>/', $content, $result );
		return $result;
	}

	public static function createFormHashes( $slots ) {
		echo "<pre>";
		foreach ( $slots as $slotContent ) {
			$forms = self::getAllFormTags( $slotContent );

			var_dump ( $forms );
		}
		echo "</pre>";
	}

	/**
	 * @param WikiPage $article
	 * @param UserIdentity $user
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult
	 *
	 * @return bool
	 * @throws Exception
	 * @throws FlexFormException
	 */
	public static function pageSaved(
		WikiPage $article,
		UserIdentity $user,
		string $summary,
		int $flags,
		RevisionRecord $revisionRecord,
		EditResult $editResult
	) : bool {
		$id = $article->getId();
		$idExists = self::exists( $id );
		if ( Rights::isUserAllowedToEditorCreateForms() ) {
			if ( $idExists ) {
				return true;
			} else {
				$render = new Render();
				$content = $render->getSlotsContentForPage(	$id	);
				self::createFormHashes( $content );
				die();
				$result = self::addPageId( $id );
				if ( $result === false ) {
					throw new FlexFormException( 'Can\'t save to Database [add]' );
				}
			}
		} else {
			if ( $idExists ) {
				$result = self::removePageId( $id );
				if ( $result === false ) {
					throw new FlexFormException( 'Can\'t save to Database [remove]' );
				}
			} else {
				return true;
			}
		}
		return true;
	}

	/**
	 * @param int $pageId
	 *
	 * @return bool
	 */
	private static function addPageId( int $pageId ): bool {
		$lb          = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbw         = $lb->getConnectionRef( DB_PRIMARY );
		try {
			$res = $dbw->insert(
				self::DBTABLE,
				[ 'page_id' => $pageId ],
				__METHOD__
			);
		} catch ( \Exception $e ) {
			echo $e;

			return false;
		}
		//var_dump( $table );
		//var_dump( $vals );
		//var_dump( $res );
		//die();
		if ( $res ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param int $pId
	 *
	 * @return bool
	 * @throws FlexFormException
	 */
	private static function removePageId( int $pId ): bool {
		$lb          = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbw         = $lb->getConnectionRef( DB_PRIMARY );
		try {
			$res = $dbw->delete(
				self::DBTABLE,
				"page_id = " . $pId,
				__METHOD__
			);
		} catch ( \Exception $e ) {
			throw new FlexFormException( 'Database error : ' . $e );
			return false;
		}

		if ( $res ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param int $pageId
	 *
	 * @return bool
	 */
	public static function exists( int $pageId ):bool {
		$lb          = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr         = $lb->getConnectionRef( DB_REPLICA );
		$select      = [
			'page_id',
			"count" => 'COUNT(*)'
		];
		$selectWhere = "page_id = '" . $pageId . "'";
		$res         = $dbr->newSelectQueryBuilder()->select( $select )->from( self::DBTABLE )->where( $selectWhere )
						   ->caller( __METHOD__ )->fetchResultSet();
		if ( $res->numRows() > 0 ) {
			$row = $res->fetchRow();
			if ( $row['count'] === '0' ) {
				return false;
			} else {
				return true;
			}
		}
		return false;
	}
}
