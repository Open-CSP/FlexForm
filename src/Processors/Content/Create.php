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
		if( Config::isDebug() ) {
			Debug::addToDebug( 'Write page activated ' . time(), $fields );
		}

		$this->content = ContentCore::createContent();

		if ( strpos(
				 $fields['writepage'],
				 '['
			 ) !== false ) {
			$fields['writepage'] = ContentCore::parseTitle( $fields['writepage'] );
		}

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
			$this->pageData['template'] = $exploded[0];
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
			$this->pageData['title'] = $exploded[1];
		} else {
			$this->pageData['title'] = false;
		}

		if ( isset( $exploded[2] ) && $exploded[2] !== '' ) {
			$this->pageData['option'] = $exploded[2];
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
		} else {
			$this->pageData['formFields'] = false;
		}

		if ( isset( $exploded[4] ) && $exploded[4] !== '' ) {
			$this->pageData['slot'] = $exploded[4];
		} else {
			$this->pageData['slot'] = false;
		}

		if ( isset( $exploded[5] ) && $exploded[5] !== '' ) {
			$this->pageData['id'] = $exploded[5];
		} else {
			$this->pageData['id'] = false;
		}

		if ( isset( $exploded[6] ) && $exploded[6] === 'true' ) {
			$this->pageData['leadByZero'] = true;
		} else {
			$this->pageData['leadByZero'] = false;
		}

		if ( isset( $exploded[7] ) && $exploded[7] === 'true' ) {
			$this->pageData['overwrite'] = true;
		} else {
			$this->pageData['overwrite'] = false;
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
		if( Config::isDebug() ) {
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
					'Content after adding form fields ' . $pageCount,
					$this->content
				);
			}
			if ( strpos(
					 $this->pageData['title'],
					 '['
				 ) !== false ) {
				$this->pageData['title'] = ContentCore::parseTitle( $this->pageData['title'] );
			}
			if ( $this->pageData['option'] == 'next_available' && $this->pageData['title'] !== false ) {

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
			if ( $this->pageData['title'] === false ) {
				throw new FlexFormException( wfMessage( 'flexform-mwcreate-wrong-title2' )->text() );
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
				if ( $rangeResult === '' ) $rangeResult = "0";

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
							[ 	'rangeCheck' => $rangeCheck,
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
			$pagesToSave[] = [
				$this->pageData['title'],
				$this->content,
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
		if( Config::isDebug() ) {
			Debug::addToDebug( 'Pages to save before addCreatToTile ' . time(), $pagesToSave );
		}
		$pagesToSave = $this->addCreateToTitle( $pagesToSave, $pageTitleToLinkTo );
		if( Config::isDebug() ) {
			Debug::addToDebug( 'Pages to save after addCreatToTile ' . time(), $pagesToSave );
		}
		$finalPages  = $this->createFinalPages( $pagesToSave );
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
			if ( is_array( $v ) ) {
				$this->content .= "|" . General::makeSpaceFromUnderscore( $k ) . "=";
				foreach ( $v as $multiple ) {
					$this->content .= wsSecurity::cleanBraces( $multiple ) . ',';
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
						$this->content .= '|' . General::makeSpaceFromUnderscore( $k ) . '=' . wsSecurity::cleanBraces(
								$v
							) . PHP_EOL;
					} else {
						$this->content = $v;
					}
				}
			}
		}
		if ( !$this->pageData['notemplate'] ) {
			$this->content .= "}}";
		}
	}

}
