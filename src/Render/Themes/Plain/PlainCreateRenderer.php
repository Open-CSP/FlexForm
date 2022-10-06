<?php

namespace FlexForm\Render\Themes\Plain;

use FlexForm\Core\Core;
use FlexForm\Render\Themes\CreateRenderer;

class PlainCreateRenderer implements CreateRenderer {
	/**
	 * @inheritDoc
	 */
	public function render_create(
		?string $follow,
		?string $template,
		?string $createId,
		?string $write,
		?string $slot,
		?string $option,
		?string $fields,
		bool $leadingZero,
		bool $noOverWrite,
		bool $skipSEO
	) : string {
		$template = $template !== null ? htmlspecialchars( $template ) : '';
		$createId = $createId !== null ? htmlspecialchars( $createId ) : '';
		$write    = $write !== null ? htmlspecialchars( $write ) : '';
		$slot     = $slot !== null ? htmlspecialchars( $slot ) : '';
		$option   = $option !== null ? htmlspecialchars( $option ) : '';
		$fields   = $fields !== null ? htmlspecialchars( $fields ) : '';

		if ( $leadingZero ) {
			$leadingZero  = "true";
		} else {
			$leadingZero = "false";
		}

		if ( $noOverWrite ) {
			$noOverWrite  = "true";
			$override = "false";
		} else {
			$override = "true";
		}

		if ( $skipSEO ) {
			$skipSEO = "true";
		} else {
			$skipSEO = "false";
		}

		if ( $follow !== null ) {
			$follow = $follow === '' || $follow === '1' ? 'true' : htmlspecialchars( $follow );
			$follow = Core::createHiddenField(
				'mwfollow',
				$follow
			);
		} else {
			$follow = '';
		}

		if ( $fields !== '' ) {
			// TODO: Support mwleadingzero with mwcreatemultiple
			$createValue = $template . Core::DIVIDER . $write . Core::DIVIDER;
			$createValue .= $option . Core::DIVIDER . $fields . Core::DIVIDER . $slot;
			$createValue .= Core::DIVIDER . $createId . Core::DIVIDER . $leadingZero;
			$createValue .= Core::DIVIDER . $override . Core::DIVIDER . $skipSEO;

			return Core::createHiddenField(
					'mwcreatemultiple[]',
					$createValue
				) . $follow;
		} else {
			if ( $template !== '' ) {
				$template = Core::createHiddenField(
					'mwtemplate',
					$template
				);
			}

			if ( $write !== '' ) {
				$write = Core::createHiddenField(
					'mwwrite',
					$write
				);
			}

			if ( $option !== '' ) {
				$option = Core::createHiddenField(
					'mwoption',
					$option
				);
			}

			if ( $slot !== '' ) {
				$slot = Core::createHiddenField(
					'mwslot',
					$slot
				);
			}

			$leadingZero = $leadingZero ? Core::createHiddenField(
				'mwleadingzero',
				'true'
			) : '';

			$noOverWrite = $noOverWrite ? Core::createHiddenField(
				'mwnooverwrite',
				'true'
			) : '';

			return $template . $write . $option . $follow . $leadingZero . $slot . $noOverWrite;
		}
	}
}