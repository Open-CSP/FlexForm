<?php

namespace FlexForm\Core;

use DatabaseUpdater;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\Render;
use Matrix\Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
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
	public static function addTables( DatabaseUpdater $updater ): bool {
		$dbt = $updater->getDB()->getType();
		// If using SQLite, just use the MySQL/MariaDB schema, it's compatible
		// anyway. Only PGSQL and some more exotic variants need a totally
		// different schema.
		if ( $dbt === 'sqlite' ) {
			$dbt = 'sql';
		}
		$tables = __DIR__ . "/../../sql/FlexForm.$dbt";
		if ( file_exists( $tables ) ) {
			$updater->addExtensionUpdate( [
											  'addTable',
											  self::DBTABLE,
											  $tables,
											  true
										  ] );
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
	public static function getAllFormTags( string $content, $specific = false ) {
		if ( !$specific ) {
			preg_match_all( '/<form[^>]*>([\s\S]*)<\/form>/U', $content, $result1 );
			preg_match_all( '/<wsform[^>]*>([\s\S]*)<\/wsform>/U', $content, $result2 );
			preg_match_all( '/<_form[^>]*>([\s\S]*)<\/_form>/U', $content, $result3 );

			// preg_match_all( '/<form(.|\n)*?<\/form>/', $content, $result );
			return array_merge_recursive( $result1[1], $result2[1], $result3[1] );
		} else {
			$result = [];
			switch ( $specific ) {
				case "wsform":
					preg_match_all( '/<wsform[^>]*>([\s\S]*)<\/wsform>/U', $content, $result );
					break;
				case "_form":
					preg_match_all( '/<_form[^>]*>([\s\S]*)<\/_form>/U', $content, $result );
					break;
				case "form":
					preg_match_all( '/<form[^>]*>([\s\S]*)<\/form>/U', $content, $result );
					break;
			}
			return $result[1];
		}
	}

	/**
	 * @param array $slots
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
		try {
			if ( Rights::isUserAllowedToEditorCreateForms() ) {
				self::removePageId( $id );
				$render = new Render();
				$content = $render->getSlotsContentForPage( $id );
				$hashes = self::createFormHashes( $content );
				$result = self::addPageId(
					$id,
					$hashes
				);
				if ( $result === false ) {
					throw new FlexFormException( 'Can\'t save to Database [add]' );
				}
			} else {
				$result = self::removePageId( $id );
				if ( $result === false ) {
					throw new FlexFormException( 'Can\'t save to Database [remove]' );
				}
			}
		} catch ( Exception $e ) {
			var_dump( $e->getMessage());
			die();
		}
		return true;
	}

	/**
	 * @param string $json
	 *
	 * @return bool
	 * @throws FlexFormException
	 */
	public static function addPagesFromIds( string $json ): bool {
		$IDArrays = json_decode( $json, true );
		if ( $IDArrays === null ) {
			return false;
		}
		foreach ( $IDArrays as $id ) {
			$result = self::addPageFromId( $id );
			if ( $result !== true ) {
				throw new FlexFormException( 'Can\'t save to Database [add]' );
			}
		}
		return true;
	}

	/**
	 * @param int $id
	 *
	 * @return true
	 * @throws FlexFormException
	 */
	public static function addPageFromId( int $id ) {
		$render = new Render();
		$content = $render->getSlotsContentForPage(	$id	);
		// Page has no content, does not exist or any other weird stuff
		if ( $content === false ) {
			return true;
		}
		$hashes = self::createFormHashes( $content );
		$result = self::addPageId( $id, $hashes );
		if ( $result === false ) {
			throw new FlexFormException( 'Can\'t save to Database [add]' );
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
					$dbw->insert(
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
	public static function removePageId( int $pId ): bool {
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
		}

		if ( $res ) {
			return true;
		} else {
			return false;
		}
	}

	public static function getAllApprovedForms() {
		$lb          = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr         = $lb->getConnectionRef( DB_REPLICA );
		$select      = [ 'page_id', "count" => 'COUNT(*)' ];
		$selectOptions = [
			'GROUP BY' => 'page_id',
			'ORDER BY' => 'count DESC'
		];
		$res = $dbr->select(
			self::DBTABLE,
			$select,
			[],
			__METHOD__,
			$selectOptions
		);

		$pages = [];
		if ( $res->numRows() > 0 ) {
			while ( $row = $res->fetchRow() ) {
				$pId = $row['page_id'];
				$cnt = $row['count'];
				$pages[$pId] = $cnt;
			}
			return $pages;
		} else {
			return [];
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
