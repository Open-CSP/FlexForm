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
	private ?string $SMWAskQuery;

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
		$this->SMWAskQuery 	  = base64_decode( str_replace( ' ', '+', $queryEncoded ) );
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
			"query"  => $this->SMWAskQuery
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
		if ( str_contains( $this->SMWAskQuery, '(fquery=' ) ) {
			$fQuery = Core::get_string_between( $this->SMWAskQuery, '(fquery=', ')' );
			$fQueryOld = $fQuery;
			if ( strpos( $fQuery, '__^^__' ) !== false ) {
				if ( !empty( $this->ffform ) ) {
					$fQuery = str_replace( '__^^__', base64_decode( $this->ffform ), $fQuery );
					$this->filterQuery = true;
				}
			}
			$this->SMWAskQuery = str_replace(
				'(fquery=' . $fQueryOld . ')',
				'',
				$this->SMWAskQuery
			);
		}
		return $fQuery;
	}

	/**
	 * @param string $key
	 *
	 * @return string|null
	 */
	private function extractAndRemove( string $key ): ?string {
		$pattern = '/\(' . preg_quote( $key, '/' ) . '=(.*?)\)/';

		if ( preg_match( $pattern, $this->SMWAskQuery, $matches ) ) {
			$this->SMWAskQuery = preg_replace( $pattern, '', $this->SMWAskQuery, 1 );
			return $matches[1];
		}

		return null;
	}

	/**
	 * @return void
	 */
	private function setupQuery(): void {
		$this->filterQuery = false;
		$fQuery = '';
		if ( str_contains( $this->SMWAskQuery, '(' ) && str_contains( $this->SMWAskQuery, ')' ) ) {
			$fQuery = $this->handleFQuery();
			$this->returnText = $this->extractAndRemove( 'returntext' );
			$this->template = $this->extractAndRemove( 'template' );
			$this->returnId = $this->extractAndRemove( 'returnid' );
			$this->limit = $this->extractAndRemove( 'limit' );
		}
		if ( $this->filterQuery ) {
			$this->SMWAskQuery .= $fQuery;
		}
	}

	/**
	 * @return void
	 */
	private function handleQType(): void {
		if ( $this->queryFromUser !== null ) {
			// Are there spaces in the query?
			if ( str_contains( $this->queryFromUser, ' ' ) ) {
				$mainQuery = $this->getMainQuery( $this->SMWAskQuery );
				$explodedQuery = explode( ' ', $this->queryFromUser );
				$newQuery = '';

				foreach ( $explodedQuery as $seperated ) {
					if ( !empty( $seperated ) ) {
						$newQuery .= '[[' . $mainQuery . '::' . $this->createNewQuery( $seperated ) . ']]';
					}
				}
				$this->SMWAskQuery = str_replace(
					'[[' . $mainQuery . '::!!!]]',
					$newQuery,
					$this->SMWAskQuery
				);
			} else {
				$this->SMWAskQuery = str_replace(
					'!!!',
					$this->createNewQuery( $this->queryFromUser ),
					$this->SMWAskQuery
				);
			}
		} else {
			$this->SMWAskQuery = str_replace(
				'!!!',
				'',
				$this->SMWAskQuery
			);
		}
		if ( $this->returnId !== null ) {
			$this->SMWAskQuery .= '|?' . $this->returnId;
		}
		if ( $this->returnText !== null ) {
			$this->SMWAskQuery .= '|?' . $this->returnText;
		}
		if ( $this->limit !== null ) {
			$this->SMWAskQuery .= '|limit=' . $this->limit;
		} else {
			$this->SMWAskQuery .= '|limit=50';
		}
		if ( $this->template !== null ) {
			$this->SMWAskQuery .= '|template=' . $this->template;
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