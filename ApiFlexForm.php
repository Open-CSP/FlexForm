<?php

use FlexForm\Core\Protect;
use FlexForm\FlexFormException;
use Wikimedia\ParamValidator\ParamValidator;

class ApiFlexForm extends ApiBase {

	private function returnFailure( $failure ) {
		$ret            = array();
		$ret['message'] = $failure;
		$this->getResult()->addValue( null,
									  $this->getModuleName(),
									  array( 'error' => $ret ) );
	}

	private function createResult( $code, $result ) {
		$ret           = array();
		$ret['status'] = $code;
		$ret['data']   = $result;

		return $ret;
	}

	private function excerpt( $text, $phrase, $radius = 100, $ending = "..." ) {
		$phraseLen = strlen( $phrase );
		if ( $radius < $phraseLen ) {
			$radius = $phraseLen;
		}

		$phrases = explode(
			' ',
			$phrase
		);

		foreach ( $phrases as $phrase ) {
			$pos = strpos(
				strtolower( $text ),
				strtolower( $phrase )
			);
			if ( $pos > -1 ) {
				break;
			}
		}

		$startPos = 0;
		if ( $pos > $radius ) {
			$startPos = $pos - $radius;
		}

		$textLen = strlen( $text );

		$endPos = $pos + $phraseLen + $radius;
		if ( $endPos >= $textLen ) {
			$endPos = $textLen;
		}

		$excerpt = substr(
			$text,
			$startPos,
			$endPos - $startPos
		);
		if ( $startPos != 0 ) {
			$excerpt = substr_replace(
				$excerpt,
				$ending,
				0,
				$phraseLen
			);
		}

		if ( $endPos != $textLen ) {
			$excerpt = substr_replace(
				$excerpt,
				$ending,
				-$phraseLen
			);
		}

		return $this->highlight(
			$excerpt,
			$phrase
		);
	}

	private function highlight( $c, $q ) {
		$q = explode(
			' ',
			str_replace(
				array(
					'',
					'\\',
					'+',
					'*',
					'?',
					'[',
					'^',
					']',
					'$',
					'(',
					')',
					'{',
					'}',
					'=',
					'!',
					'<',
					'>',
					'|',
					':',
					'#',
					'-',
					'_'
				),
				'',
				$q
			)
		);
		for ( $i = 0; $i < sizeOf( $q ); $i++ ) {
			$c = preg_replace(
				"/($q[$i])(?![^<]*>)/i",
				"<span class=\"highlight\">\${1}</span>",
				$c
			);
		}

		return $c;
	}

	/**
	 * @param string $txt
	 *
	 * @return array
	 */
	private function decrypt( string $txt ) : array {
		\FlexForm\Core\Config::setConfigFromMW();
		$crypt = new Protect();
		try {
			$crypt::setCrypt();
		} catch ( FlexFormException $exception ) {
			return $this->createResult(
				'error',
				$exception->getMessage()
			);
		}

		$json = json_decode( $txt, true );
		if ( $json === null ) {
			$json = $crypt::decrypt( $txt );
		} elseif ( is_array( $json ) ) {
			foreach ( $json as $k=>$v ){
				$json[$k] = $crypt::decrypt( $v );
			}
		} else {
			$json = $crypt::decrypt( $json );
		}

		return $this->createResult(
			'ok',
			$json
		);
	}

	private function searchDocs( $keyword ) {
		global $IP, $wgScript;
		$path     = $IP . '/extensions/FlexForm/docs/';
		$realUrl  = str_replace(
			'/index.php',
			'',
			$wgScript
		);
		$purl     = $realUrl . "/index.php/Special:FlexForm/Docs";
		$fileList = glob( $path . '*.json' );
		$data     = [];

		foreach ( $fileList as $file ) {
			$content = json_decode(
				file_get_contents( $file ),
				true
			);

			$type        = explode(
				'_',
				basename( $file ),
				2
			);
			$t           = $type[0];
			$n           = $type[1];
			$textToSeach = $content['doc']['description'] . $content['doc']['synopsis'] . $content['doc']['parameters'];
			$textToSeach .= $content['doc']['example'] . $content['doc']['note'] . $content['doc']['links'];
			$pos         = stripos(
				$textToSeach,
				$keyword
			);
			if ( $pos !== false ) {
				$pos            = (int) $pos;
				$tmparr         = [];
				$tmparr['name'] = substr(
					$n,
					0,
					-5
				);
				$tmparr['type'] = $t;
				$tmparr['link'] = $purl . '/' . substr(
						basename( $file ),
						0,
						-5
					) . "/" . $keyword;

				$tmparr['snippet'] = $this->excerpt(
					$textToSeach,
					$keyword
				);
				//$tmparr['snippet'] = substr( $content['doc']['description'], (int) $start, (int) $end );

				$data[] = $tmparr;
			}
		}
		//$ret[] = $keyword;
		$ret = $data;

		//$ret =  $this->array_search_own( $keyword, $data );
		return $ret;
	}

