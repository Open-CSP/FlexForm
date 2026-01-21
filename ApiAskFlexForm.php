<?php

use FlexForm\Core\Core;
use FlexForm\Processors\Content\Render;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : ApiAskFlexForm.php
 * Description :
 * Date        : 09/10/2020
 * Time        : 20:14
 */
class ApiAskFlexForm extends ApiBase {

	/**
	 * @var ?string
	 */
	private ?string $query;

	/**
	 * @var ?string
	 */
	private ?string $q;

	/**
	 * @var ?string
	 */
	private ?string $returnId;

	/**
	 * @var ?string
	 */
	private ?string $returnText;

	/**
	 * @var ?string
	 */
	private ?string $template;

	/**
	 * @var ?string
	 */
	private ?string $limit;

	/**
	 * @var ?string
	 */
	private ?string $ffform;

	/**
	 * @return bool
	 * @throws ApiUsageException
	 * @throws Exception
	 */
	public function execute() {
		$this->checkUserRightsAny( [ 'read' ] );
		$params = $this->extractRequestParams();
		$ret            = [];
		$ret['results'] = [];
		$queryEncoded = $params['query'];
		$this->query = base64_decode( str_replace( ' ', '+', $queryEncoded ) );
		$this->q              = $params['q'];
		$this->returnId       = $params['returnid'];
		$this->returnText     = $params['returntext'];
		$this->template       = $params['template'];
		$this->limit          = $params['limit'];
		$this->ffform         = $params['ffform'];

		$this->setupQuery();
		$this->handleQType();
		$postdata = [
			"action" => "ask",
			"format" => "json",
			"query"  => $this->query
		];
		$mRequest = new Render();
		$results = $this->handleResults( $mRequest->makeRequest( $postdata ), $ret );
		$this->getResult()->addValue(
			null,
			'results',
			$results['results']
		);
		return true;
	}

	/**
	 * @return void
	 */
	private function setupQuery(): void {
		$filterQuery = false;
		if ( str_contains( $this->query, '(' ) && str_contains( $this->query, ')' ) ) {
			if ( str_contains( $this->query, '(fquery=' ) ) {
				$fQuery = Core::get_string_between( $this->query, '(fquery=', ')' );
				$fQueryOld = $fQuery;
				if ( strpos( $fQuery, '__^^__' ) !== false ) {
					if ( empty( $this->ffform ) ) {
						$fQuery = '';
					} else {
						$fQuery = str_replace( '__^^__', base64_decode( $this->ffform ), $fQuery );
						$filterQuery = true;
					}
				}
				$this->query = str_replace(
					'(fquery=' . $fQueryOld . ')',
					'',
					$this->query
				);
			}
			if ( str_contains( $this->query, '(returntext=' ) ) {
				$returnText = Core::get_string_between( $this->query, '(returntext=', ')' );
				$this->query = str_replace(
					'(returntext=' . $returnText . ')',
					'',
					$this->query
				);
			}
			if ( str_contains( $this->query, '(template=' ) ) {
				$template = Core::get_string_between( $this->query, '(template=', ')' );
				$this->query = str_replace(
					'(template=' . $template . ')',
					'',
					$this->query
				);
			}
			if ( str_contains( $this->query, '(returnid=' ) ) {
				$returnId = Core::get_string_between( $this->query, '(returnid=', ')' );
				$this->query = str_replace(
					'(returnid=' . $returnId . ')',
					'',
					$this->query
				);
			}
			if ( str_contains( $this->query, '(limit=' ) ) {
				$limit = Core::get_string_between( $this->query, '(limit=', ')' );
				$this->query = str_replace(
					'(limit=' . $limit . ')',
					'',
					$this->query
				);
			}
		}
		if ( $filterQuery ) {
			$this->query .= $fQuery;
		}
	}

	/**
	 * @return void
	 */
	private function handleQType(): void {
		if ( $this->q !== null || !empty( $this->q ) ) {
			// Are there spaces in the query?
			if ( str_contains( $this->q, ' ' ) ) {
				$mainQuery = $this->getMainQuery( $this->query );
				$explodedQuery = explode( ' ', $this->q );
				$newQuery = '';

				foreach ( $explodedQuery as $seperated ) {
					if ( !empty( $seperated ) ) {
						$newQuery .= '[[' . $mainQuery . '::' . $this->createNewQuery( $seperated ) . ']]';
					}
				}
				$this->query = str_replace(
					'[[' . $mainQuery . '::!!!]]',
					$newQuery,
					$this->query
				);
			} else {
				$this->query = str_replace(
					'!!!',
					$this->createNewQuery( $this->q ),
					$this->query
				);
			}
		} else {
			$this->query = str_replace(
				'!!!',
				'',
				$this->query
			);
		}
		if ( $this->returnId !== null ) {
			$this->query .= '|?' . $this->returnId;
		}
		if ( $this->returnText !== null ) {
			$this->query .= '|?' . $this->returnText;
		}
		if ( $this->limit !== null ) {
			$this->query .= '|limit=' . $this->limit;
		} else {
			$this->query .= '|limit=50';
		}
		if ( $this->template !== null ) {
			$this->query .= '|template=' . $this->template;
		}
	}

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
	 * @param array $data
	 * @param array $ret
	 *
	 * @return array
	 */
	private function handleResults( array $data, array $ret ): array {
		if ( !empty( $data['query']['results'] ) ) {
			$data = $data['query']['results'];

			$t = 0;
			foreach ( $data as $k => $val ) {
				if ( $this->returnText === null ) {
					$ret['results'][$t]['text'] = $val['displaytitle'];
				} elseif ( isset( $val['printouts'][$this->returnText][0] ) ) {
					$ret['results'][$t]['text'] = $val['printouts'][$this->returnText][0];
				} else {
					$ret['results'][$t]['text'] = 'Not found';
				}

				if ( $this->returnId === null ) {
					$ret['results'][$t]['id'] = $k;
				} elseif ( isset( $val['printouts'][$this->returnId][0] ) ) {
					$ret['results'][$t]['id'] = $val['printouts'][$this->returnId][0];
				} else {
					$ret['results'][$t]['id'] = 'Not found';
				}
				$t++;
			}
		}
		return $ret;
	}

	public function needsToken() {
		return false;
		//return "csrf";
	}

	public function isWriteMode() {
		return false;
	}

	/**
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'query'            => [
				ParamValidator::PARAM_TYPE     => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'q' => [
				ParamValidator::PARAM_TYPE     => 'string',
			],
			'returnid' => [
				ParamValidator::PARAM_TYPE     => 'string',
			],
			'returntext' => [
				ParamValidator::PARAM_TYPE     => 'string',
			],
			'template' => [
				ParamValidator::PARAM_TYPE     => 'string',
			],
			'ffform' => [
				ParamValidator::PARAM_TYPE     => 'string',
			],
			'limit' => [
				ParamValidator::PARAM_TYPE     => 'string',
			]
		];
	}

	/**
	 * @return array
	 */
	protected function getExamplesMessages() : array {
		return array(
			'action=FlexFormAsk&query=base64query&q=search' => 'apihelp-flexform-bot-example-1'
		);
	}

}