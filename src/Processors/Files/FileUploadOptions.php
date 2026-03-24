<?php

namespace FlexForm\Processors\Files;

use FlexForm\Processors\Utilities\General;


class FileUploadOptions {

	/**
	 * @var mixed
	 */
	public mixed $target;

	/**
	 * @var mixed
	 */
	public mixed $pageContent;

	/**
	 * @var mixed
	 */
	public mixed $pageContentPrefix;

	/**
	 * @var mixed
	 */
	public string|bool $pageContentSuffix;

	/**
	 * @var mixed
	 */
	public mixed $pageTemplate;

	/**
	 * @var mixed
	 */
	public mixed $parseContent;

	/**
	 * @var mixed
	 */
	public mixed $imageForce;

	/**
	 * @var mixed
	 */
	public mixed $imageComment;

	/**
	 * @var mixed
	 */
	public mixed $fileAction;

	/**
	 * @param array $fileDetails
	 */
	public function __construct( array $fileDetails ) {
		$this->target            = General::getJsonValue( 'wsform_file_target', $fileDetails );
		$this->pageContent       = General::getJsonValue( 'wsform_page_content', $fileDetails );
		$this->pageContentPrefix = General::getJsonValue( 'wsform_pandoc_prefix', $fileDetails );
		$this->pageContentSuffix = General::getJsonValue( 'wsform_pandoc_suffix', $fileDetails );
		$this->pageTemplate      = General::getJsonValue( 'wsform_file_template', $fileDetails );
		$this->parseContent      = General::getJsonValue( 'wsform_parse_content', $fileDetails );
		$this->imageForce        = General::getJsonValue( 'wsform_image_force', $fileDetails );
		$this->imageComment      = General::getJsonValue( 'wsform-upload-comment', $fileDetails );
		$this->fileAction        = General::getJsonValue( 'wsform_action', $fileDetails );
	}
}