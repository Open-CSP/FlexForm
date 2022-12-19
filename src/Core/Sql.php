<?php

namespace FlexForm\Core;

use DatabaseUpdater;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\Render;
use FlexForm\Processors\Utilities\General;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\SlotRecord;
use MediaWiki\User\UserIdentity;
use MWException;
use WikiPage;

class Sql {

	private const DBTABLE = 'flexform';

	/**
	 * @param DatabaseUpdater $updater
	 *
	 * @return bool
	 * @throws MWException
	 */
	public static function addTables( DatabaseUpdater $updater ) {
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
			throw new MWException(
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

	/**
	 * @param string $content
	 *
	 * @return mixed
	 */
	public static function getAllFormTags( string $content ) {
		preg_match_all( '/<form[^>]*>([\s\S]*)<\/form>/U', $content, $result1 );
		preg_match_all( '/<wsform[^>]*>([\s\S]*)<\/wsform>/U', $content, $result2 );
		preg_match_all( '/<_form[^>]*>([\s\S]*)<\/_form>/U', $content, $result3 );
		// preg_match_all( '/<form(.|\n)*?<\/form>/', $content, $result );
		return array_merge_recursive( $result1[1], $result2[1], $result3[1] );
	}

	/**
	 * @param $slots
	 *
	 * @return array
	 */
	public static function createFormHashes( array $slots ): array {
		$forms = [];
		foreach ( $slots as $slotName => $slotContent ) {
			if ( !empty( $slotContent ) ) {
				$forms[$slotName] = self::getAllFormTags( $slotContent );
			}
		}
		$hashes = [];
		foreach ( $forms as $page ) {
			foreach ( $page as $singleForm ) {
				if ( !empty( trim( $singleForm ) ) ) {
					$hashes[] = self::createHash( trim( $singleForm ) );
				}
			}
		}
		return $hashes;
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
		//$idExists = self::exists( $id );
		if ( Rights::isUserAllowedToEditorCreateForms() ) {
			$render = new Render();
			$content = $render->getSlotsContentForPage(	$id	);
			$hashes = self::createFormHashes( $content );
			$result = self::addPageId( $id, $hashes );
			if ( $result === false ) {
				throw new FlexFormException( 'Can\'t save to Database [add]' );
			}
		} else {
			$result = self::removePageId( $id );
			if ( $result === false ) {
				throw new FlexFormException( 'Can\'t save to Database [remove]' );
			}
		}
		return true;
	}

	/**
	 * @param int $pageId
	 * @param array $hashes
	 *
	 * @return bool
	 */
	private static function addPageId( int $pageId, array $hashes ): bool {
		$lb          = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbw         = $lb->getConnectionRef( DB_PRIMARY );
		try {
			foreach ( $hashes as $hash ) {
				if ( !self::exists( $pageId, $hash ) ) {
					$res = $dbw->insert(
						self::DBTABLE,
						[
							'page_id'     => $pageId,
							'hash_string' => $hash
						],
						__METHOD__
					);
				}
			}
		} catch ( \Exception $e ) {
			echo $e;
			return false;
		}
		return true;
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
	 * @param string $hash
	 *
	 * @return bool
	 */
	public static function exists( int $pageId, string $hash ):bool {
		$lb          = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr         = $lb->getConnectionRef( DB_REPLICA );
		$select      = [ 'page_id', "count" => 'COUNT(*)' ];
		$selectOptions    = [
			'LIMIT'    => 1
		];
		$selectWhere = [
			"page_id = '" . $pageId . "'",
			"hash_string = '" . $hash . "'"
		];
		$res = $dbr->select(
			self::DBTABLE,
			$select,
			$selectWhere,
			__METHOD__,
			$selectOptions
		);

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
