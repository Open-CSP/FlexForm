<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Core\Config;
use FlexForm\Core\Core;
use FlexForm\Core\Protect;
use FlexForm\Render\Themes\FormRenderer;

class PlainFormRenderer implements FormRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_form(
		string $input,
		string $actionUrl,
		string $mwReturn,
		string $formId,
		?string $messageOnSuccess,
		?string $wikiComment,
		?string $action,
		?string $extension,
		?string $autosaveType,
		?string $additionalClass,
		bool $showOnSelect,
		array $additionalArgs
	) : string {
		$javascript     = '';
		$formAttributes = array_merge(
			[
				'action' => $actionUrl,
				'id'     => $formId,
				'method' => 'post',
				'class'  => 'flex-form'
			],
			$additionalArgs
		);

		$messageOnSuccess = $messageOnSuccess !== null ? Core::createHiddenField(
			'mwonsuccess',
			htmlspecialchars( $messageOnSuccess )
		) : '';
		$wikiComment      = $wikiComment !== null ? Core::createHiddenField(
			'mwwikicomment',
			htmlspecialchars( $wikiComment )
		) : '';
		$action           = $action !== null ? Core::createHiddenField(
			'mwaction',
			htmlspecialchars( $action )
		) : '';
		$extension        = $extension !== null ? Core::createHiddenField(
			'mwextension',
			htmlspecialchars( $extension )
		) : '';
		$mwReturn         = Core::createHiddenField(
			'mwreturn',
			urlencode( $mwReturn )
		);

		if ( $additionalClass !== null ) {
			$formAttributes['class'] .= ' ' . htmlspecialchars( $additionalClass );
		}

		if ( $autosaveType !== null ) {
			$formAttributes['class']         .= ' ws-autosave';
			$formAttributes['data-autosave'] = $autosaveType === "onchange" || $autosaveType === "oninterval" ? $autosaveType : 'auto';

			$javascript .= sprintf(
				'var wsAutoSaveGlobalInterval = %s;',
				htmlspecialchars( Core::$wsConfig['autosave-interval'] ?? '30000' )
			);
			$javascript .= sprintf(
				'var wsAutoSaveOnChangeInterval = %s;',
				htmlspecialchars( Core::$wsConfig['autosave-after-change'] ?? '3000' )
			);
			$javascript .= sprintf(
				'var wsAutoSaveButtonOn = \'%s\';',
				htmlspecialchars( Core::$wsConfig['autosave-btn-on'] ?? 'Autosave on' )
			);
			$javascript .= sprintf(
				'var wsAutoSaveButtonOff = \'%s\';',
				htmlspecialchars( Core::$wsConfig['autosave-btn-off'] ?? 'Autosave off' )
			);
		}

		if ( $showOnSelect ) {
			$formAttributes['class'] .= ' WSShowOnSelect flex-form-hide';
		}

		$formContent = $mwReturn . $action . $messageOnSuccess . $wikiComment . $extension . \Xml::tags(
				'input',
				[
					'type'  => 'hidden',
					'name'  => 'mwtoken',
					'value' => isset( $_SERVER['HTTP_HOST'] ) ? base64_encode(
						"wsform_" . $_SERVER['HTTP_HOST'] . "_" . time()
					) : base64_encode(
						"wsform_TERMINAL_" . time()
					)
				],
				''
			);

		if ( Core::isShowOnSelectActive() ) {
			$formContent .= Core::createHiddenField(
				'showonselect',
				'1'
			);
		}

		if ( Config::isSecure() ) {
			// FIXME: Move some of this logic to the caller
			Protect::setCrypt( Core::$checksumKey );
			$checksumName = Protect::encrypt( 'checksum' );

			if ( ! empty( Core::$chkSums ) ) {
				$checksumValue = Protect::encrypt( serialize( Core::$chkSums ) );

				$formContent .= \Xml::input(
					$checksumName,
					false,
					$checksumValue,
					[ 'type' => 'hidden' ] );

				// FIXME: Rename the field 'formid' to 'securityid'
				$formContent .= \Xml::input(
					'formid',
					false,
					Core::$securityId,
					[ 'type' => 'hidden' ] );
			}
		}

		if ( $javascript !== '' ) {
			Core::includeInlineScript( $javascript );
		}

		return \Xml::tags(
			'form',
			$formAttributes,
			$formContent . $input
		);
	}
}