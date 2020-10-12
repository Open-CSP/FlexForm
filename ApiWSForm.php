<?php
/**
 * Created by  : Designburo.nl
 * Project     : wsformWikiBaseNL
 * Filename    : ApiWSForm.php
 * Description :
 * Date        : 09/10/2020
 * Time        : 20:14
 */

class ApiWSForm extends ApiBase {

	private function returnFailure( $failure ) {
		$ret = array();
		$ret['message'] = $failure;
		$this->getResult()->addValue(null, $this->getModuleName(),
			array('error' => $ret)
		);
	}

	private function createResult( $code, $result ){
		$ret = array();
		$ret['status'] = $code;
		$ret['data'] = $result;
		return $ret;
	}

	public function execute(){
		$params = $this->extractRequestParams();
		$action = $params['what'];
		if (!$action || $action === null) {
			$this->dieUsageMsg('missingparam');
		}
		switch( $action ) {
			case "nextAvailable" :
				$title = $params['titleStartsWith'];
				$result = $this->getNextAvailable( $title );
				if( $result['status'] === "error" ) {
					$this->returnFailure($result['data']);
					break;
				}
				$output = $result['data'];
				//echo "--$result--"; die();
				//$this->getResult()->addValue(null, array('result' => "ok:" . $output) );
				break;
			case "getRange" :
				$title = $params['titleStartsWith'];
				$range = $params['range'];
				if (!$range || $range === null) {
					$this->returnFailure( wfMessage('wsform-api-error-parameter-range-missing')->text() );
					break;
				}
				$range = explode('-', $range);

				if( !ctype_digit( $range[0] ) || !ctype_digit( $range[1] ) ) {
					$this->returnFailure( wfMessage('wsform-api-error-bad-range')->text() );
					break;
				}
				$startRange = (int)$range[0];
				$endRange = (int)$range[1];

				$result = $this->getFromRange( $title,  array('start' => $startRange, 'end' => $endRange) );
				if( $result['status'] === "error" ) {
					$this->returnFailure($result['data']);
					break;
				}
				$output = $result['data'];
				break;
			default :
				$this->returnFailure( wfMessage('wsform-api-error-unknown-what-parameter')->text() );
				break;
		}

		$this->getResult()->addValue(null, $this->getModuleName(),
			array('result' => $output)
		);

		return true;
	}

	public function needsToken()
	{
		return false;
	}

	public function isWriteMode()
	{
		return false;
	}

	/**
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'what' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'titleStartsWith' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'range' => array(
				ApiBase::PARAM_TYPE => 'string'
			)
		);
	}

	private function getNextAvailable( $nameStartsWith ) {
		$number = array();
		$continue = true;
		$appContinue = false;
		$cnt = 0;
		while( $continue ) {
			$result = $this->getDataForWikiList( $nameStartsWith, $appContinue, false );
			$appContinue = $this->getApiContinue( $result );

			if( !isset( $result['query'] ) ) {
				return $this->createResult('error', wfMessage('wsform-api-error-noquery-response')->text() );
			}

			$pages = $result['query']['allpages'];
			if( is_null( $pages ) || $pages === false ) {
				return $this->createResult('error', wfMessage('wsform-api-error-allpages')->text() );
			}
			if( isset( $pages['_element'] ) ) unset( $pages['_element'] );

			$thisCnt = count( $pages );

			$cnt = $cnt + $thisCnt;

			if ( $cnt < 1 && $appContinue === false ) {
				return $this->createResult('ok', "1");;
			}

			if($thisCnt > 0) {
				foreach ($pages as $page) {
					$tempTitle = str_replace( $nameStartsWith, '', $page['title'] );
					if( is_numeric( $tempTitle ) ) {
						$number[] = $tempTitle;
					}
				}
			}
			if( $appContinue === false ) {
				$continue = false;
			}

		}
		rsort($number);
		$nr = intval($number[0]);
		return  $this->createResult('ok', $nr+1);

	}

	/**
	 * Get the ID of the given namespace name
	 *
	 * @param string $ns
	 * @return bool|mixed Either the ID of the namespace or false when not found
	 */
	private function getIdForNameSpace( $ns ) {
		$ns = strtolower( $ns );
		$id = false;
		$postdata =[
			"action" => "query",
			"format" => "json",
			"meta" => "siteinfo",
			"siprop" => "namespaces"
		];
		$lst = $this->makeRequest($postdata, true);
		if( isset( $lst['query']['namespaces'] ) ) {
			$lst = $lst['query']['namespaces'];
		} else return false;
		foreach( $lst as $nameSpace ) {
			if( isset( $nameSpace['canonical'] ) ) {
				$can   = strtolower( $nameSpace['canonical'] );
				$alias = strtolower( $nameSpace['*'] );
				if ( $can === $ns || $alias === $ns ) {
					$id = $nameSpace['id'];
					break;
				}
			}
		}
		return $id;
		//echo "ns id is :" . $id;

	}