	private function array_search_own( $keyword, $data ) {
		error_reporting( -1 );
		ini_set(
			'display_errors',
			1
		);
		$newArr = [];
		if ( strpos( $data ) ) {
			return array_filter( $data,
				function ( $subarray ) use ( $keyword ) {
					if ( array_search(
						$keyword,
						$subarray
					) ) {
						return true;
					} else {
						return false;
					}
				} );
		}

		return null;
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$action = $params['what'];
		if ( ! $action || $action === null ) {
			$this->dieUsageMsg( 'missingparam' );
		}

		switch ( $action ) {
			case "searchdocs":
				$output = $this->searchDocs( $params['for'] );

				break;
			case "decrypt":

				$output = $this->decrypt( $params['titleStartsWith'] );
				if ( $output['status'] === "error" ) {
					$this->returnFailure( $output['data'] );
					break;
				}

				break;
			case "nextAvailable" :
				$title  = $params['titleStartsWith'];
				$result = $this->getNextAvailable( $title );
				if ( $result['status'] === "error" ) {
					$this->returnFailure( $result['data'] );
					break;
				}
				$output = $result['data'];
				//echo "--$result--"; die();
				//$this->getResult()->addValue(null, array('result' => "ok:" . $output) );
				break;
			case "getRange" :
				$title = $params['titleStartsWith'];
				$range = $params['range'];
				if ( ! $range || $range === null ) {
					$this->returnFailure( wfMessage( 'flexform-api-error-parameter-range-missing' )->text() );
					break;
				}
				$range = explode(
					'-',
					$range
				);

				if ( ! ctype_digit( $range[0] ) || ! ctype_digit( $range[1] ) ) {
					$this->returnFailure( wfMessage( 'flexform-api-error-bad-range' )->text() );
					break;
				}
				$startRange = (int) $range[0];
				$endRange   = (int) $range[1];

				$result = $this->getFromRange( $title,
											   array(
												   'start' => $startRange,
												   'end'   => $endRange
											   ) );
				if ( isset( $result['status'] ) && $result['status'] === "error" ) {
					$this->returnFailure( $result['data'] );
					break;
				}
				if ( isset( $result['data'] ) ) {
					$output = $result['data'];
				} else {
					$output = '';
				}
				break;
			default :
				$this->returnFailure( wfMessage( 'flexform-api-error-unknown-what-parameter' )->text() );
				break;
		}

		$this->getResult()->addValue( null,
									  $this->getModuleName(),
									  array( 'result' => $output ) );

		return true;
	}

	public function needsToken() {
		return false;
	}

	public function isWriteMode() {
		return false;
	}

	/**
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'what'            => array(
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => true
			),
			'titleStartsWith' => array(
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => true
			),
			'range'           => array(
				ParamValidator::PARAM_TYPE => 'string'
			),
			'for'             => array(
				ParamValidator::PARAM_TYPE => 'string'
			)
		);
	}

	private function getNextAvailable( $nameStartsWith ) {
		[$ns, $name] = $this->splitNamespace($nameStartsWith);

		// Add an 'x' to prevent makeTitleSafe from erroring out.
		// See also ApiQueryBase::titlePartToKey() on MW1.35
		$t = Title::makeTitleSafe($ns, $name.'x');
		if (!$t || $t->hasFragment() || $ns != $t->getNamespace() || $t->isExternal()) {
			return $this->createResult(
				'error',
				wfMessage( 'flexform-api-error-bad-title' )
			);
		}
		// Remove 'x' we added earlier.
		$t = substr($t->getDBkey(), 0, -1);

		$db = $this->getDB();
		$res = $db->newSelectQueryBuilder(
			)->select('page_title')->from('page')->where(
				'page_title' . $db->buildLike($t, $db->anyString())
			)->where(['page_namespace' => $ns])->orderBy(
				['length(page_title)', 'page_title'], 'DESC'
			)->fetchFieldValues();

		$rtr = 1;
		foreach ($res as $r) {
			$r = substr($r, strlen($t));
			if (empty($r)) {
				// Because of sort on title length, we are done now.
				break;
			}
			if (ctype_digit($r)) {
				$r = intval($r);
				if ($r >= $rtr) {
					$rtr = $r+1;
				}
				// Because of sort on title value, we are done now.
				break;
			}
		}
		return $this->createResult(
			'ok',
			$rtr
		);
	}

	/**
	 * Get the ID of the given namespace name
	 *
	 * @param string $ns
	 *
	 * @return bool|mixed Either the ID of the namespace or false when not found
	 */
	private function getIdForNameSpace( $ns ) {
		return $this->getLanguage()->getNsIndex($ns);
	}

	/**
	 * If there are more results from the API, get the next results
	 *
	 * @param $result array of previous API results
	 *
	 * @return bool when no further results
	 * @return string where to start next API call
	 */
	function getApiContinue( $result ) {
		if ( isset( $result['continue']['apcontinue'] ) ) {
			$appContinue = $result['continue']['apcontinue'];
		} else {
			$appContinue = false;
		}

		return $appContinue;
	}

