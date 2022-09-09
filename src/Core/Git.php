<?php

namespace FlexForm\Core;

use FlexForm\FlexFormException;

class Git {

	/**
	 * @var mixed Path to Git Repo
	 */
	private $gitPath = null;

	/**
	 * @param string $path
	 */
	public function __construct( string $path ) {
		$this->gitPath = $path;
	}

	public function isGitRepo(): bool {
		$cmd = 'rev-parse --show-toplevel';
		$result = $this->executeGitCmd( $cmd );
		if ( $this->checkResponseForError( $this->implodeResponse( $result['output'] ) ) !== "ok" ) {
			return false;
		}
		if ( $result['output'][0] !== $this->gitPath ) {
			return false;
		}
		return true;
	}

	/**
	 * @param array $response
	 *
	 * @return string
	 */
	public function implodeResponse( array $response ): string {
		return implode( '<br>', $response );
	}

	/**
	 * @param string $response
	 *
	 * @return string
	 */
	public function checkResponseForError( string $response ): string {
		if ( substr( $response, 0, 6 ) === 'error:' ) {
			return "error";
		}
		if ( substr( $response, 0, 6 ) === 'fatal:' ) {
			return "fatal";
		}
		return "ok";
	}

	/**
	 * @param string $cmd
	 *
	 * @return false|null[]
	 */
	public function executeGitCmd( string $cmd ) {
		if ( $this->gitPath === null ) {
			return false;
		}
		$cmd = "cd " . $this->gitPath . ' && git ' . $cmd . ' 2>&1';
		$output = null;
		$resultCode = null;
		exec( $cmd, $output, $resultCode );

		return [
			'exit_status'  => $resultCode,
			'output'       => $output
		];
	}

}