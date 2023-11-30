<?php

namespace FlexForm\Core;

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

}