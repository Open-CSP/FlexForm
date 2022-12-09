<?php

namespace FlexForm\Core;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
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
		$tables = __DIR__ . "/sql/FlexForm.$dbt";

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
					'flexform-unspported-database',
					$dbt
				)
			);
		}

		return true;
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
	 */
	public static function pageSaved(
		WikiPage $article,
		UserIdentity $user,
		string $summary,
		int $flags,
		RevisionRecord $revisionRecord,
		EditResult $editResult
	) : bool {
		return true;
	}

	/**
	 * @param int $pageId
	 *
	 * @return bool
	 */
	public function exists( int $pageId ):bool {
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
