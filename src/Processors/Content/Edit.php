<?php
/**
 * Created by  : Wikibase Solutions
 * Project     : FlexForm
 * Filename    : edit.class.php
 * Description :
 * Date        : 19-3-2021
 * Time        : 21:23
 */

namespace FlexForm\Processors\Content;

use FlexForm\Core\Config;
use FlexForm\Core\Core;
use FlexForm\Core\Debug;
use FlexForm\Processors\Utilities\General;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Class for editing pages
 */
class Edit {

	private $editCount = 0;

	/**
	 * Function used by the Edit page functions
	 *
	 * @param string $source
	 * @param string $template
	 * @param mixed $find
	 * @param mixed $value
	 *
	 * @return false|string
	 */
	public function getTemplate( string $source, string $template, $find = false, $value = false ) {
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

	/**
	 * @param $str
	 *
	 * @return array|false|string[]
	 */
	public static function pregExplode( $str ) {
		return preg_split(
			'~\|(?![^{{}}]*\}\})~',
			$str
		);
	}

	/**
	 * @param $start
	 * @param $txt
	 *
	 * @return false|mixed
	 */
	private function getEndPos( $start, $txt ) {
		$pos      = false;
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

	/**
	 * @param $name
	 * @param $template
	 *
	 * @return array
	 */
	private function getTemplateValueAndDelete( $name, $template ) {
		//echo "searching for $name";
		$regex = '#%ws_' . $name . '=(.*?)%#';
		preg_match(
			$regex,
			$template,
			$tmp
		);

		if ( isset( $tmp[1] ) ) {
			$tmp = $tmp[1];
		} else {
			$ret['val'] = false;
			$ret['tpl'] = $template;
		}
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

	/**
	 * @param string $string
	 * @param string $start
	 * @param int $offset
	 *
	 * @return false|int
	 */
	private function getStartPos( string $string, string $start, int $offset = 0 ) {
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

	/**
	 * @param $txt
	 *
	 * @return array|string|string[]
	 */
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

	/**
	 * @param string $source
	 * @param int $start
	 * @param int $end
	 * @param string $find
	 * @param string $value
	 *
	 * @return bool
	 */
	private function checkTemplateValue( string $source, int $start, int $end, string $find, string $value ): bool {
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
	private function createEditData() : array {
		//edit = [0]pid [1]template [2]Form field [3]Use field [4]Value [5]Slot
		$data   = array();
		$t      = 0;
		$fields = ContentCore::getFields();
		foreach ( $fields['mwedit'] as $edits ) {
			$this->editCount++;
			//[0]target:[1]template:[2]formfields:[3]usefields:[4]value:[5]slot
			$edit = explode(
				Core::DIVIDER,
				$edits
			);
			if ( trim( $edit[0] ) == '' || trim( $edit[1] ) == '' || trim( $edit[2] ) == '' ) {
				continue;
			}
			$pid                        = trim( $edit[0] );
			$data[$pid][$t]['template'] = General::makeSpaceFromUnderscore( trim( $edit[1] ) );

			// Get format to use. defaults to wiki
			if ( isset( $edit[6] ) && $edit[6] !== 'wiki' ) {
				$data[$pid][$t]['format'] = trim( $edit[6] );
			} else {
				$data[$pid][$t]['format'] = 'wiki';
			}

			switch ( $data[$pid][$t]['format'] ) {
				case "json":
					if ( strpos( $data[$pid][$t]['template'], 'json' ) !== false ) {
						if ( strpos( $data[$pid][$t]['template'], '|' ) ) {
							$templateExplode = explode( '|', $data[$pid][$t]['template'] );
							$data[$pid][$t]['template'] = $templateExplode[0];
							if ( $templateExplode[0] === 'jsonk' ) {
								$data[$pid][$t]['find'] = explode(
									'.',
									$templateExplode[1]
								);
							} else {
								$data[$pid][$t]['find'] = explode(
									'=',
									$templateExplode[1]
								);
							}
						}
					}
					break;
				case "wiki":
				default :
					// Do we have a unique identifier to search for?
					if ( ( strpos(
							   $edit[1],
							   '|'
						   ) !== false ) && ( strpos(
												  $edit[1],
												  '='
											  ) !== false ) ) {
						// We need to find the template with a specific argument and value
						$line                       = explode(
							'|',
							$data[$pid][$t]['template']
						);
						$info                       = explode(
							'=',
							$line[1]
						);
						$data[$pid][$t]['find']     = $info[0];
						$data[$pid][$t]['val']      = $info[1];
						$data[$pid][$t]['template'] = $line[0];
					} else {
						$data[$pid][$t]['find'] = false;
						$data[$pid][$t]['val']  = false;
					}
					break;
			}

			if ( $edit[3] != '' ) {
				$data[$pid][$t]['variable'] = trim( $edit[3] );
			} else {
				$data[$pid][$t]['variable'] = trim( $edit[2] );
			}

			if ( $edit[4] != '' ) {
				$data[$pid][$t]['value'] = trim( $edit[4] );
			} else {
				$ff = General::makeUnderscoreFromSpace( trim( $edit[2] ) );
				// Does this field exist in the current form that we can use ?
				if ( ! isset( $_POST[$ff] ) ) {
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
				$data[$pid][$t]['slot'] = trim( $edit[5] );
			} else {
				$data[$pid][$t]['slot'] = false;
			}

			$t++;
		}

		return $data;
	}

	/**
	 * @param array $edit
	 * @param int|string $pid
	 * @param array &$pageContents
	 * @param array &$result
	 * @param array &$usedVariables
	 *
	 * @return void
	 */
	private function actualWikiEdit(
		array $edit,
		$pid,
		array &$pageContents,
		array &$result,
		array &$usedVariables
	) {
		$slotToEdit = $edit['slot'];
		if ( $slotToEdit === false ) {
			$slotToEdit = 'main';
		}

		if ( $edit['find'] !== false ) {
			$templateContent = $this->getTemplate(
				$pageContents[$pid][$slotToEdit]['content'],
				$edit['template'],
				$edit['find'],
				$edit['val']
			);
			if ( $templateContent === false ) {
				$rslt                          = 'Template: ' . $edit['template'];
				$rslt                          .= ' where variable:' . $edit['find'] . '=' . $edit['val'] . ' not found';
				$result['received']['error'][] = $rslt;
			}
		} else {
			$templateContent = $this->getTemplate(
				$pageContents[$pid][$slotToEdit]['content'],
				$edit['template']
			);
		}
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Template content for ' . $pid,
				$templateContent
			);
		}
		if ( $templateContent === false || empty( trim( $templateContent ) ) ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Skipping this edit. Template content is false or Template Content is empty for ' .
					$edit['template'],
					$templateContent
				);
			}

			// echo 'skipping ' . $edit['template'] ;
			return;
		}

		$expl = self::pregExplode( $templateContent );
		if ( $expl === false ) {
			// There's nothing to explode lets add the new argument
			$expl            = [];
			$expl[]          = $edit['variable'] . '=' . $edit['value'];
			$usedVariables[] = $edit['variable'];
		}
		foreach ( $expl as $k => $line ) {
			$tmp = explode(
				'=',
				$line
			);
			if ( trim( $tmp[0] ) == $edit['variable'] ) {
				$expl[$k]        = $edit['variable'] . '=' . $edit['value'];
				$usedVariables[] = $edit['variable'];
			}
		}
		if ( !in_array(
			$edit['variable'],
			$usedVariables
		) ) {
			$ttemp  = $edit['variable'];
			$expl[] = $edit['variable'] . '=' . $edit['value'];
		}

		$newTemplateContent = '';
		$cnt                = count( $expl );
		$t                  = 0;
		if ( Config::isDebug() ) {
			Debug::addToDebug( 'Creating new template content for ' . $pid,
							   [
								   'cnt expl'          => $cnt,
								   'expl'              => $expl,
								   'cnt usedvariables' => count( $usedVariables ),
								   'Edit'              => $edit
							   ] );
		}
		foreach ( $expl as $line ) {
			if ( strlen( $line ) > 1 ) {
				$newTemplateContent .= "\n" . '|' . trim( $line );
			}
			// Is it the last one. Then {5041} put end template }} on a new line
			if ( $t === ( $cnt - 1 ) ) {
				$newTemplateContent .= "\n";
			}
			$t++;
		}
		$pageContents[$pid][$slotToEdit]['content'] = str_replace(
			$templateContent,
			$newTemplateContent,
			$pageContents[$pid][$slotToEdit]['content']
		);
	}

	/**
	 * @param array $arr
	 * @param string $lookup
	 * @param string|int $value
	 *
	 * @return array|null
	 */
	private function getkeypath( array $arr, string $lookup, $value ) {
		if ( is_numeric( $value ) ) {
			$value = (int)$value;
		}
		if ( array_key_exists( $lookup, $arr ) && $arr[$lookup] === $value ) {
			return array( $lookup );
		} else {
			foreach ( $arr as $key => $subarr ) {
				if ( is_array( $subarr ) ) {
					$ret = $this->getkeypath( $subarr, $lookup, $value );

					if ( $ret ) {
						$ret[] = $key;

						return $ret;
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param array $edit
	 * @param int|string $pid
	 * @param array &$pageContents
	 * @param array &$result
	 * @param array &$usedVariables
	 *
	 * @return void
	 */
	private function actualJSONEdit(
		array $edit,
		$pid,
		array &$pageContents,
		array &$result,
		array &$usedVariables
	) {
		$slotToEdit = $edit['slot'];
		if ( $slotToEdit === false ) {
			$slotToEdit = 'main';
		}
		$template = $edit['template'];
		$content = $pageContents[$pid][$slotToEdit]['content'];

		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'Template content for ' . $pid,
				$content
			);
		}
		$JSONContent = json_decode(
			$content,
			true
		);
		if ( $content === false || empty( trim( $content ) || $JSONContent === null ) ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'Skipping this edit. Template content is false or Template Content is empty or JSON' . 'cannot be decoded for ' . $edit['template'],
					[
						$content,
						$JSONContent
					] );
			}

			// echo 'skipping ' . $edit['template'] ;
			return;
		}
		if ( empty( $edit['find'] ) || $edit['find'] === false ) {
			if ( Config::isDebug() ) {
				Debug::addToDebug( 'Skipping this edit. There no JSON keys defined',
								   [
									   $content,
									   $edit
								   ] );
			}

			// echo 'skipping ' . $edit['template'] ;
			return;
		}

		if ( $template === "json" ) {
			$findKey   = $edit['find'][0];
			$findValue = $edit['find'][1];

			if ( is_numeric( $findValue ) ) {
				$findValue = (int) $findValue;
			}

			//echo "<pre>";
			//$edit['variable'] . '=' . $edit['value'];
			//var_dump( $findKey );
			//var_dump( $findValue );
			$pathresult = $this->getkeypath(
				$JSONContent,
				$findKey,
				$findValue
			);
			//var_dump( $pathresult );
			krsort( $pathresult );
			$path = [];
			$cur  = &$path;
			foreach ( $pathresult as $value ) {
				$cur[$value] = [];
				$cur         = &$cur[$value];
			}
			$cur = null;
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'array search result ' . time(),
					[
						"findKey"    => $findKey,
						"findValue"  => $findValue,
						"pathResult" => $path
					]
				);
			}
			//var_dump( $path );
			if ( $path !== null ) {
				$JSONContent[key( $path )][$edit['variable']] = $edit['value'];
			} else {
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'array search result error. Not found' . time(),
						[
							"findKey"    => $findKey,
							"findValue"  => $findValue,
							"pathResult" => $path
						]
					);
				}
			}
			// TODO: How to treat values for forms and numbers ?
			//$JSONContent[0][$edit['variable']] = $edit['variable'];
			//$newKey = $this->createNestedArray( $find );
			//var_dump( $newKey );
			//var_dump( $JSONContent[$newKey] );
			//var_dump( $JSONContent );
			//die();
			//if ( isset( $JSONContent[$newKey] ) ) {
			//	echo "found";
			//$edit['variable'] . '=' . $edit['value'];
			//	}

		} else {
			if ( $this->arrayPath( $JSONContent, $edit['find'] ) === null ) {
				if ( Config::isDebug() ) {
					Debug::addToDebug(
						'array search result error. Not found' . time(),
						[
							"find"    => $edit['find'],
							"JSON"  => $JSONContent
						]
					);
				}
			} else {
				$this->arrayPath( $JSONContent, $edit['find'], $edit['value'] );
			}
			//echo "<pre>";
			//var_dump( $edit['find'] );
			//var_dump( $edit['value'] );
			//var_dump( $resr );
			//var_dump($this->arrayPath( $JSONContent, $edit['find'] ));
			//var_dump( $JSONContent );
			//die();
		}

