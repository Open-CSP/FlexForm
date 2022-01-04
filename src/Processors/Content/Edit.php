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

class Edit {

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

}