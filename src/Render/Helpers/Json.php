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
		$functionType = $inputSingle['htmlElement'] ?? 'field';
		return "render" . ucfirst( $functionType );
	}

	private function setFieldType( $data ) {
		$inputType = "text";
		if ( isset( $data['inputType'] ) ) {
			$inputType = $data['inputType'];
		} else {
			switch ( $data['type'] ) {
				case "string":
					$inputType = "text";
				case "number":
				case "integer":
					$inputType = "number";
					break;
				default:
					$inputType = "text";
					break;
			}
		}
		return $inputType;
	}

	/**
	 * @param array $data
	 *
	 * @return mixed|string
	 */
	private function setInputType( array $data ): string {
		if ( isset( $data['htmlElemenent'] ) ) {
			if ( $data['htmlElemenent'] === "input" ) {
				return "field";
			} else {
				return $data['htmlElemenent'];
			}
		} else {
			return "field";
		}
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
				case "label":
					if ( isset( $data['title'] ) ) {
						$input = $data['title'];
					}
					break;
				case "textarea":
					if ( isset( $data['content'] ) ) {
						$input = $data['content'];
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
				$this->handleJsonObject( $element['title'], $element['properties'] );
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
	private function renderElement( string $input, array $args, string $functionName ) {
		$tagHook = new TagHooks( $this->themeStore );
		echo "<pre>";
		var_dump( $input );
		var_dump( $args );
		var_dump( $functionName );
		echo "</pre>";
		return $tagHook->$functionName(
			$input,
			$args,
			$this->parser,
			$this->frame
		);
	}

	/**
	 * @param string $name
	 * @param array $properties
	 *
	 * @return void
	 */
	private function handleJsonObject( string $name, array $properties ) {
		foreach ( $properties as $propertyName => $property ) {
			if ( $property['type'] === 'object' ) {
				$this->content .= "<h3>$propertyName</h3>";
				$this->handleJsonObject( $propertyName, $property['properties'] );
			}
			$functionName = $this->createFunctionName( $property );
			$input = $this->createInput( $property );
			$newArgs         = $property;
			$newArgs['name'] = $propertyName;
			//$newArgs['type'] = $this->setFieldType()
			if ( $this->checkRequired( $name, $properties ) ) {
				$newArgs['required'] = 'required';
			}
			$this->content .= $this->renderElement( $input, $newArgs, $functionName )[0];
			if ( isset( $property['behaviour'] ) && $property['behaviour'] === 'break' ) {
				$this->content .= '<br>';
			}
		}
	}

}
