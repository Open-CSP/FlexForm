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

use FlexForm\Render\TagHooks;
use Parser;
use PPFrame;

class Json {

	public function handleJSON( $json, array $args, Parser $parser, PPFrame $frame, $themeStore ) {
		$content = '';
		global $IP;
		$json = $IP . $json;
		//echo "<pre>";
		//echo $json;
		//if ( filter_var( $json, FILTER_VALIDATE_URL ) ) {
			//echo "Getting JSON";
			$actions = json_decode( file_get_contents( $json ), true );
			$inputs = $actions['properties']['form']['properties'];
			foreach ( $inputs as $name => $inputSingle ) {

				$input = '';
				if ( $inputSingle['type'] === 'label' ) {
					$input = $inputSingle['title'];
				}
				if ( $inputSingle['type'] === 'input' ) {
					$functionType = 'field';
					$inputSingle['type'] = $inputSingle['input-type'];
				} else {
					$functionType = $inputSingle['type'];
				}
				$newArgs = $inputSingle;
				$newArgs['name'] = $name;
				$taghooks = new TagHooks( $themeStore );
				$functionName = "render" . ucfirst( $functionType );
				//var_dump( "Running $functionName" );
				//var_dump( $newArgs );
				//var_dump( $input );
				$result = $taghooks->$functionName( $input, $newArgs, $parser, $frame );
				$content .= $result[0];
				if ( isset( $inputSingle['behaviour'] ) && $inputSingle['behaviour'] === 'break' ) {
					$content .= '<br>';
				}
			}
		//}
		return $content;
	}

}