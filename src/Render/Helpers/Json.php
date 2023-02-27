<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : Json.php
 * Description :
 * Date        : 17-2-2023
 * Time        : 15:57
 */

namespace FlexForm\Render\Helpers;

use Cdb\Exception;
use FlexForm\FlexFormException;
use FlexForm\Processors\Content\Render;
use FlexForm\Render\TagHooks;
use FlexForm\Render\ThemeStore;
use Parser;
use PPFrame;

class Json {

	/**
	 * @var Parser
	 */
	private Parser $parser;

	/**
	 * @var PPFrame
	 */
	private PPFrame $frame;

	/**
	 * @var ThemeStore
	 */
	private ThemeStore $themeStore;

	/**
	 * @var int
	 */
	private int $level;

	/**
	 * @var array
	 */
	private array $json;

	/**
	 * @var string
	 */
	private string $content;

	/**
	 * @param mixed $data
	 * @param bool $success
	 *
	 * @return array
	 */
	private function returns( $data, bool $success = false ) : array {
		return [
			"status" => $success,
			"data"   => $data
		];
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	private function removeSchemeSpecificOptions( array $args ): array {
		$schemeOptions = [
			"enum", "htmlElement", "inputType", "input", '$id', 'description'
		];
		foreach ( $schemeOptions as $scheme_option ) {
			if ( array_key_exists( $scheme_option, $args ) ) {
				unset( $args[$scheme_option] );
			}
		}
		return $args;
	}

	/**
	 * @param string $json
	 * @param array $args
	 *
	 * @return string|bool
	 */
	private function setJSON( string $json, array $args ) {
		if ( !isset( $args['json-type'] ) ) {
			return wfMessage( 'flexform-error-no-json-type' )->text();
		}
		switch ( $args['json-type'] ) {
			case "string":
				try {
					$this->json = json_decode( $json, true );
				} catch ( Exception $e ) {
					return wfMessage( 'flexform-error-invalid-json' )->text() . ": " . $e->getMessage();
				}
				break;
			case "url":
				try {
					$downloaded = file_get_contents( $json );
					$this->json = json_decode( $downloaded, true );
				} catch ( Exception $e ) {
					return wfMessage( 'flexform-error-invalid-json' )->text() . ": " . $e->getMessage();
				}
				break;
			case "title":
				try {
					$render = new Render();
					$source = $render->getSlotContent( $json );
					$this->json = json_decode( $source['content'], true );
				} catch ( Exception | FlexFormException $e ) {
					return wfMessage( 'flexform-error-invalid-json' )->text() . ": " . $e->getMessage();
				}
				break;
		}
		return true;
	}

	/**
	 * @param array $inputSingle
	 *
	 * @return string
	 */
	private function createFunctionName( array $inputSingle ) : string {
		$functionType = 'field';
		if ( isset( $inputSingle['htmlElement'] ) && $inputSingle['htmlElement'] !== 'input' ) {
			$functionType = $inputSingle['htmlElement'];
		}
		return "render" . ucfirst( $functionType );
	}

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	private function setFieldType( array $data ): string {
		if ( isset( $data['inputType'] ) ) {
			$inputType = $data['inputType'];
		} else {
			switch ( $data['type'] ) {
				case "number":
				case "integer":
					$inputType = "number";
					break;
				case "string":
				default:
					$inputType = "text";
					break;
			}
		}
		return $inputType;
	}

	/**
	 * @param array $names
	 *
	 * @return string
	 */
	private function renderOptionFields( array $names ):string  {
		$args = [];
		$content = '';
		foreach ( $names as $name ) {
			//echo "args, $name :<br>";
			$args['value'] = $name;
			//var_dump( $args );
			//var_dump( $name );
			$content .= $this->renderElement( $name, $args, "renderOption" );
		}
		return $content;
	}

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	private function createInput( array $data ): string {
		$input = '';
		if ( isset( $data['htmlElement'] ) ) {
			switch ( $data['htmlElement'] ) {
				case "select":
					if ( isset( $data['enum'] ) ) {
						$input = $this->renderOptionFields( $data['enum'] );
					}
					break;
				case "instance":
					if ( isset( $data['input'] ) ) {
						$input = $data['input'];
					}
					break;
				case "option":
					if ( isset( $data['label'] ) ) {
						$input = $data['label'];
					}
					break;
			}
		}
		return $input;
	}

	/**
	 * @param string $name
	 * @param array $properties
	 *
	 * @return bool
	 */
	private function checkRequired( string $name, array $properties ): bool {
		//echo "<pre>";
		//var_dump ("Checking required", $name, $properties  );
		//echo "</pre>";
		if ( isset( $properties['required'] ) ) {
			return in_array(
				$name,
				$properties['required']
			);
		} else {
			return false;
		}
	}

	/**
	 * @param string $json
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param ThemeStore $themeStore
	 *
	 * @return string
	 */
	public function handleJSON(
		string $json,
		array $args,
		Parser $parser,
		PPFrame $frame,
		ThemeStore $themeStore
	) : string {
		$this->content = '';

		$status = $this->setJSON(
			$json,
			$args
		);

		//echo "<pre>";
		if ( $status !== true ) {
			return $status;
		}

		$this->parser = $parser;
		$this->frame = $frame;
		$this->themeStore = $themeStore;

		// depth 1
		if ( !isset( $this->json['type'] ) ) {
			return "No type defined. Exiting.";
		}

		$this->walkThroughJson( $this->json );
		// }
		//var_dump ($this->content);
		//die( "test" );
		return $this->content;
	}

	/**
	 * @param array $element
	 *
	 * @return string|void
	 */
	private function walkThroughJson( $element ) {

		switch ( $element['type'] ) {
			case "object" :
				if ( !isset( $element['properties'] ) ) {
					return "No properties defined for object " . $element['title'];
				}
				$this->handleObject( $element, $element );
				break;
			case "array" :
				break;
			default:
				break;
		}
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @param string $functionName
	 *
	 * @return mixed
	 */
	private function renderElement( string $input, array $args, string $functionName, $renderWiki = false ) {
		$args    = $this->removeSchemeSpecificOptions( $args );
		if ( !$renderWiki ) {
			$tagHook = new TagHooks( $this->themeStore );

			//echo "<pre>RenderElement";
			//var_dump( "input:", $input );
			//var_dump( "args:", $args );
			//var_dump( "functionName:", $functionName );
			//echo "</pre>";

			return $tagHook->$functionName(
				$input,
				$args,
				$this->parser,
				$this->frame
			)[0];
		} else {
			$res = '';
			$function = strtolower( str_replace( 'render', '', $functionName ) );
			if ( $function === 'field' ) {
				$function = "input";
			}
			$res .= '<' . $function;
			foreach( $args as $k => $v ) {
				$res .= ' ' . $k . '="' . $v . '"';
			}
			if ( $input !== '' ) {
				$res .= '>' . $input . '</' . $function . '>';
			} else {
				$res .= '/>';
			}
			return $res;
		}
	}

	private function setFunctionAttributes( $name, $property ) {
		$args = $property;
		$args['name'] = $name;
		$args['type'] = $this->setFieldType( $property );

		return $args;
	}

	private function renderProperty( $name, $property, $element, $ret = false ) {
		$functionName = $this->createFunctionName( $property );
		$input = $this->createInput( $property );
		$newArgs = $this->setFunctionAttributes( $name, $property );
		if ( $this->checkRequired( $name, $element ) ) {
			$newArgs['required'] = 'required';
		}
		$content = $this->renderElement( $input, $newArgs, $functionName, $ret  );
		if ( isset( $property['behaviour'] ) && $property['behaviour'] === 'break' ) {
			$content .= '<br>';
		}
		if ( !$ret ) {
			$this->content .= $content;
		} else {
			return $content;
		}
	}

	private function renderInstance( $content, $args ) {
		unset( $args['properties'] );
		unset( $args['required'] );
		$args = $this->removeSchemeSpecificOptions( $args );
		return $this->renderElement( "<div>".$content."</div>", $args, 'renderInstance' );
	}

	private function renderRadio( $name, $radios ) {
		$args = [];
		$args['type'] = "radio";
		$args['name'] = $name;
		$content = '';
		foreach ( $radios as $radio ) {
			$args['value'] = $radio;
			$content .= $this->renderElement( $radio, $args, "renderField" ) . " " . $radio;
		}
		return $content;
	}

	private function handleProperty( $name, $property, $element, $ret = false ) {
		$renderType = $this->setFieldType( $property );
		$content = '';
		switch( $renderType ) {
			case "radio":
				if ( isset( $property['enum'] ) ) {
					$content .= $this->renderRadio( $name, $property['enum'] );
				}
				break;
			default :
				$content .= $this->renderProperty( $name, $property, $element, true );
		}
		if ( $ret ) {
			return $content;
		} else {
			$this->content .= $content;
		}
	}

	public function find_recursive( $obj, $key ) {
		$found = array();
		if ( is_object( $obj ) ) {
			foreach ( $obj as $property => $value ) {
				if ( $property === $key ) {
					$found[] = $value;
				} elseif ( is_array( $value ) || is_object( $value ) ) {
					$found = array_merge( $found,
						$this->find_recursive( $value,
							$key ) );
				}
			}
		} elseif ( is_array( $obj ) ) {
			foreach ( $obj as $keyar => $value ) {
				if ( $keyar === $key ) {
					$found[] = $value;
				} elseif ( is_array( $value ) || is_object( $value ) ) {
					$found = array_merge( $found,
						$this->find_recursive( $value,
							$key ) );
				}
			}
		}

		return $found;
	}

	/**
	 * @param array $element
	 *
	 * @return mixed
	 */
	private function handleObject( array $element, $parentelement, $ret = false ) {
		$htmlElement = false;
		$content = '';
		if ( isset( $element['properties' ] ) ) {
			// in ons voorbeeld zijn properties dus "form" => {}
			$properties = $element['properties' ];
			if ( isset( $element['htmlElement'] ) && $element['htmlElement'] === 'instance' ) {
				$htmlElement = $element['htmlElement'];
				$ret = true;
			}
			foreach ( $properties as $propertyName => $property ) {
				//echo "Working on $propertyName";
				//Property name is "instance" properties = "type", "properties", "required"
				if ( !isset( $property['type'] ) ) {
					return "Error in schema. Missing type property in $propertyName";
				}
				if ( $property['type'] === 'object' ) {
					//echo "\ngoing recursive\n";
					$this->content .= "<h3>$propertyName</h3>";
					if ( $ret ) {
						return $this->handleObject(
							$property,
							$element,
							$ret
						);
					} else {
						$this->handleObject(
							$property,
							$element,
							$ret );
					}
				} else {
					$content .= $this->handleProperty( $propertyName,
						$property,
						$element,
						$ret
						);
				}
			}
			if ( $ret && $htmlElement === 'instance' ) {
				//var_dump( $content );
				//die();
				$this->content = $this->renderInstance( $content, $element );
			}
		}
	}

}
