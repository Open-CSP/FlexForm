<?php

namespace FlexForm\Processors\Request\Handlers;

use FlexForm\Core\HandleResponse;
use FlexForm\Processors\Utilities\General;

class SemanticAsk {
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
//if( strlen( $q ) < 3 ) return $ret;
		if ( $query !== false ) {
			//$ret = createMsg('No query found.');
			// test query :  $query = "[[Class::Organization]] [[Name::~*ik*]]|?Name |format=json |limit=99999"
			// ik kan dat q worden voor select2 door !!! in te vullen in de query, deze wordt dan vervangen.

			if ( $q !== false ) {
				$q2    = ucwords( $q );
				$q3    = strtoupper( $q );
				$query = str_replace(
					'!!!',
					'~*' . $q . '*||~*' . $q2 . '*||~*' . $q3 . '*',
					$query
				);
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

			//echo $query."<BR>";

			//[[Class::Organization]][[Name::~*ik*]]|?Name|?Contact|limit=99999
			//Process~*hallo*|?Name|?Name|limit=50
			//echo $query."<pre>";

			$postdata = [
				"action" => "ask",
				"format" => "json",
				"query"  =>  $query
			];
			$mRequest = new \FlexForm\Processors\Content\Render();
			$data     = $mRequest->makeRequest( $postdata );

			if ( isset( $data['query']['results'] ) && ! empty( $data['query']['results'] ) ) {
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