		$pageContents[$pid][$slotToEdit]['content'] = json_encode( $JSONContent, JSON_PRETTY_PRINT );
	}

	/**
	 * set/return a nested array value
	 *
	 * @param array $array the array to modify
	 * @param array $path the path to the value
	 * @param mixed $value (optional) value to set
	 *
	 * @return mixed previous value
	 */
	private function arrayPath( &$array, $path = array(), &$value = null ) {
		$args = func_get_args();
		$ref  = &$array;
		foreach ( $path as $key ) {
			if ( !is_array( $ref ) ) {
				$ref = [];
			}
			$ref = &$ref[$key];
		}
		$prev = $ref;
		if ( array_key_exists(
			2,
			$args
		) ) {
			// value param was passed -> we're setting
			$ref = $value;  // set the value
		}

		return $prev;
	}

	/**
	 * @return array|void
	 */
	public function editPage() {
		// We have edits to make to existing pages!

		$data = $this->createEditData();
		if ( Config::isDebug() ) {
			Debug::addToDebug(
				'edit data accumulation ' . $this->editCount,
				$data
			);
		}
		// We have all the info in the data Array
		// Now we need to grab the page and replace what needs to be replaced.

		$pageContents = [];
		$render       = new Render();
		// Loop through all edits
		foreach ( $data as $pid => $edits ) {
			// setup slots if needed
			$wehaveslots = false;
			foreach ( $edits as $edit ) {
				if ( $edit['slot'] !== false && !isset( $pageContents[$pid][$edit['slot']]['content'] ) ) {
					$wehaveslots = true;
					// $pageTitle = $edit['slot'];
					$content = $render->getSlotContent(
						$pid,
						$edit['slot']
					);
					if ( Config::isDebug() ) {
						Debug::addToDebug(
							'Content for ' . $pid,
							$content
						);
					}

					// $content = $api->getWikiPage( $pid, $edit['slot'] );
					if ( $content['content'] == '' ) {
						$pageContents[$pid][$edit['slot']]['content'] = false;
					} else {
						$pageContents[$pid][$edit['slot']]['content'] = $content['content'];
					}

					$pageContents[$pid][$edit['slot']]['title'] = $content['title'];
				} elseif ( !isset( $pageContents[$pid]['main'] ) ) {
					$pageContents[$pid]['main'] = $render->getSlotContent( $pid );
					if ( Config::isDebug() ) {
						Debug::addToDebug(
							'Content for ' . $pid,
							$pageContents
						);
					}
				}
			}

			$usedVariables = [];
			$result = [];
			foreach ( $edits as $edit ) {
				$format = $edit['format'];
				switch ( $format ) {
					case "json":
						$this->actualJSONEdit( $edit, $pid, $pageContents, $result, $usedVariables );
						break;
					case "wiki":
					default:
						$this->actualWikiEdit( $edit, $pid, $pageContents, $result, $usedVariables );
						break;
				}
			}
			if ( Config::isDebug() ) {
				Debug::addToDebug(
					'edit data page formation ',
					[ 'pagecontents' => $pageContents ]
				);
			}
		}
		return $pageContents;
	}

}