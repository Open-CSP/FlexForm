<?php

namespace FlexForm\Core;

use FlexForm\FlexFormException;
use MediaWiki\MediaWikiServices;
use RequestContext;
use User;
use Wikimedia\Rdbms\ILoadBalancer;

class Messaging {


	private const DBTABLE = 'flexformmsg';

	/**
	 * @var ILoadBalancer
	 */
	private ILoadBalancer $lb;

	/**
	 * @var User
	 */
	private User $user;


	public function __construct() {
		$this->lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$this->user = RequestContext::getMain()->getUser();
	}


	/**
	 * @param string $type
	 * @param string $message
	 * @param int $userId
	 *
	 * @return bool
	 */
	public function addMessage( string $type, string $message, int $userId = 0 ) : bool {
		$dbw = $this->lb->getConnectionRef( DB_PRIMARY );
		if ( $userId === 0 ) {
			$userId = $this->user->getId();
		}
		if ( $userId === 0 || empty( $message ) ) {
			return false;
		}
		try {
			$dbw->insert( self::DBTABLE,
				[ 'user' => $userId,
					'type' => $type,
					'message' => $message ],
				__METHOD__ );
		} catch ( \Exception $e ) {
			echo $e;

			return false;
		}

		return true;
	}

	/**
	 * @param int $userId
	 *
	 * @return array
	 * @throws FlexFormException
	 */
	public function getMessagesForUser( int $userId = 0 ): array {
		if ( $userId === 0 ) {
			$userId = $this->user->getId();
		}
		if ( $userId === 0 ) {
			return [];
		}
		$dbr         = $this->lb->getConnectionRef( DB_REPLICA );
		$select      = [ '*' ];
		$selectWhere = [
			"user = '" . $userId . "'"
		];
		$res = $dbr->select(
			self::DBTABLE,
			$select,
			$selectWhere,
			__METHOD__,
			[]
		);

		$messages = [];
		if ( $res->numRows() > 0 ) {
			$t = 0;
			while ( $row = $res->fetchRow() ) {
				$messages[$t]['type'] = $row['type'];
				$messages[$t]['message'] = $row['message'];
				$t++;
			}
			$this->removeUserMessages( $userId );
		}
		return $messages;
	}

	/**
	 * @param int $uId
	 *
	 * @return bool
	 * @throws FlexFormException
	 */
	public function removeUserMessages( int $uId ): bool {
		$dbw = $this->lb->getConnectionRef( DB_PRIMARY );
		try {
			$res = $dbw->delete(
				self::DBTABLE,
				"user = " . $uId,
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

}