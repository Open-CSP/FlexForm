<?php
/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : HandlerInterface.php
 * Description :
 * Date        : 30-3-2022
 * Time        : 20:53
 */

namespace FlexForm\Modules\Handlers;

interface HandlerInterface {
	/**
	 * @param array $flexFormFields
	 *
	 * @return mixed
	 */
	public function execute( array $flexFormFields );
}