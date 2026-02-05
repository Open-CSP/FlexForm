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
	private ?string $SMWAskquery;

	/**
	 * @var ?string
	 */
	private ?string $queryFromUser;

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
	 * @var bool
	 */
	private bool $filterQuery = false;

	/**
	 * @return bool
	 * @throws ApiUsageException
	 * @throws Exception
	 */
	public function execute() {
		$this->checkUserRightsAny( [ 'read' ] );
		$params = $this->extractRequestParams();
		$queryEncoded = $params['query'];
		$this->SMWAskquery 	  = base64_decode( str_replace( ' ', '+', $queryEncoded ) );
		$this->queryFromUser  = $params['q'];
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
			"query"  => $this->SMWAskquery
		];
		$mRequest = new Render();
		$results = $this->handleResults( $mRequest->makeRequest( $postdata ), [ 'results' => [] ] );
		$this->getResult()->addValue(
			null,
			'results',
			$results['results']
		);
		return true;
	}

	/**
	 * @return string
	 */
	private function handleFQuery(): string {
		$fQuery = '';
		if ( str_contains( $this->SMWAskquery, '(fquery=' ) ) {
			$fQuery = Core::get_string_between( $this->SMWAskquery, '(fquery=', ')' );
			$fQueryOld = $fQuery;
			if ( strpos( $fQuery, '__^^__' ) !== false ) {
				if ( !empty( $this->ffform ) ) {
					$fQuery = str_replace( '__^^__', base64_decode( $this->ffform ), $fQuery );
					$this->filterQuery = true;
				}
			}
			$this->SMWAskquery = str_replace(
				'(fquery=' . $fQueryOld . ')',
				'',
				$this->SMWAskquery
			);
		}
		return $fQuery;
	}

	/**
	 * @return void
	 */
	private function handleReturnText(): void {
		if ( str_contains( $this->SMWAskquery, '(returntext=' ) ) {
			$returnText = Core::get_string_between( $this->SMWAskquery, '(returntext=', ')' );
			$this->SMWAskquery = str_replace(
				'(returntext=' . $returnText . ')',
				'',
				$this->SMWAskquery
			);
		}
	}

	/**
	 * @return void
	 */
	private function handleTemplate(): void {
		if ( str_contains( $this->SMWAskquery, '(template=' ) ) {
			$template = Core::get_string_between( $this->SMWAskquery, '(template=', ')' );
			$this->SMWAskquery = str_replace(
				'(template=' . $template . ')',
				'',
				$this->SMWAskquery
			);
		}
	}

	/**
	 * @return void
	 */
	private function handleReturnId(): void {
		if ( str_contains( $this->SMWAskquery, '(returnid=' ) ) {
			$returnId = Core::get_string_between( $this->SMWAskquery, '(returnid=', ')' );
			$this->SMWAskquery = str_replace(
				'(returnid=' . $returnId . ')',
				'',
				$this->SMWAskquery
			);
		}
	}

	/**
	 * @return void
	 */
	private function handleLimit(): void {
		if ( str_contains( $this->SMWAskquery, '(limit=' ) ) {
			$limit = Core::get_string_between( $this->SMWAskquery, '(limit=', ')' );
			$this->SMWAskquery = str_replace(
				'(limit=' . $limit . ')',
				'',
				$this->SMWAskquery
			);
		}
	}

	/**
	 * @return void
	 */
	private function setupQuery(): void {
		$filterQuery = false;
		$fQuery = '';
		if ( str_contains( $this->SMWAskquery, '(' ) && str_contains( $this->SMWAskquery, ')' ) ) {
			$fQuery = $this->handleFQuery();
			$this->handleReturnText();
			$this->handleTemplate();
			$this->handleReturnId();
			$this->handleLimit();
		}
		if ( $this->filterQuery ) {
			$this->SMWAskquery .= $fQuery;
		}
	}

	/**
	 * @return void
	 */
	private function handleQType(): void {
		if ( $this->queryFromUser !== null || !empty( $this->queryFromUser ) ) {
			// Are there spaces in the query?
			if ( str_contains( $this->queryFromUser, ' ' ) ) {
				$mainQuery = $this->getMainQuery( $this->SMWAskquery );
				$explodedQuery = explode( ' ', $this->queryFromUser );
				$newQuery = '';

				foreach ( $explodedQuery as $seperated ) {
					if ( !empty( $seperated ) ) {
						$newQuery .= '[[' . $mainQuery . '::' . $this->createNewQuery( $seperated ) . ']]';
					}
				}
				$this->SMWAskquery = str_replace(
					'[[' . $mainQuery . '::!!!]]',
					$newQuery,
					$this->SMWAskquery
				);
			} else {
				$this->SMWAskquery = str_replace(
					'!!!',
					$this->createNewQuery( $this->queryFromUser ),
					$this->SMWAskquery
				);
			}
		} else {
			$this->SMWAskquery = str_replace(
				'!!!',
				'',
				$this->SMWAskquery
			);
		}
		if ( $this->returnId !== null ) {
			$this->SMWAskquery .= '|?' . $this->returnId;
		}
		if ( $this->returnText !== null ) {
			$this->SMWAskquery .= '|?' . $this->returnText;
		}
		if ( $this->limit !== null ) {
			$this->SMWAskquery .= '|limit=' . $this->limit;
		} else {
			$this->SMWAskquery .= '|limit=50';
		}
		if ( $this->template !== null ) {
			$this->SMWAskquery .= '|template=' . $this->template;
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
	}

	public function isWriteMode() {
		return false;
	}

	/**
	 * @return array
	 */
	public function getAllowedParams(): array {
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
		return [
			'action=FlexFormAsk&query=base64query&q=search' => 'apihelp-FlexFormAsk-example-1'
		];
	}

}