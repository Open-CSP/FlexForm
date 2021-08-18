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
use wsform\wsform;

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
		$instanceList = 'WSmultipleTemplateList';
		$instanceName = '';
		$template  = "";
		$instanceMainClass = "";
		$instanceAddButtonClass = "";
		$instanceRemoveButtonClass = "";
		$instanceMoveButtonClass = 'ws-formgroup-sortable';
		$instanceMoveListClass = 'ws-sortable-handle';
		$allowMove = true;

		if( isset( $args['buttons-position'] ) ) $template = $args['template'];

		if( isset( $args['template'] ) ) $template = $args['template'];
		if( isset( $args['name'] ) ) {
			$instanceName = $args['name'];
		}
		if( isset( $args['main-class'] ) ) $instanceMainClass = $args['main-class'];
		if( isset( $args['add-button-class'] ) ) $instanceAddButtonClass = $args['add-button-class'];
		if( isset( $args['remove-button-class'] ) ) $instanceRemoveButtonClass = $args['remove-button-class'];
		if( isset( $args['disable-move'] ) ) $allowMove = false;

		if( $allowMove ) {
			$instanceMoveListClass = ' ' . $instanceMoveListClass;
		} else $instanceMoveListClass = '';

		$ret .= '<div class="' . $classWrapper . '">' . PHP_EOL;

		$ret .= '<div class="hidden">' . PHP_EOL;

		//$ret .= '<div data-template="' . $template . '" class="hidden ' . $fieldClass . '">';

		$ret .= '<textarea rows="10" name="' . $instanceName . '"  class="hidden ' . $fieldClass . '" data-template="' . $template . '"></textarea>' . PHP_EOL;

		$ret .= '<div class="' . $mainClass . ' ' . $instanceMainClass . '">' . PHP_EOL;

		$ret .= $innerHtml;

		$ret .= '</div></div>';

		$ret .= PHP_EOL . '<div class="' . $instanceList . $instanceMoveListClass . '"></div>' . PHP_EOL . '</div>' . PHP_EOL;

		$instanceSettings = array(
			'draggable' => $allowMove,
			'addButtonClass' => "." . $instanceAddButtonClass,
			'removeButtonClass' => "." . $instanceRemoveButtonClass,
			'handleClass' => "." . trim( $instanceMoveListClass ),
			'selector' => "." . $classWrapper,
			'textarea' => "." . $fieldClass,
			'list' => "." . $instanceList,
			'copy' => "." . $mainClass
		);

		$out = \RequestContext::getMain()->getOutput();
		$out->addJsConfigVars( array( "wsinstance" => $instanceSettings ) );


		if( !wsform::isLoaded( 'wsinstance' ) ) {
			$js = 'wachtff( startInstance, true );';
			wsform::includeInlineScript( $js );
			wsform::addAsLoaded( 'wsinstance' );
		}

		return $ret;

	}

}