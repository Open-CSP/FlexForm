<?php

$query          = getGetString( 'query' );
$q              = getGetString( 'q' );
$returnId       = getGetString( 'returnid' );
$returnText     = getGetString( 'returntext' );
$limit          = getGetString( 'limit' );
$ret            = array();
$ret['results'] = array();
//if( strlen( $q ) < 3 ) return $ret;
if ( $query === false ) {
	//$ret = createMsg('No query found.');
	// test query :  $query = "[[Class::Organization]] [[Name::~*ik*]]|?Name |format=json |limit=99999"
	// ik kan dat q worden voor select2 door !!! in te vullen in de query, deze wordt dan vervangen.
} elseif ( strlen( $q ) >= 3 ) {
	$query = html_entity_decode( urldecode( $query ) );
	//var_dump($query);
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

	$postdata = http_build_query( [
									  "action" => "ask",
									  "format" => "json",
									  "query"  => $query
								  ] );
	$data     = $api->apiPost(
		$postdata,
		true
	);

	if ( isset( $data['received']['query']['results'] ) && ! empty( $data['received']['query']['results'] ) ) {
		$data = $data['received']['query']['results'];

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

