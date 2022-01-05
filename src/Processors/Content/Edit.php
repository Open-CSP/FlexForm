<?php
/**
 * Created by  : Designburo.nl
 * Project     : wsformWikiBaseNL
 * Filename    : edit.class.php
 * Description :
 * Date        : 19-3-2021
 * Time        : 21:23
 */

namespace WSForm\Processors\Content;

use wsDebug;
use WSForm\Core\Config;
use WSForm\Processors\Utilities\General;

class Edit {

	private $editCount = 0;

	/**
	 * Function used by the Edit page functions
	 *
	 * @param $source
	 * @param $template
	 * @param mixed $find
	 * @param mixed $value
	 *
	 * @return bool|string
	 */
	public function getTemplate( $source, $template, $find = false, $value = false ) {
		$multiple = substr_count(
			$source,
			'{{' . $template
		);
		// template not found
		if ( $multiple == 0 ) {
			return false;
		}

		// 1 template found and no specific argument=value is needed
		if ( $multiple == 1 && $find === false ) {
			$startPos = $this->getStartPos(
				$source,
				'{{' . $template
			);
			$endPos   = $this->getEndPos(
				$startPos,
				$source
			);
			if ( $startPos !== false && $endPos !== false ) {
				return substr(
					$source,
					$startPos,
					( $endPos - $startPos - 1 )
				);
			} else {
				return false;
			}
		}

		// 1 template found, but we need to check for argument=value
		if ( $multiple == 1 && $find !== false && $value !== false ) {
			$startPos = $this->getStartPos(
				$source,
				'{{' . $template
			);
			$endPos   = $this->getEndPos(
				$startPos,
				$source
			);
			if ( $startPos !== false && $endPos !== false ) {
				if ( $this->checkTemplateValue(
						$source,
						$startPos,
						$endPos,
						$find,
						$value
					) !== false ) {
					return substr(
						$source,
						$startPos,
						( $endPos - $startPos - 1 )
					);
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		// We have multiple templates on the page, but no identifier
		if ( $multiple > 1 && $find === false ) {
			return false;
		}

		// We have multiple templates on the page and we have an identifier
		if ( $multiple > 1 && $find !== false && $value !== false ) {
			$offset = 0;
			for ( $t = 0; $t < $multiple; $t++ ) {
				$startPos = $this->getStartPos(
					$source,
					'{{' . $template,
					$offset
				);
				$endPos   = $this->getEndPos(
					$startPos,
					$source
				);
				if ( $startPos !== false && $endPos !== false ) {
					if ( $this->checkTemplateValue(
							$source,
							$startPos,
							$endPos,
							$find,
							$value
						) !== false ) {
						return substr(
							$source,
							$startPos,
							( $endPos - $startPos - 1 )
						);
					} else {
						$offset = $endPos;
					}
				} else {
					return false;
				}
			}
		}

		return false;
	}

	private function getEndPos( $start, $txt ) {
		$pos = false;
		$brackets = 2;
		for ( $i = $start; $i < strlen( $txt ) + 1; $i++ ) {
			if ( $txt[$i] == '{' ) {
				$brackets++;
			}
			if ( $txt[$i] == '}' ) {
				$brackets--;
			}
			if ( $brackets == 0 ) {
				$pos = $i;
				break;
			}
		}

		return $pos;
	}

	private function getTemplateValueAndDelete( $name, $template ) {
		//echo "searching for $name";
		$regex = '#%ws_' . $name . '=(.*?)%#';
		preg_match(
			$regex,
			$template,
			$tmp
		);
		//echo "<pre>";
		//print_r($tmp);
		//echo "</pre>";
		if ( isset( $tmp[1] ) ) {
			$tmp = $tmp[1];
		} else {
			$ret['val'] = false;
			$ret['tpl'] = $template;
		}
		//$tmp = $this->get_string_between( $template, '%ws_' . $name . '=' , '%' );
		//echo "<p>found : $tmp</p>";
		$ret = array();
		if ( $tmp !== "" ) {
			$ret['val'] = $tmp;
			$ret['tpl'] = str_replace(
				'%ws_' . $name . '=' . $tmp . '%',
				'',
				$template
			);
		} else {
			$ret['val'] = false;
		}

		return $ret;
	}

	private function getStartPos( $string, $start, $offset = 0 ) {
		$ini = strpos(
			$string,
			$start,
			$offset
		);
		if ( $ini === false ) {
			return false;
		}
		$ini += strlen( $start );

		return $ini;
	}

	public function clearWhiteSpacePlusEOLs( $txt ) {
		return str_replace(
			array(
				"\n",
				"\r",
				" "
			),
			'',
			$txt
		);
	}

	private function checkTemplateValue( $source, $start, $end, $find, $value ) {
		if ( substr_count(
			$source,
			$find . '=' . $value,
			$start,
			( $end - $start - 1 )
		) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return array
	 */
	private function createEditData(): array{
		//edit = [0]pid [1]template [2]Form field [3]Use field [4]Value [5]Slot
		$data = array();
		$t=0;
		$fields = ContentCore::getFields();
		foreach ( $fields['mwedit'] as $edits ) {
			$this->editCount++;
			$edit = explode( '-^^-', $edits );
			if ( $edit[0] == '' || $edit[1] == '' || $edit[2] == '' ) {
				continue;
			}
			$pid = $edit[0];
			$data[$pid][$t]['template'] = General::makeSpaceFromUnderscore($edit[1]);
			if( ( strpos($edit[1],'|') !== false ) && ( strpos($edit[1],'=') !== false ) ) {
				// We need to find the template with a specific argument and value
				$line = explode('|',$data[$pid][$t]['template']);
				$info = explode('=',$line[1]);
				$data[$pid][$t]['find'] = $info[0];
				$data[$pid][$t]['val'] = $info[1];
				$data[$pid][$t]['template'] = $line[0];
			} else {
				$data[$pid][$t]['find'] = false;
				$data[$pid][$t]['val'] = false;
			}

			if ( $edit[3] != '' ) {
				$data[$pid][$t]['variable'] = $edit[3];
			} else {
				$data[$pid][$t]['variable'] = $edit[2];
			}

			if ( $edit[4] != '' ) {
				$data[$pid][$t]['value'] = $edit[4];
			} else {
				$ff = General::makeUnderscoreFromSpace($edit[2]);
				// Does this field exist in the current form that we can use ?
				if ( ! isset( $_POST[ $ff ] ) ) {
					$data[$pid][$t]['value'] = '';
				} else {
					// The value will be grabbed from the form
					// But first check if this is an array
					if ( is_array( $_POST[$ff] ) ) {
						$data[$pid][$t]['value'] = "";
						foreach ( $_POST[$ff] as $multiple ) {
							$data[$pid][$t]['value'] .= $multiple . ',';
						}
						$data[$pid][$t]['value'] = rtrim(
							$data[$pid][$t]['value'],
							','
						);
					} else { // it is not an array.
						$data[$pid][$t]['value'] = $_POST[$ff];
					}
				}
			}
			if ( $edit[5] != '' ) {
				$data[$pid][$t]['slot'] = $edit[5];
			} else {
				$data[$pid][$t]['slot'] = false;
			}

			$t++;
		}
		return $data;
	}

	public function editPage(){
	// We have edits to make to existing pages!
		$data = array();
		$t=0;

		$data = $this->createEditData();
		if( Config::isDebug() ) {
			wsDebug::addToDebug( 'edit data accumulation '. $this->editCount, $data );
		}
		//echo "<pre>";
		//print_r( $data );
		//die();
		// We have all the info in the data Array
		// Now we need to grab the page and replace what needs to be replaced.


		$pageContents = array();
		$render = new Render();
		foreach ($data as $pid => $edits) {
			//setup slots if needed
			$wehaveslots = false;
			foreach( $edits as $edit ) {
				if( $edit['slot'] !== false ) {
					$wehaveslots = true;
					//$pageTitle = $edit['slot'];
					$content = $render->getSlotContent( $pid, $edit['slot'] );

					//$content = $api->getWikiPage( $pid, $edit['slot'] );
					if( $content['content'] == '' ) {
						$pageContents[ $edit['slot']['content'] ] = false;
					} else {
						$pageContents[ $edit['slot'] ] = $content['content'];
					}

					$pageTitle = $content['title'];
				}
			}
			//print_r( "We have " . count( $pageContents ) . " pagecontents and we have " .count( $edits ). " edits" );
			if( !$wehaveslots ) {
				$pageContents[ 'main' ] = $render->getSlotContent( $pid );
				$pageTitle = $pageContents['main']['title'];
				$pageContents['main'] = $pageContents['main']['content'];

			}
			//die();
			//$editSlot = $edits[0]['slot'];
			//$pageContent = $api->getWikiPage( $pid, $editSlot );
			$usedVariables = array();
			foreach ( $edits as $edit ) {
				$slotToEdit = $edit['slot'];
				if( $slotToEdit === false ){
					$slotToEdit = 'main';
				}

				if($edit['find'] !== false) {
					$templateContent = $this->getTemplate( $pageContents[$slotToEdit], $edit['template'], $edit['find'], $edit['val'] );
					if($templateContent===false) {
						$result['received']['error'][] = 'Template: '.$edit['template'].' where variable:'.$edit['find'] . '='.$edit['val'].' not found';
					}
				} else {
					$templateContent = $this->getTemplate( $pageContents[$slotToEdit], $edit['template'] );
				}
				if ($templateContent === false) {
					//echo 'skipping ' . $edit['template'] ;
					continue;
				}


				$expl = pregExplode($templateContent);
				foreach ( $expl as $k => $line ) {
					$tmp = explode('=',$line);
					if( trim( $tmp[0]) == $edit['variable'] ) {
						$expl[$k] = $edit['variable'].'='.$edit['value'];
						$usedVariables[]=$edit['variable'];
					}
				}
				if(!in_array($edit['variable'],$usedVariables)) {
					$ttemp = $edit['variable'];
					$expl[]=$edit['variable'].'='.$edit['value'];
				}


				$newTemplateContent = '';
				$cnt = count( $expl );
				$t = 0;
				foreach ($expl as $line) {

					if(strlen($line) > 1) {
						$newTemplateContent .= "\n" . '|' . trim($line) ;
					}
					// Is it the last one. Then {5041} put end template }} on a new line
					if( $t === ($cnt-1) ){
						$newTemplateContent .= "\n";
					}
					$t++;

				}
				$pageContents[$slotToEdit] = str_replace($templateContent,$newTemplateContent, $pageContents[$slotToEdit] );

			}
			if( Config::isDebug() ) {
				wsDebug::addToDebug( 'edit data page formation ', $pageContents );
			}
			return $pageContents;

		}
	}

}