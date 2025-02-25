<?php

namespace FlexForm\Core;

use FlexForm\FlexFormException;
use FlexForm\Processors\Content\ContentCore;
use FlexForm\Processors\Content\Mail;
use FlexForm\Processors\Utilities\General;
use MediaWiki\MediaWikiServices;
use Exception;
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

	public static $messageTypes = [ 'danger', 'warning', 'success', 'info', 'html' ];

	public function __construct() {
		$this->lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$this->user = RequestContext::getMain()->getUser();
	}

	/**
	 * @param array $ffMessages
	 *
	 * @return void
	 * @throws FlexFormException
	 * @throws Exception
	 */
	public function setMessages( array $ffMessages ) {
		$separator = General::getPostString( 'ff_separator' );
		$sep = '^^-^^';
		$mail = new Mail();
		foreach ( $ffMessages as $singleMessage ) {
			$exploded = explode( $sep, $singleMessage );
			$user = ContentCore::parseTitle( trim( $exploded[0] ), true );
			$type = ContentCore::parseTitle( trim( $exploded[1] ), true );
			if ( !in_array( strtolower( $type ), self::$messageTypes ) ) {
				continue;
			}
			$message = $mail->parseWikiText( ContentCore::parseTitle( trim( $exploded[2] ), true ) );
			$title = $mail->parseWikiText( ContentCore::parseTitle( trim( $exploded[3] ), true ) );
			if ( $type !== 'html' ) {
				$message = str_replace( ['<p>','</p>'], '', $message );
				$title = str_replace( ['<p>','</p>'], '', $title );
			}
			$persistent = ContentCore::parseTitle( trim( $exploded[4] ), true );
			if ( $persistent === 'message-confirm' ) {
				$persistent = true;
			} else {
				$persistent = false;
			}
			if ( strpos( $user, $separator ) !== false ) {
				$users = explode( $separator, $user );
			} else {
				$users = [ $user ];
			}
			foreach ( $users as $singleUser ) {
				$newUser = MediaWikiServices::getInstance()->getUserFactory()->newFromName( trim( $singleUser ) );
				if ( $newUser !== false ) {
					$id = $newUser->getId();
					if ( $id !== 0 ) {
						$this->addMessage(
							$type,
							$message,
							$title,
							$id,
							$persistent
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
	 * @param bool $persistent
	 *
	 * @return bool
	 */
	public function addMessage(
		string $type,
		string $message,
		string $title = '',
		int $userId = 0,
		bool $persistent = false
	): bool {
		if ( $persistent ) {
			$persistent = 1;
		} else {
			$persistent = 0;
		}
		$dat = [ "type" => $type,
				 "message" => $message,
				 "title" => $title,
				 "user"     => $userId,
				 "persistent" => $persistent,
				 "initiator"  => $this->user->getId()
		];
		if ( Config::isDebug() ) {
			$debugTitle = '<b>' . get_class( $this ) . '<br>Function: ' . __FUNCTION__ . '<br></b>';

			Debug::addToDebug( $debugTitle . 'Adding message to database', $dat );
		}
		$dbw = MediaWikiServices::getInstance()->getConnectionProvider()->getPrimaryDatabase();
		if ( $userId === 0 ) {
			$userId = $this->user->getId();
		}
		if ( $userId === 0 || empty( $message ) ) {
			return false;
		}
		$dat = [ 'user' => $userId,
				 'type' => $type,
				 'title' => $title,
				 'message' => $message,
				 'persistent' => $persistent,
				 'initiator' => $this->user->getId() ];
		try {
			$result = $dbw->insert( self::DBTABLE,
				$dat,
				__METHOD__ );
		} catch ( Exception $e ) {
			echo $e->getMessage();
			if ( Config::isDebug() ) {
				Debug::addToDebug( "Cannot add message to database", $e->getMessage() );
			}

			return false;
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug( "Adding message to database result", $result );
		}
		global $IP;
		return true;
	}

	/**
	 * @return array
	 */
	public function getAllMessages(): array {
		$dbr = MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase();
		$select      = [ '*' ];
		$res = $dbr->select(
			self::DBTABLE,
			$select,
			'',
			__METHOD__,
			[]
		);
		$messages = [];
		if ( $res->numRows() > 0 ) {
			$t = 0;
			while ( $row = $res->fetchRow() ) {
				$messages[$t]['id'] = $row['id'];
				$messages[$t]['user'] = $row['user'];
				$messages[$t]['type'] = $row['type'];
				$messages[$t]['message'] = $row['message'];
				$messages[$t]['title'] = $row['title'];
				$messages[$t]['persistent'] = $row['persistent'];
				$messages[$t]['from'] = $row['initiator'];
				$messages[$t]['added'] = $row['added'];
				$t++;
			}
		}
		return $messages;
	}

	/**
	 * @param int $mId
	 *
	 * @return int
	 */
	public function getUserIdFromMessageId( int $mId ): int {
		$dbr = MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase();
		$select      = [ 'user' ];
		$selectWhere = [
			"id = '" . $mId . "'"
		];
		$res = $dbr->select(
			self::DBTABLE,
			$select,
			$selectWhere,
			__METHOD__,
			[]
		);

		if ( $res->numRows() > 0 ) {
			$row = $res->fetchRow();
			return $row['user'];
		} else {
			return 0;
		}
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
		$dbr = MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase();
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
				$messages[$t]['id'] = $row['id'];
				$messages[$t]['type'] = $row['type'];
				$messages[$t]['message'] = $row['message'];
				$messages[$t]['title'] = $row['title'];
				$messages[$t]['persistent'] = $row['persistent'];
				$messages[$t]['from'] = $row['initiator'];
				$messages[$t]['date'] = $row['added'];
				$t++;
			}
			$this->removeUserMessages( $userId );
		}
		return $messages;
	}

	/**
	 * @param int $mId
	 * @param bool $checkUser
	 *
	 * @return bool
	 * @throws FlexFormException
	 */
	public function removeUserMessageById( int $mId, bool $checkUser = false ): bool {
		if ( $checkUser ) {
			$userForMId = $this->getUserIdFromMessageId( $mId );
			$userId = $this->user->getId();
			if ( $userId !== $userForMId ) {
				return false;
			}
		}
		$dbw = MediaWikiServices::getInstance()->getConnectionProvider()->getPrimaryDatabase();
		try {
			$res = $dbw->delete(
				self::DBTABLE,
				[ "id = " . $mId ],
				__METHOD__
			);
		} catch ( Exception $e ) {
			throw new FlexFormException( 'Database error : ' . $e );
		}

		if ( $res ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param int $uId
	 *
	 * @return bool
	 * @throws FlexFormException
	 */
	public function removeUserMessages( int $uId ): bool {
		$dbw = MediaWikiServices::getInstance()->getConnectionProvider()->getPrimaryDatabase();
		try {
			$res = $dbw->delete(
				self::DBTABLE,
				[ "user = " . $uId, "persistent = 0" ],
				__METHOD__
			);
		} catch ( Exception $e ) {
			throw new FlexFormException( 'Database error : ' . $e );
		}

		if ( $res ) {
			return true;
		} else {
			return false;
		}
	}

}