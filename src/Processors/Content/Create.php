<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : FlexForm
 * Filename    : create.class.php
 * Description :
 * Date        : 19-3-2021
 * Time        : 21:22
 */

namespace FlexForm\Processors\Content;

use FlexForm\Core\Config;
use FlexForm\Core\Core;
use FlexForm\Core\Debug;
use FlexForm\Processors\Definitions;
use FlexForm\Processors\Security\wsSecurity;
use FlexForm\Processors\Utilities\General;
use FlexForm\FlexFormException;

class Create {

	private $content;
	private $JSONContent;
	private $title;
	private $pagesToSave;
	private $pageData;

	/**
	 * @return array
	 * @throws FlexFormException
	 * @throws \MWException
	 */
	public function writePage(): array {
		$fields = ContentCore::getFields();
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Write page activated ' . time(),
							   [ "fields" => $fields,
								"_post" => $_POST ] );
		}

		$this->content = ContentCore::createContent( true );

		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Write page activated CONTENT ' . time(),
							   $this->content );
		}
		if ( strpos(
				 $fields['writepage'],
				 '['
			 ) !== false ) {
			$fields['writepage'] = ContentCore::parseTitle( $fields['writepage'] );
		}

		$fields['writepage'] = ContentCore::checkCapitalTitle( $fields['writepage'] );

		$this->title = $fields['writepage'];


		if ( strtolower( $fields['option'] ) == 'next_available' ) {
			// get highest number
			$hnr = ContentCore::getNextAvailable( $this->title );
			if ( $hnr['status'] !== 'error' ) {
				$this->title = $fields['writepage'] . $hnr['result'];
			} else {
				throw new FlexFormException( $hnr['message'] );
				// return wbHandleResponses::createMsg( $hnr['message'], 'error', $returnto);
			}
			// $title = $writepage . $api->getNextAvailable( $title );
			//die( $title );
			//$title = $writepage . $api->getWikiListNumber($title);
			if ( $this->title === false ) {
				throw new FlexFormException( wfMessage( 'flexform-mwcreate-wrong-title2' )->text() );
			}
		}
		if ( substr(
				 strtolower( $fields['option'] ),
				 0,
				 6
			 ) === 'range:' ) {
			$range      = substr(
				$fields['option'],
				6
			);
			$rangeCheck = explode(
				'-',
				$range
			);

			if ( !ctype_digit( $rangeCheck[0] ) || !ctype_digit( $rangeCheck[1] ) ) {
				throw new FlexFormException( wfMessage( 'flexform-mwoption-bad-range' ) );
			}

			// $startRange = (int)$range[0];
			//$endRange = (int)$range[1];

			//$tmp  = $api->getWikiListNumber($title, array('start' => $startRange, 'end' => $endRange) );
			$rangeResult = ContentCore::getFromRange(
				$this->title,
				$range
			);
			if ( $rangeResult['status'] === 'error' ) {
				// echo $tmp['message'];
				throw new FlexFormException( $rangeResult['message'] );
				// return wbHandleResponses::createMsg( $tmp['message'], 'error', $returnto);
			}
			$rangeResult = $rangeResult['result'];

			if ( $fields['leadByZero'] === true ) {
				$endrangeLength = strlen( $rangeCheck[1] );
				$rangeResult    = str_pad(
					$rangeResult,
					$endrangeLength,
					'0',
					STR_PAD_LEFT
				);
			}
			$this->title = $fields['writepage'] . $rangeResult;
		}

		if ( $fields['option'] == 'add_random' && $fields['writepage'] !== false ) {
			$this->title = $fields['writepage'] . ContentCore::createRandom();
		}

		if ( !$fields['writepage'] ) {
			throw new FlexFormException( wfMessage( 'flexform-mwcreate-wrong-title' )->text() );
			// return wbHandleResponses::createMsg( $i18n->wsMessage( 'wsform-mwcreate-wrong-title') );

		}

		// Return the result
		return [
			'title'   => $this->title,
			'content' => $this->content
		];
	}

	/**
	 * @param array $fields
	 *
	 * @return void
	 */
	private function setPageData( array $fields ) {
		$this->pageData['template'] = $fields['template'];
		if ( strtolower( $this->pageData['template'] ) === 'wsnone' ) {
			$this->pageData['notemplate'] = true;
		} else {
			$this->pageData['notemplate'] = false;
		}
		$this->pageData['title']      = $fields['writepage'];
		$this->pageData['option']     = $fields['writepage'];
		$this->pageData['slot']       = $fields['slot'];
		$this->pageData['formFields'] = false;
	}

	public function getFormFieldAliases( $fields ){
		$alias = [];
		foreach ( $fields as $k => $field ) {
			if ( strpos( $field, '::' ) !== false ) {
				// We have Aliases
				$exploded = explode( '::', $field );
				$originalName = $exploded[0];
				$templateName = $exploded[1];
				$alias['aliasFields'][$originalName] = $templateName;
			}
		}
	}

	private function setFormFieldAliases() {
		$this->pageData['aliasFields'] = [];
		foreach ( $this->pageData['formFields'] as $k => $field ) {
			if ( strpos( $field, '::' ) !== false ) {
				// We have Aliases
				$exploded = explode( '::', $field );
				$originalName = $exploded[0];
				$templateName = $exploded[1];
				$this->pageData['aliasFields'][$originalName] = $templateName;
				$this->pageData['formFields'][$k] = $originalName;
			}
		}
	}

	/**
	 * @param string $page
	 *
	 * @return void
	 */
	private function setPageDataMultiple( string $page ) {
		$this->pageData = [];
		$exploded = explode(
			Core::DIVIDER,
			$page
		);
		if ( isset( $exploded[0] ) && $exploded[0] !== '' ) {
			$this->pageData['template'] = trim( $exploded[0] );
			if ( strtolower( $exploded[0] ) === 'wsnone' ) {
				$this->pageData['notemplate'] = true;
			} else {
				$this->pageData['notemplate'] = false;
			}
		} else {
			$this->pageData['template']   = false;
			$this->pageData['notemplate'] = false;
		}

		if ( isset( $exploded[1] ) && $exploded[1] !== '' ) {
			$this->pageData['title'] = trim( $exploded[1] );
		} else {
			$this->pageData['title'] = false;
		}

		if ( isset( $exploded[2] ) && $exploded[2] !== '' ) {
			$this->pageData['option'] = trim( $exploded[2] );
		} else {
			$this->pageData['option'] = false;
		}

		if ( isset( $exploded[3] ) && $exploded[3] !== '' ) {
			$formFields                   = explode(
				',',
				$exploded[3]
			);
			$this->pageData['formFields'] = array_map(
				'trim',
				$formFields
			);
			$this->setFormFieldAliases();
		} else {
			$this->pageData['formFields'] = false;
			$this->pageData['aliasFields'] = [];
		}

		if ( isset( $exploded[4] ) && $exploded[4] !== '' ) {
			$this->pageData['slot'] = trim( $exploded[4] );
		} else {
			$this->pageData['slot'] = false;
		}

		if ( isset( $exploded[5] ) && $exploded[5] !== '' ) {
			$this->pageData['id'] = trim( $exploded[5] );
		} else {
			$this->pageData['id'] = false;
		}

		if ( isset( $exploded[6] ) && trim( $exploded[6] ) === 'true' ) {
			$this->pageData['leadByZero'] = true;
		} else {
			$this->pageData['leadByZero'] = false;
		}

		if ( isset( $exploded[7] ) && trim( $exploded[7] ) === 'true' ) {
			$this->pageData['overwrite'] = true;
		} else {
			$this->pageData['overwrite'] = false;
		}

		if ( isset( $exploded[8] ) && trim( $exploded[8] ) === 'true' ) {
			$this->pageData['noseo'] = true;
		} else {
			$this->pageData['noseo'] = false;
		}
		if ( isset( $exploded[9] ) && trim( $exploded[9] ) !== '' ) {
			$this->pageData['format'] = trim( $exploded[9] );
		} else {
			$this->pageData['format'] = 'wiki';
		}
	}

	/**
	 * @return array
	 * @throws FlexFormException
	 * @throws \MWException
	 */
	public function writePages() : array {
		$pageCount = 0;
		$fields    = ContentCore::getFields();
		$pageTitleToLinkTo = [];
		$json = [];
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Write several page activated ' . time(), $fields );
		}
		foreach ( $fields['writepages'] as $singlePage ) {
			$pageCount++;
			$this->content = '';
			$this->setPageDataMultiple( $singlePage );
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Working on page ' . $pageCount,
					[ 'pageinfo' => $singlePage,
						'after setpageDataMultiple' => $this->pageData ]
				);
			}
			// If we do not have a page title or a template, then skip!
			if ( $this->pageData['template'] === false || $this->pageData['title'] === false ) {
				continue;
			}
			if ( !$this->pageData['notemplate'] ) {
				$this->content = "{{" . $this->pageData['template'] . "\n";
			}
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Content original ' . $pageCount,
					$this->content
				);
			}
			$this->addPostFieldsToContent();
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'PageData after adding form fields ' . $pageCount,
					$this->pageData
				);
				if ( $this->pageData['format'] === 'wiki' ) {
					Debug::addToDebug(
						'Content after adding form fields ' . $pageCount,
						$this->content
					);
				} else {
					Debug::addToDebug(
						'JSONContent after adding form fields ' . $pageCount,
						$this->JSONContent
					);
				}
			}
			if ( strpos(
					 $this->pageData['title'],
					 '['
				 ) !== false ) {
				$this->pageData['title'] = ContentCore::parseTitle(
					$this->pageData['title'],
					$this->pageData['noseo']
				);
			}

			if ( $this->pageData['title'] === false ) {
				throw new FlexFormException( wfMessage( 'flexform-mwcreate-wrong-title2' )->text() );
			}

			$this->pageData['title'] = ContentCore::checkCapitalTitle( $this->pageData['title'] );
			if ( $this->pageData['option'] == 'next_available' ) {

				$hnr = ContentCore::getNextAvailable( $this->pageData['title'] );
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'next available',
						$hnr
					);
				}
				if ( $hnr['status'] !== 'error' ) {
					$this->pageData['title'] = $this->pageData['title'] . $hnr['result'];
				} else {
					throw new FlexFormException( $hnr['message'] );
					// return wbHandleResponses::createMsg( $hnr['message'], 'error', $returnto);
				}
			}


			if ( substr(
					 strtolower( $this->pageData['option'] ),
					 0,
					 6
				 ) === 'range:' ) {
				$range      = substr(
					$this->pageData['option'],
					6
				);
				$rangeCheck = explode(
					'-',
					$range
				);
				if ( !ctype_digit( $rangeCheck[0] ) || !ctype_digit( $rangeCheck[1] ) ) {
					throw new FlexFormException( wfMessage( 'flexform-mwoption-bad-range' ) );
				}

				$rangeResult = ContentCore::getFromRange(
					$this->pageData['title'],
					$range
				);
				if ( $rangeResult['status'] === 'error' ) {
					// echo $tmp['message'];
					throw new FlexFormException( $rangeResult['message'] );
					// return wbHandleResponses::createMsg( $tmp['message'], 'error', $returnto);
				}
				$rangeResult = $rangeResult['result'];
				if ( $rangeResult === '' ) {
					$rangeResult = "0";
				}

				if ( $this->pageData['leadByZero'] === true ) {
					$endrangeLength = strlen( $rangeCheck[1] );
					$rangeResult    = str_pad(
						$rangeResult,
						$endrangeLength,
						'0',
						STR_PAD_LEFT
					);
					if ( Config::isDebug() ) {
						Debug::addToDebug(
							'lead by zero active ' . time(),
							[ 'rangeCheck' => $rangeCheck,
							'endrangeLenth' => $endrangeLength,
							'rangeResult' => $rangeResult ]
						);
					}

				}
				$this->pageData['title'] = $this->pageData['title'] . $rangeResult;
			}

			if ( strtolower( $this->pageData['option'] ) === 'add_random' && $this->pageData['title'] !== false ) {
				$this->pageData['title'] = $this->pageData['title'] . ContentCore::createRandom();
			}

			ContentCore::checkFollowPage( $this->pageData['title'] );

			if ( false !== $this->pageData['id'] ) {
				$pageTitleToLinkTo[strtolower( $this->pageData['id'] )] = $this->pageData['title'];
			}
			if ( $this->pageData['format'] === 'wiki' ) {
				$saveContent = $this->content;
			} else {
				$saveContent = json_encode( $this->JSONContent, JSON_PRETTY_PRINT );
			}
			$pagesToSave[] = [
				$this->pageData['title'],
				$saveContent,
				$fields['summary'],
				$this->pageData['slot'],
				$this->pageData['overwrite']
			];
		}

		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'$pagesToSave',
				$pagesToSave
			);
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Pages to save before addCreatToTile ' . time(), $pagesToSave );
		}
		$pagesToSave = $this->addCreateToTitle( $pagesToSave, $pageTitleToLinkTo );
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Pages to save after addCreatToTitle ' . time(), $pagesToSave );
		}
		$finalPages = $this->createFinalPages( $pagesToSave );
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'$finalPages',
				$finalPages
			);
		}

		return $finalPages;
	}

	/**
	 * @param array $pagesToSave
	 *
	 * @return array
	 */
	private function createFinalPages( array $pagesToSave ) : array {
		$finalPages = [];
		foreach ( $pagesToSave as $k => $pageToSave ) {
			// print_r( $pageToSave );
			$title                = $pageToSave[0]; // $ret, $summary, $writePageSlot
			$summary              = $pageToSave[2];
			$slot                 = [ $pageToSave[3] => $pageToSave[1] ];
			$pArray               = [
				'slot'    => $slot,
				'summary' => $summary,
				'overwrite' => $pageToSave[4]
			];
			$finalPages[$title][] = $pArray;
		}

		return $finalPages;
	}

	/**
	 * @param array $pagesToSave
	 * @param array $pageTitleToLinkTo
	 *
	 * @return array
	 */
	private function addCreateToTitle( array $pagesToSave, array &$pageTitleToLinkTo ) : array {
		foreach ( $pagesToSave as $k => $pageToSave ) {
			// var_dump(substr( trim( $pageToSave[0] ), 0, 6 ) );
			if ( substr(
					 $pageToSave[0],
					 0,
					 6
				 ) === '--id--' || substr(
									   $pageToSave[0],
									   0,
									   6
								   ) === '::id::' ) {
				// We need to append a create to a title
				//echo "ok";
				$idTitle = strtolower(
					substr(
						$pageToSave[0],
						6
					)
				);
				// var_dump( $idTitle );
				if ( isset( $pageTitleToLinkTo[$idTitle] ) ) {
					$pagesToSave[$k][0] = $pageTitleToLinkTo[$idTitle];
				}
			}
			if ( $pageToSave[3] === false ) {
				$pagesToSave[$k][3] = 'main';
			}
		}

		return $pagesToSave;
	}

	private function addPostFieldsToContent() {
		$fields    = ContentCore::getFields();
		$format = $fields['format'];
		$json = [];
		$json['ffID'] = ContentCore::createRandom();
		foreach ( $_POST as $k => $v ) {
			if ( is_array( $this->pageData['formFields'] ) ) {
				if ( !in_array(
						General::makeSpaceFromUnderscore( $k ),
						$this->pageData['formFields']
					) && !in_array(
						$k,
						$this->pageData['formFields']
					) ) {
					continue;
				}
			}
			if ( is_array( $v ) && !Definitions::isFlexFormSystemField( $k ) ) {
				if ( array_key_exists(
					$k,
					$this->pageData['aliasFields']
				) ) {
					$kField = General::makeSpaceFromUnderscore( $this->pageData['aliasFields'][$k] );
					$this->content .= "|" . $kField . "=";
				} else {
					$kField = General::makeSpaceFromUnderscore( $k );
					$this->content .= "|" . $kField . "=";
				}
				if ( ContentCore::hasAssignedKeys( $v ) ) {
					$json[$kField]['ffID'] = ContentCore::createRandom();
				}
				foreach ( $v as $multiple ) {
					$this->content .= wsSecurity::cleanBraces( $multiple ) . ',';
					if ( contentcore::isInstance( $kField ) === true ) {
						$json[ $kField ][] = json_decode( $multiple, true );
					} else {
						$json[ $kField ][] = ContentCore::checkJsonValues( $multiple );
					}
				}
				$this->content = rtrim(
									 $this->content,
									 ','
								 ) . PHP_EOL;
			} else {
				if ( !Definitions::isFlexFormSystemField( $k ) && $v != "" ) {
					// if ( $k !== "mwtemplate" && $k !== "mwoption" && $k !== "mwwrite" &&
					// $k !== "mwreturn" && $k !== "mwedit" && $v != "" ) {
					if ( !$this->pageData['notemplate'] ) {
						if ( array_key_exists(
							$k,
							$this->pageData['aliasFields']
						) ) {
							$kField = General::makeSpaceFromUnderscore( $this->pageData['aliasFields'][$k] );
							$this->content .= '|' . $kField . '=' . wsSecurity::cleanBraces(
									$v
								) . PHP_EOL;
							if ( contentcore::isInstance( $kField ) === true ) {
								$json[$kField] = json_decode( $v, true );
							} else {
								$json[ $kField ] = ContentCore::checkJsonValues( $v );
							}
						} else {
							$kField = General::makeSpaceFromUnderscore(	$k );
							$vField = wsSecurity::cleanBraces( $v );
							$this->content .= '|' . $kField . '=' . $vField . PHP_EOL;
							if ( contentcore::isInstance( $kField ) === true ) {
								$json[$kField] = json_decode( $vField,	true );
							} else {
								$json[ $kField ] = ContentCore::checkJsonValues( $vField );
							}
						}
					} else {
						$this->content = $v;
					}
				}
			}
		}
		if ( !$this->pageData['notemplate'] ) {
			$this->content .= "}}";
			$templateToUse = $this->pageData['template'];
			$this->JSONContent[ $templateToUse ] = $json;
		} else {
			$this->JSONContent = $json;
		}
	}

}
