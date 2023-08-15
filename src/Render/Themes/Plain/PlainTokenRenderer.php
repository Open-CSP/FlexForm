<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Core\Core;
use FlexForm\Render\Themes\TokenRenderer;
use Xml;

class PlainTokenRenderer implements TokenRenderer {

	const OPTION_SEPARATOR = '::';

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
		array $selectedValues,
		array $options,
		array $additionalArguments
	) : string {
		$selectTag = $this->renderSelectTag(
			$input,
			$id,
			$placeholder,
			$multiple,
			$selectedValues,
			$options,
			$additionalArguments,
			$allowTags
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
	 * @param array $selectedValues
	 * @param array $options
	 * @param array $attribs
	 * @param bool $allowTags
	 *
	 * @return string
	 */
	private function renderSelectTag(
		string $input,
		string $id,
		?string $placeholder,
		bool $multiple,
		array $selectedValues,
		array $options,
		array $attribs,
		bool $allowTags
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

		if ( $allowTags ) {
			$attribs['data-allowtags'] = "yes";
		}

		$contents = '';

		// If we have a placeholder, we put an empty option inside of the select tag
		if ( $placeholder !== null ) {
			$contents .= '<option></option>';
		}

		$tagContent = '';
		foreach ( $options as $option ) {
			if ( ! strpos(
				$option,
				self::OPTION_SEPARATOR
			) ) {
				$text = $valueName = $option;
			} else {
				list ( $text, $valueName ) = explode(
					self::OPTION_SEPARATOR,
					$option,
					2
				);
			}

			$isSelected = in_array(
				$text,
				$selectedValues
			);
			$tagContent .= Xml::option(
				htmlspecialchars( $text ),
				$valueName,
				$isSelected
			);
		}

		// Place the fully parsed contents of the "wstoken" tag inside of the select
		$contents .= $input;
		$tagContent .= $contents;
		// Render the "wstoken" tag
		return Xml::tags(
			'select',
			$attribs,
			$tagContent
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
			$smwQueryUrl = "/" . ltrim( $wgScriptPath, '/' ) . '/index.php/Special:FlexForm';
		} else {
			$smwQueryUrl = '/index.php/Special:FlexForm';
		}
		if ( $smwQuery !== null ) {
			$filterQuery = $this->checkFilterQuery( $smwQuery );
			if ( $filterQuery !== false ) {
				$smwQuery = $filterQuery['query'];
				$ffFormField = $filterQuery['fffield'];
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
			$javascript .= "var ffTokenFormField" . $uniqueID . " = '" . base64_encode( $ffFormField ) . "';\n";
			$javascript .= "var ffForm" . $uniqueID . " = $('#" . $id . "').closest('form');\n";
		}

		$javascript .= "var selectEl = $('#" . $id . "').select2({";
		//escape markup
		$javascript .= "\nescapeMarkup: function (markup) {\nreturn decodeHtml(markup);\n},\n";

		if ( $placeholder !== null ) {
			$javascript .= "placeholder: '" . htmlspecialchars(
					$placeholder,
					ENT_QUOTES
				) . "',";
		}



		if ( $smwQueryUrl !== null ) {
			$noResultsFound = wfMessage( 'flexform-query-handler-smw-no-result' )->text();
			$noResultsFoundJS = "\nlanguage: {
                    \nnoResults: function() {
                        \nreturn '" . $noResultsFound . "';
                    \n}
                \n},";
			$javascript .= <<<SCRIPT
                templateResult: testSelect2Callback,
                $noResultsFoundJS
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

		if ( $allowClear !== false && $placeholder !== null ) {
			$javascript .= $smwQueryUrl !== null || $allowTags !== false ? ",\nallowClear: true" : "\nallowClear: true";
		}

		$javascript .= '});';
		if ( $allowSort ) {
			$javascript .= '$.when( ' . "\n";
			$javascript .= '$.getScript( \'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js\' ) )';
			$javascript .= '.done( function () { ' . "\n";
			$javascript .= "\nselectEl.next().children().children().children().sortable({
	containment: 'parent', stop: function (event, ui) {
		ui.item.parent().children('[title]').each(function () {
			var title = $(this).attr('title');
			var original = $( 'option:contains(' + title + ')', selectEl ).first();
			original.detach();
			selectEl.append(original)
		});
		selectEl.change();
	}
}); })\n";
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

		$javascript .= $callbackJavascript;

		$javascript .= "$('#" . $id . "').trigger('change');\n;";

		$format = '<input type="hidden" id="%s" value="%s" />';

		return sprintf(
			$format,
			'select2options-' . $id,
			$javascript
		);
	}
}