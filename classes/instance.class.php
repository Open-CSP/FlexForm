<?php
/**
 * Created by  : Designburo.nl
 * Project     : csp
 * Filename    : instance.class.php
 * Description :
 * Date        : 6-8-2021
 * Time        : 08:44
 */

namespace wsform\instance;

use wsform\validate\validate;

class render {

	/**
	 * Render WSInstance
	 *
	 * @param $args
	 * @param string $innerHtml
	 *
	 * @return string Formatted HTML
	 */
	public static function render_instance( $args, $innerHtml ) {
		$ret = '';
		$classWrapper = "WSmultipleTemplateWrapper";
		$mainClass = "WSmultipleTemplateMain";
		$fieldClass = 'WSmultipleTemplateField';
		$instanceName = '';
		$template  = "";
		$instanceId = "";
		$instanceMainClass = "";
		$instanceAddButtonClass = "";
		$instanceRemoveButtonClass = "";
		$allowMove = true;

		if( isset( $args['template'] ) ) $template = $args['template'];
		if( isset( $args['name'] ) ) {
			$instanceName = $args['name'];
			$instanceId = str_replace(' ', '_', $instanceName );
		}
		if( isset( $args['main-class'] ) ) $instanceMainClass = $args['main-class'];
		if( isset( $args['add-button-class'] ) ) $instanceAddButtonClass = $args['add-button-class'];
		if( isset( $args['remove-button-class'] ) ) $instanceRemoveButtonClass = $args['remove-button-class'];
		if( isset( $args['disable-move'] ) ) $allowMove = false;

		$ret .= '<div class="' . $classWrapper . ' hidden" data-id="' . $instanceId . '>' . PHP_EOL;

		$ret .= '<div id="' . $instanceId . '" data-template=' . $template . '" class="' . $fieldClass . '">';

		$ret .= '<textarea rows="10" name="' . $instanceName . '"></textarea>' . PHP_EOL . '</div>' . PHP_EOL;

		$ret .= '<div class="hidden">' . PHP_EOL;

		$ret .= 'div class="' . $mainClass . ' ' . $instanceMainClass . '">' . PHP_EOL;

		$ret .= $innerHtml;

		$ret .= '</div></div></div>';

		return $ret;

	}

}