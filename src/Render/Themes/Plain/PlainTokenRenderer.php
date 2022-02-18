<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Core\Core;
use FlexForm\Render\Themes\TokenRenderer;
use Xml;

class PlainTokenRenderer implements TokenRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_token(
		string $input,
		string $id,
		int $inputLengthTrigger,
		?string $placeholder,
		?string $smwQuery,
		?string $json,
		?string $callback,
		?string $template,
		bool $multiple,
		bool $allowTags,
		bool $allowClear,
		array $additionalArguments
	) : string {
		$selectTag = $this->renderSelectTag(
			$input,
			$id,
			$placeholder,
			$multiple,
			$additionalArguments
		);
		$selectJavascript = $this->renderSelectJavascript(
			$id,
			$template,
			$smwQuery,
			$json,
			$placeholder,
			$callback,
			$allowTags,
			$allowClear,
			$inputLengthTrigger
		);

		// FIXME: Rename 'wachtff' to something that makes more sense
		return $selectTag . $selectJavascript . "<script>wachtff(attachTokens, true );</script>";
	}

	/**
	 * @param string $input
	 * @param string $id
	 * @param string|null $placeholder
	 * @param bool $multiple
	 * @param array $attribs
	 *
	 * @return string
	 */
	private function renderSelectTag(
		string $input,
		string $id,
		?string $placeholder,
		bool $multiple,
		array $attribs
	) : string {
		$attribs = array_merge(
			$attribs,
			[
				'data-inputtype' => 'ws-select2',
				'id'             => $id
			]
		);

		if ( $multiple ) {
			$attribs['multiple'] = 'multiple';
		}

		if ( Core::isLoaded( 'wsinstance-initiated' ) ) {
			$attribs['data-wsselect2id'] = $id;
		}

		$contents = '';

		// If we have a placeholder, we put an empty option inside of the select tag
		if ( $placeholder !== null ) {
			$contents .= '<option></option>';
		}

		// Place the fully parsed contents of the "wstoken" tag inside of the select
		$contents .= $input;

		// Render the "wstoken" tag
		return Xml::tags(
			'select',
			$attribs,
			$contents
		);
	}

	/**
	 * @param string $id
	 * @param string|null $template
	 * @param string|null $smwQuery
	 * @param string|null $json
	 * @param string|null $placeholder
	 * @param string|null $callback
	 * @param bool $allowTags
	 * @param bool $allowClear
	 * @param int $inputLengthTrigger
	 *
	 * @return string
	 */
	private function renderSelectJavascript(
		string $id,
		?string $template,
		?string $smwQuery,
		?string $json,
		?string $placeholder,
		?string $callback,
		bool $allowTags,
		bool $allowClear,
		int $inputLengthTrigger
	) : string {
		$javascript = '';

		global $wgScriptPath, $wgServer;
		if ( $wgScriptPath !== "" ) {
			$smwQueryUrl = $wgServer . "/" . $wgScriptPath . '/index.php/Special:FlexForm';
		} else {
			$smwQueryUrl = $wgServer . '/index.php/Special:FlexForm';
		}
		if ( $smwQuery !== null ) {
			$smwQueryUrl .= '?action=handleExternalRequest';
			$smwQueryUrl .= '&script=SemanticAsk';
			$smwQueryUrl .= '&query=' . $smwQuery;
		} else {
			$smwQueryUrl = null;
		}

		if ( $smwQueryUrl !== null ) {
			$javascript .= "var jsonDecoded = decodeURIComponent( '" . urlencode( $smwQueryUrl ) . "' );\n";
		}

		$javascript .= "$('#" . $id . "').select2({";

		if ( $placeholder !== null ) {
			$javascript .= "placeholder: '" . htmlspecialchars(
					$placeholder,
					ENT_QUOTES
				) . "',";
		}

		if ( $smwQueryUrl !== null ) {
			$javascript .= <<<SCRIPT
                templateResult: testSelect2Callback,
                escapeMarkup: function (markup) { 
                    return markup; 
                },
                minimumInputLength: $inputLengthTrigger,
                ajax: { 
                    url: jsonDecoded, 
                    delay:500, 
                    dataType: 'json',
                    data: function (params) { 
                        var queryParameters = { q: params.term }; 
                        return queryParameters; 
                    }
                }
            SCRIPT;
		}

		if ( $allowTags ) {
			$javascript .= $smwQueryUrl !== null ? ",\ntags: true" : "\ntags: true";
		}

		if ( $allowClear && $placeholder !== null ) {
			$javascript .= $smwQueryUrl !== null || $allowTags !== false ? ",\nallowClear: true" : "\nallowClear: true";
		}

		$javascript .= '});';

		if ( $smwQueryUrl !== null && $callback !== null ) {
			$preparedTemplate = $template !== null ? ", '" . $template . "'" : '';

			$callbackJavascript = <<<SCRIPT
                $('#$id').on('select2:select', function(e) { 
                    $callback('$id'$preparedTemplate);
                });
                
                $('#$id').on('select2:unselect', function(e) { 
                    $callback('$id'$preparedTemplate);
                });
            SCRIPT;
		} else {
			$callbackJavascript = '';
		}

		$javascript .= $callbackJavascript . "$('select').trigger('change');";

		$format = ! Core::isLoaded(
			'wsinstance-initiated'
		) ? '<input type="hidden" id="%s" value="%s" />' : '<input type="hidden" data-wsselect2options="%s" value="%s" />';

		return sprintf(
			$format,
			'select2options-' . $id,
			$javascript
		);
	}
}