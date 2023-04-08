<?php

namespace FlexForm\Modules\Handlers;

use FlexForm\Core\HandleResponse;
use FlexForm\FlexFormException;

/**
 * Created by  : Designburo.nl
 * Project     : MWWSForm
 * Filename    : HandlerInterface.php
 * Description :
 * Date        : 30-3-2022
 * Time        : 20:53
 */

/*
 * Usage HandleResponse:
 *
 * throw a new FlexFormException on error
 * on success with no further output needed :
 * $responseHandler->setReturnType( HandleResponse::TYPE_SUCCESS );
 * end return $responseHandler
 *
 * on success with an additional message :
 *
 * $responseHandler->setReturnType( HandleResponse::TYPE_SUCCESS );
 * $responseHandler->setReturnData( string <your message> );
 * return $responsehandler
 */

interface HandlerInterface {
	/**
	 * @param array $flexFormFields
	 * @param array|null $config
	 * @param HandleResponse $responseHandler
	 *
	 * @return HandleResponse
	 * @throws FlexFormException
	 */
	public function execute( array $flexFormFields, ?array $config, HandleResponse $responseHandler ): HandleResponse;
}