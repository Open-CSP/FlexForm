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
					$source = $render->getSlotContent( $args['source'] );
					$this->json = json_decode( $source['content'] );
				} catch ( Exception | FlexFormException $e ) {
					return wfMessage( 'flexform-error-invalid-json' )->text() . ": " . $e->getMessage();
				}
				break;
		}
		return true;
	}

	/**
	 * @param array &$inputSingle
	 *
	 * @return string
	 */
	private function createFunctionType( array &$inputSingle ) : string {
		if ( $inputSingle['type'] === 'input' ) {
			$functionType        = 'field';
			$inputSingle['type'] = $inputSingle['input-type'];
		} else {
			$functionType = $inputSingle['type'];
		}

		return $functionType;
	}

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	private function createInput( array $data ): string {
		$input = '';
		switch ( $data['type'] ) {
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
		return $input;
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

		$inputs = $this->json['properties']['form']['properties'];
		foreach ( $inputs as $name => $inputSingle ) {
			$input = $this->createInput( $inputSingle );
			$functionType = $this->createFunctionType( $inputSingle );
			$newArgs         = $inputSingle;
			$newArgs['name'] = $name;
			$tagHook        = new TagHooks( $themeStore );
			$functionName    = "render" . ucfirst( $functionType );
			// var_dump( "Running $functionName" );
			//var_dump( $newArgs );
			//var_dump( $input );
			$result  = $tagHook->$functionName(
				$input,
				$newArgs,
				$parser,
				$frame
			);
			$this->content .= $result[0];
			if ( isset( $inputSingle['behaviour'] ) && $inputSingle['behaviour'] === 'break' ) {
				$this->content .= '<br>';
			}
		}

		// }
		return $this->content;
	}

}
