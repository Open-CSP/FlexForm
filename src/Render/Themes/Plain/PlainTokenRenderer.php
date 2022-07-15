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
		bool $allowSort,
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
			$allowSort,
			$inputLengthTrigger
		);

		// FIXME: Rename 'wachtff' to something that makes more sense
		if ( !Core::isLoaded( 'attachTokens' ) ) {
			Core::includeInlineScript( 'wachtff(attachTokens, true );' );
			Core::addAsLoaded( 'attachTokens' );
		}
		//return $selectTag . $selectJavascript . "<script>wachtff(attachTokens, true );</script>";
		return $selectTag . $selectJavascript;
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
	 * @param string $query
	 *
	 * @return array|false
	 */
	private function checkFilterQuery( string $query ) {
		if ( strpos( $query, '[fffield=' ) !== false ) {
			$fQuery = Core::get_string_between( $query, '[fffield=', ']' );
			$query = str_replace(
				'[fffield=' . $fQuery . ']',
				'__^^__',
				$query
			);
			return [ 'query' => $query, 'fffield' => $fQuery ];
		} else {
			return false;
		}
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
	 * @param bool $allowSort
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
		bool $allowSort,
		int $inputLengthTrigger
	) : string {
		$javascript = '';

		global $wgScriptPath, $wgServer;
		if ( $wgScriptPath !== "" ) {
			$smwQueryUrl =  "/" . $wgScriptPath . '/index.php/Special:FlexForm';
		} else {
			$smwQueryUrl =  '/index.php/Special:FlexForm';
		}
		if ( $smwQuery !== null ) {
			$filterQuery = $this->checkFilterQuery( $smwQuery );
			if ( $filterQuery !== false ) {
				$smwQuery = $filterQuery['query'];
				$ffFormField = $filterQuery['ffformfield'];
			} else {
				$ffFormField = '';
			}
			$smwQueryUrl .= '?action=handleExternalRequest';
			$smwQueryUrl .= '&script=SemanticAsk&query=';
			$smwQueryUrlQ = base64_encode( $smwQuery );
		} else {
			$smwQueryUrl = null;
		}

		if ( $smwQueryUrl !== null ) {
			$uniqueID = uniqid();
			$javascript .= "var jsonDecoded". $uniqueID . " = '" . $smwQueryUrl . $smwQueryUrlQ . "';\n";
			$javascript .= "var ffTokenFormField" . $uniqueID . " = '" . $ffFormField . "';\n";
			$javascript .= "var ffForm" . $uniqueID . " = $('#" . $id . "').closest('form');\n";
		}

		$javascript .= "var selectEl = $('#" . $id . "').select2({";

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
                    url: jsonDecoded$uniqueID,
                    delay:500, 
                    dataType: 'json',
                    data: function (params) { 
                        var queryParameters = { q: params.term , ffform: ffFindFormElementValueByName( ffForm$uniqueID, ffTokenFormField$uniqueID ) }; 
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
		if( $allowSort ) {
			$javascript .= "selectEl.next().children().children().children().sortable({
	containment: 'parent', stop: function (event, ui) {
		ui.item.parent().children('[title]').each(function () {
			var title = $(this).attr('title');
			var original = $( 'option:contains(' + title + ')', selectEl ).first();
			original.detach();
			selectEl.append(original)
		});
		selectEl.change();
	}
});";
		}

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

		$format = '<input type="hidden" id="%s" value="%s" />';

		return sprintf(
			$format,
			'select2options-' . $id,
			$javascript
		);
	}
}