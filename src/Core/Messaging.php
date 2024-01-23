<?php

namespace FlexForm\Core;

use FlexForm\FlexFormException;
use FlexForm\Processors\Content\ContentCore;
use FlexForm\Processors\Content\Mail;
use FlexForm\Processors\Utilities\General;
use MediaWiki\MediaWikiServices;
use MWException;
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
	 * @param array $ffMessages
	 *
	 * @return void
	 * @throws FlexFormException
	 * @throws MWException
	 */
	public function setMessages( array $ffMessages ) {
		$separator = General::getPostString( 'ff_separator' );
		$sep = '^^-^^';
		$mail = new Mail();
		foreach ( $ffMessages as $singleMessage ) {
			$exploded = explode( $sep, $singleMessage );
			$user = ContentCore::parseTitle( trim( $exploded[0] ), true );
			$type = ContentCore::parseTitle( trim( $exploded[1] ), true );
			$message = $mail->parseWikiText( ContentCore::parseTitle( trim( $exploded[2] ), true ) );
			$title = ContentCore::parseTitle( trim( $exploded[3] ), true );
			if ( strpos( $user, $separator ) !== false ) {
				$users = explode( $separator, $user );
			} else {
				$users = [ $user ];
			}
			foreach ( $users as $singleUser ) {
				$newUser = User::newFromName( trim( $singleUser ) );
				if ( $newUser !== false ) {
					$id = $newUser->getId();
					if ( $id !== 0 ) {
						$this->addMessage(
							$type,
							$message,
							$title,
							$id
						);
					}
				}
			}
		}
	}

	/**
	 * @param string $type
	 * @param string $message
	 * @param string $title
	 * @param int $userId
	 *
	 * @return bool
	 */
	public function addMessage( string $type, string $message, string $title = '', int $userId = 0 ) : bool {
		if ( Config::isDebug() ) {
			$debugTitle = '<b>' . get_class() . '<br>Function: ' . __FUNCTION__ . '<br></b>';
			Debug::addToDebug(
				$debugTitle . 'Adding message to database',
				[ "type" => $type,
				  "message" => $message,
				  "title" => $title,
				  "userid" => $userId ]
			);
		}
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
							'title' => $title,
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
				$messages[$t]['title'] = $row['title'];
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