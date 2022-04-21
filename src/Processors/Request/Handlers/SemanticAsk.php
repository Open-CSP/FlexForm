<?php

namespace FlexForm\Processors\Request\Handlers;

use FlexForm\Core\Core;
use FlexForm\Core\HandleResponse;
use FlexForm\Processors\Utilities\General;

class SemanticAsk {

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
	 * @throws \MWException
	 */
	public function execute( HandleResponse $responseHandler ) {
		$query          = base64_decode( General::getGetString( 'query', true, false ) );
		$q              = General::getGetString( 'q', true, false );
		$returnId       = General::getGetString( 'returnid', true, false );
		$returnText     = General::getGetString( 'returntext', true, false );
		$limit          = General::getGetString( 'limit', true, false );
		$ret            = [];
		$ret['results'] = [];
		// if( strlen( $q ) < 3 ) return $ret;
		if ( $query !== false ) {
			// $ret = createMsg('No query found.');
			// test query :  $query = "[[Class::Organization]] [[Name::~*ik*]]|?Name |format=json |limit=99999"
			// ik kan dat q worden voor select2 door !!! in te vullen in de query, deze wordt dan vervangen.
			if ( strpos( $query, '(' ) !== false && strpos( $query, ')' ) !== false ) {
				if ( strpos( $query, '(returntext=' ) !== false ) {
					$returnText = Core::get_string_between( $query, '(returntext=', ')' );
					$query = str_replace(
						'(returntext=' . $returnText . ')',
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
			//echo $q;
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

			// echo $query."<BR>";

			//[[Class::Organization]][[Name::~*ik*]]|?Name|?Contact|limit=99999
			//Process~*hallo*|?Name|?Name|limit=50
			//echo $query;

			$postdata = [
				"action" => "ask",
				"format" => "json",
				"query"  => $query
			];
			$mRequest = new \FlexForm\Processors\Content\Render();
			$data     = $mRequest->makeRequest( $postdata );

			if ( isset( $data['query']['results'] ) && !empty( $data['query']['results'] ) ) {
				$data = $data['query']['results'];

				$t = 0;
				foreach ( $data as $k => $val ) {
					if ( $returnText === false ) {
						$ret['results'][$t]['text'] = htmlentities( $val['displaytitle'] );
					} elseif ( isset( $val['printouts'][$returnText][0] ) ) {
						$ret['results'][$t]['text'] = htmlentities( $val['printouts'][$returnText][0] );
					} else {
						$ret['results'][$t]['text'] = 'Not found';
					}

					if ( $returnId === false ) {
						$ret['results'][$t]['id'] = htmlentities( $k );
					} elseif ( isset( $val['printouts'][$returnId][0] ) ) {
						$ret['results'][$t]['id'] = htmlentities( $val['printouts'][$returnId][0] );
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
		die();
	}
}


