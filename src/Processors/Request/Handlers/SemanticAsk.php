<?php

namespace FlexForm\Processors\Request\Handlers;

use Exception;
use FlexForm\Core\Core;
use FlexForm\Core\HandleResponse;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\Render;
use FlexForm\Processors\Utilities\General;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\IDatabase;

class SemanticAsk {

	// TODO: Rewrite this to be an proper API call for version 2.7.0

	/**
	 * @param string $query
	 *
	 * @return string
	 */
	private function getMainQuery( string $query ): string {
		$matches = [];
		$mainQuery = '';
		preg_match_all( '/\[\[(.*?)\]\]/', $query, $matches );
		foreach ( $matches[1] as $key => $match ) {
			// Looking for the actual query
			if ( strpos( $match, '!!!' ) ) {
				$matchExploded = explode( '::', $match );
				$mainQuery = $matchExploded[0];
				break;
			}
		}
		return $mainQuery;
	}

	/**
	 * Take a search string or part and add Uppercase first letter and all uppercase to them
	 * @param string $searchPart
	 *
	 * @return string
	 */
	private function createNewQuery( string $searchPart ): string {
		$q2    = ucwords( $searchPart );
		$q3    = strtoupper( $searchPart );
		return '~*' . $searchPart . '*||~*' . $q2 . '*||~*' . $q3 . '*';
	}

	/**
	 * @param HandleResponse $responseHandler
	 *
	 * @return void
	 * @throws Exception
	 */
	public function execute( HandleResponse $responseHandler ) {
		$ret            = [];
		$ret['results'] = [];
		$queryEncoded = General::getGetString( 'query', true, false );
		if ( $queryEncoded !== false ) {
			// $_GET will urldecode automatically. Make sure any spaces return to a +
			$query = base64_decode( str_replace( ' ', '+', $queryEncoded ) );
		} else {
			$query = false;
		}
		$q              = General::getGetString( 'q', true, false );
		$returnId       = General::getGetString( 'returnid', true, false );
		$returnText     = General::getGetString( 'returntext', true, false );
		$template       = General::getGetString( 'template', true, false );
		$limit          = General::getGetString( 'limit', true, false );

		if ( $query !== false ) {
			$filterQuery = false;
			if ( strpos( $query, '(' ) !== false && strpos( $query, ')' ) !== false ) {
				if ( strpos( $query, '(fquery=' ) !== false ) {
					$fQuery = Core::get_string_between( $query, '(fquery=', ')' );
					$fQueryOld = $fQuery;
					if ( strpos( $fQuery, '__^^__' ) !== false ) {
						$fform = General::getGetString( 'ffform', true, false );
						if ( $fform === false ) {
							$fQuery = '';
						} else {
							$fQuery = str_replace( '__^^__', base64_decode( $fform ), $fQuery );
							$filterQuery = true;
						}
					}
					$query = str_replace(
						'(fquery=' . $fQueryOld . ')',
						'',
						$query
					);
				}
				if ( strpos( $query, '(returntext=' ) !== false ) {
					$returnText = Core::get_string_between( $query, '(returntext=', ')' );
					$query = str_replace(
						'(returntext=' . $returnText . ')',
						'',
						$query
					);
				}
				if ( strpos( $query, '(template=' ) !== false ) {
					$template = Core::get_string_between( $query, '(template=', ')' );
					$query = str_replace(
						'(template=' . $template . ')',
						'',
						$query
					);
				}
				if ( strpos( $query, '(returnid=' ) !== false ) {
					$returnId = Core::get_string_between( $query, '(returnid=', ')' );
					$query = str_replace(
						'(returnid=' . $returnId . ')',
						'',
						$query
					);
				}
				if ( strpos( $query, '(limit=' ) !== false ) {
					$limit = Core::get_string_between( $query, '(limit=', ')' );
					$query = str_replace(
						'(limit=' . $limit . ')',
						'',
						$query
					);
				}
			}
			if ( $filterQuery ) {
				$query .= $fQuery;
			}

			if ( $q !== false ) {
				// Are there spaces in the query?
				if ( strpos( $q, ' ' ) !== false ) {
					$mainQuery = $this->getMainQuery( $query );
					$explodedQuery = explode( ' ', $q );
					$newQuery = '';
//
					foreach ( $explodedQuery as $seperated ) {
						if ( !empty( $seperated ) ) {
							$newQuery .= '[[' . $mainQuery . '::' . $this->createNewQuery( $seperated ) . ']]';
						}
					}
					$query = str_replace(
						'[[' . $mainQuery . '::!!!]]',
						$newQuery,
						$query
					);
				} else {
					$query = str_replace(
						'!!!',
						$this->createNewQuery( $q ),
						$query
					);
				}
			} else {
				$query = str_replace(
					'!!!',
					'',
					$query
				);
			}
			if ( $returnId !== false ) {
				$query .= '|?' . $returnId;
			}
			if ( $returnText !== false ) {
				$query .= '|?' . $returnText;
			}
			if ( $limit !== false ) {
				$query .= '|limit=' . $limit;
			} else {
				$query .= '|limit=50';
			}
			if ( $template !== false ) {
				$query .= '|template=' . $template;
			}

			// echo $query."<BR>";

			//[[Class::Organization]][[Name::~*ik*]]|?Name|?Contact|limit=99999
			//Process~*hallo*|?Name|?Name|limit=50
			//echo $query;

			$postdata = [
				"action" => "ask",
				"format" => "json",
				"query"  => $query
			];
			$mRequest = new Render();
			$data     = $mRequest->makeRequest( $postdata );

			if ( isset( $data['query']['results'] ) && !empty( $data['query']['results'] ) ) {
				$data = $data['query']['results'];

				$t = 0;
				foreach ( $data as $k => $val ) {
					if ( $returnText === false ) {
						$ret['results'][$t]['text'] = $val['displaytitle'];
					} elseif ( isset( $val['printouts'][$returnText][0] ) ) {
						$ret['results'][$t]['text'] = $val['printouts'][$returnText][0];
					} else {
						$ret['results'][$t]['text'] = 'Not found';
					}

					if ( $returnId === false ) {
						$ret['results'][$t]['id'] = $k;
					} elseif ( isset( $val['printouts'][$returnId][0] ) ) {
						$ret['results'][$t]['id'] = $val['printouts'][$returnId][0];
					} else {
						$ret['results'][$t]['id'] = 'Not found';
					}
					$t++;
				}
			}
		}
		header( 'Content-Type: application/json' );
		echo json_encode(
			$ret,
			JSON_PRETTY_PRINT
		);
		$database = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );

		if ( $database->writesPending() ) {

			// If there is still a database update pending, commit it here
			try {
				$database->commit(
					__METHOD__,
					IDatabase::FLUSHING_INTERNAL
				);
			} catch ( DBError | DBUnexpectedError $e ) {
				throw new FlexFormException(
					$e->getMessage(),
					0,
					$e
				);
			}
		}
		die();
	}
}