	function getFromRange( $nameStartsWith, $range = false ) {
		$number      = array();
		$continue    = true;
		$appContinue = false;
		$cnt         = 0;
		while ( $continue ) {
			$result = $this->getDataForWikiList(
				$nameStartsWith,
				$appContinue,
				$range
			);
			//print_r($result);
			//die();
			$appContinue = $this->getApiContinue( $result );

			//var_dump( $appContinue );
			//die();
			if ( ! isset( $result['query'] ) ) {
				return $this->createResult(
					'error',
					wfMessage( 'flexform-api-error-noquery-response' )->text()
				);
			}

			$pages = $result['query']['allpages'];

			if ( is_null( $pages ) || $pages === false ) {
				return $this->createResult(
					'error',
					wfMessage( 'flexform-api-error-allpages' )->text()
				);
			}
			if ( isset( $pages['_element'] ) ) {
				unset( $pages['_element'] );
			}

			//print_r($pages);
			//die();
			$thisCnt = count( $pages );

			$cnt = $cnt + $thisCnt;

			if ( $cnt < 1 && $appContinue === false ) {
				return $range['start'] + 1;
			}

			if ( $thisCnt > 0 ) {
				foreach ( $pages as $page ) {
					$tempTitle = str_replace(
						ltrim(
							$nameStartsWith,
							':'
						),
						'',
						$page['title']
					);

					if ( is_numeric( $tempTitle ) ) {
						$number[] = $tempTitle;
					}
				}
			}
			if ( $appContinue === false ) {
				$continue = false;
			}
		}

		if ( count( $pages ) < 1 ) {
			return $range['start'] + 1;
		}
		$s = $range['start'];
		$e = $range['end'];

		for ( $t = $s; $t < $e; $t++ ) {
			if ( ! in_array(
				$t,
				$number
			) ) {
				return $this->createResult(
					'ok',
					$t
				);
			}
		}

		// TODO:  Still need a procedure what to do if range is full
		return false;
	}


	private function splitNamespace(string $title): array {
		if ( strpos(
			$title,
			':'
		) !== false ) {
                        $split = explode(
                                ':',
                                $title
                        );

                        $title = $split[1];
                        $nameSpace      = $split[0];
                        if ( empty( $nameSpace ) ) {
                                $id = 0;
                        } else {
                                $id = $this->getIdForNameSpace( $nameSpace );
                        }
                } else {
                        $id = 0;
		}
		return [$id, $title];
	}

	/**
	 * Get a list of pages that start with a certain name and take multiple results into account
	 *
	 * @param $nameStartsWith string Start title of a page
	 * @param $appContinue string returned from getApiContinue()
	 * @param bool $range void
	 *
	 * @return mixed API results
	 */
	private function getDataForWikiList( $nameStartsWith, $appContinue, $range = false ) {
		[$id, $nameStartsWith] = $this->splitNamespace($nameStartsWith);

		if ( $id === false ) {
			return false;
		}
		if ( ! $range ) { // next available
			if ( $appContinue === false ) {
				$postdata = [
					"action"      => "query",
					"format"      => "json",
					"list"        => "allpages",
					"aplimit"     => 5,
					"apprefix"    => $nameStartsWith,
					"apnamespace" => $id
				];
			} else {
				$postdata = [
					"action"      => "query",
					"format"      => "json",
					"aplimit"     => 5,
					"apcontinue"  => $appContinue,
					"list"        => "allpages",
					"apprefix"    => $nameStartsWith,
					"apnamespace" => $id
				];
			}
		} else {
			$trimmedStart = rtrim(
				$range['start'],
				"0"
			);
			$trimmedStart = $range['start'];
			if ( $appContinue === false ) {
				$postdata = [
					"action"      => "query",
					"format"      => "json",
					"aplimit"     => "max",
					"list"        => "allpages",
					"apnamespace" => $id,
					"apprefix"    => $nameStartsWith
				];
			} else {
				$postdata = [
					"action"      => "query",
					"format"      => "json",
					"list"        => "allpages",
					"aplimit"     => "max",
					"apcontinue"  => $appContinue,
					"apnamespace" => $id,
					"apprefix"    => $nameStartsWith
				];
			}
		}
		//echo "<pre>";
		//print_r($postdata);
		$result = $this->makeRequest(
			$postdata,
			true
		);

		//var_dump($result);
		//die();
		return $result;
	}

	private function makeRequest( $data, $useGet = false ) {
		$api = new ApiMain(
			new DerivativeRequest(
				$this->getRequest(),
				// Fallback upon $wgRequest if you can't access context
				$data,
				$useGet // treat this as a POST
			),
			false // not write.
		);
		$api->execute();
		$data = $api->getResult()->getResultData();

		return $data;
	}

	/**
	 * @return array
	 */
	protected function getExamplesMessages() : array {
		return array(
			'action=flexform&what=getRange&titleStartsWith=Invoice/&range=0000-9999' => 'apihelp-flexform-example-1',
			'action=flexform&what=nextAvailable&&titleStartsWith=Invoice/'           => 'apihelp-flexform-example-2'
		);
	}

}