	/**
	 * If there are more results from the API, get the next results
	 *
	 * @param $result array of previous API results
	 * @return bool when no further results
	 * @return string where to start next API call
	 */
	function getApiContinue($result) {
		if( isset( $result['continue']['apcontinue'] ) ) {
			$appContinue = $result['continue']['apcontinue'];
		} else $appContinue = false;
		return $appContinue;
	}

	function getFromRange( $nameStartsWith, $range = false ) {
		$number = array();
		$continue = true;
		$appContinue = false;
		$cnt = 0;
		while( $continue ) {
			$result = $this->getDataForWikiList( $nameStartsWith, $appContinue, $range );
			//print_r($result);
			//die();
			$appContinue = $this->getApiContinue( $result );

			//var_dump( $appContinue );
			//die();
			if( !isset( $result['query'] ) ) {
				return $this->createResult('error', wfMessage('wsform-api-error-noquery-response')->text());
			}

			$pages = $result['query']['allpages'];

			if( is_null( $pages ) || $pages === false ) {
				return $this->createResult('error', wfMessage('wsform-api-error-allpages')->text() );
			}
			if( isset( $pages['_element'] ) ) unset( $pages['_element'] );

			//print_r($pages);
			//die();
			$thisCnt = count( $pages );

			$cnt = $cnt + $thisCnt;

			if ( $cnt < 1 && $appContinue === false ) {
				return $range['start'] + 1;
			}

			if( $thisCnt > 0 ) {
				foreach ($pages as $page) {

					$tempTitle = str_replace( $nameStartsWith, '', $page['title'] );

					if( is_numeric( $tempTitle ) ) {
						$number[] = $tempTitle;
					}
				}
			}
			if( $appContinue === false ) {
				$continue = false;
			}

		}

		if (count($pages) < 1) {
			return $range['start'] + 1;
		}
		$s = $range['start'];
		$e = $range['end'];

		for( $t=$s; $t < $e; $t++ ) {
			if(!in_array($t, $number)) {
				return $this->createResult('ok', $t);
			}
		}
		// TODO:  Still need a procedure what to do if range is full
		return false;
	}


	/**
	 * Get a list of pages that start with a certain name and take multiple results into account
	 *
	 * @param $nameStartsWith string Start title of a page
	 * @param $appContinue string returned from getApiContinue()
	 * @param bool $range void
	 * @return mixed API results
	 */
	private function getDataForWikiList( $nameStartsWith, $appContinue, $range = false ) {
		if( strpos( $nameStartsWith, ':' ) !== false ) {
			$split = explode(':', $nameStartsWith);
			$nameStartsWith = $split[1];
			$nameSpace = $split[0];
			$id = $this->getIdForNameSpace( $nameSpace );
		} else $id = 0;

		if( $id === false ) {
			return false;
		}
		if (!$range) { // next available
			if ($appContinue === false) {

				$postdata = [
					"action" => "query",
					"format" => "json",
					"list" => "allpages",
					"aplimit" => 5,
					"apprefix" => $nameStartsWith,
					"apnamespace" => $id
				];
			} else {
				$postdata = [
					"action" => "query",
					"format" => "json",
					"aplimit" => 5,
					"apcontinue" => $appContinue,
					"list" => "allpages",
					"apprefix" => $nameStartsWith,
					"apnamespace" => $id
				];
			}
		} else {
			$trimmedStart = rtrim($range['start'], "0");
			$trimmedStart = $range['start'];
			if( $appContinue === false ) {
				$postdata = [
					"action" => "query",
					"format" => "json",
					"aplimit" => "max",
					"list" => "allpages",
					"apnamespace" => $id,
					"apprefix" => $nameStartsWith
				];
			} else {
				$postdata = [
					"action" => "query",
					"format" => "json",
					"list" => "allpages",
					"aplimit" => "max",
					"apcontinue" => $appContinue,
					"apnamespace" => $id,
					"apprefix" => $nameStartsWith
				];
			}
		}
		//echo "<pre>";
		//print_r($postdata);
		$result = $this->makeRequest($postdata, true);
		//var_dump($result);
		//die();
		return $result;
	}

	private function makeRequest( $data, $useGet = false ) {
		$api = new ApiMain(
			new DerivativeRequest(
				$this->getRequest(), // Fallback upon $wgRequest if you can't access context
				$data,
				/*
				array(
					'action' => 'ask',
					'query' => $query
				),
				*/
				$useGet // treat this as a POST
			),
			false // not write.
		);
		$api->execute();
		$data = $api->getResult()->getResultData();
		return $data;
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wsform&what=getRange&titleStartsWith=Invoice/&range=0000-9999' => 'apihelp-wsform-example-1',
			'action=wsform&what=nextAvailable&&titleStartsWith=Invoice/' => 'apihelp-wsform-example-2'
		);
	}

}