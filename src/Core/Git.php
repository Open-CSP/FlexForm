<?php

namespace FlexForm\Core;

/**
 * Class to make simple git commands. Used on src/SpecialFlexForm.php
 */
class Git {

	/**
	 * @var string|null Path to git repo
	 */
	private ?string $gitPath = null;

	/**
	 * @param string $path
	 */
	public function __construct( string $path ) {
		$realPath = realpath( $path );
		if ( $realPath !== false && is_dir( $realPath ) ) {
			$this->gitPath = $realPath;
		}
	}

	/**
	 * Check if the path is a Git repo and not part of top level repo
	 * @return bool
	 */
	public function isGitRepo(): bool {
		$cmd = 'rev-parse --show-toplevel';
		$result = $this->executeGitCmd( $cmd );
		if ( $result === false || empty( $result['output'] ) ) {
			return false;
		}
		if ( $this->checkResponseForError( $this->implodeResponse( $result['output'] ) ) !== "ok" ) {
			return false;
		}
		if ( ( $result['output'][0] ?? '' ) !== $this->gitPath ) {
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
		if ( str_starts_with( $response, 'error:' ) ) {
			return "error";
		}
		if ( str_starts_with( $response, 'fatal:' ) ) {
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
		$fullCmd = 'git -C ' . escapeshellarg( $this->gitPath ) . ' ' . $cmd . ' 2>&1';
		$output = [];
		$resultCode = 0;
		exec( $fullCmd, $output, $resultCode );

		return [
			'exit_status'  => $resultCode,
			'output'       => $output
		];
	}

}