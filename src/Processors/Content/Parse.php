<?php

namespace FlexForm\Processors\Content;

class Parse {
	/**
	 * @param string $pageContent
	 *
	 * @return array|string
	 */
	public function parseArticle( string $pageContent, $returnSourceOnly = false ) {
		$templateSources = $this->findTemplates( $pageContent );
		if ( $returnSourceOnly ) {
			return $templateSources;
		}
		$templates = $this->parseTemplates( $templateSources );

		return $templates;
	}

	/**
	 * Finds all the templates on a page. This function takes nested templates into account.
	 *
	 * @param string $articleSource
	 *
	 * @return array
	 */
	protected function findTemplates( string $articleSource ) : array {
		$templateSources = [];

		if ( version_compare( PHP_VERSION,
				"7.4" ) >= 0 ) {
			$characters = mb_str_split( $articleSource );
		} else {
			$characters = str_split( $articleSource );
		}

		$templateSource = '';
		$openBrackets = 0;

		for ( $idx = 0; $idx < count( $characters ); $idx++ ) {
			$currentCharacter = $characters[$idx];
			$nextCharacter = isset( $characters[$idx + 1] ) ? $characters[$idx + 1] : "\0";

			if ( $currentCharacter === "{" && $nextCharacter === "{" ) {
				$openBrackets++;
				$idx++;

				// Add the "{" we skipped
				$templateSource .= $currentCharacter;
			} else {
				if ( $currentCharacter === "}" && $nextCharacter === "}" ) {
					$openBrackets--;
					$idx++;

					// Add the "}" we skipped
					$templateSource .= $currentCharacter;
				}
			}

			if ( $openBrackets > 0 ) {
				$templateSource .= $currentCharacter;
			}

			if ( $openBrackets === 0 && strlen( $templateSource ) > 0 ) {
				// We are done parsing a template
				$templateSource .= $currentCharacter;

				if ( $this->isValidTemplate( $templateSource ) ) {
					array_push( $templateSources, $templateSource );
				}

				$templateSource = '';
			}
		}

		return $templateSources;
	}

	/**
	 * Check if this is a valid template.
	 *
	 * @param string $templateSource
	 *
	 * @return bool
	 */
	private function isValidTemplate( string $templateSource ) : bool {
		if ( strlen( $templateSource ) < 5 ) {
			return false;
		}

		if ( $templateSource[0] !== '{' || $templateSource[1] !== '{' ) {
			return false;
		}

		if ( $templateSource[strlen( $templateSource ) - 1] !== '}' || $templateSource[strlen( $templateSource ) - 2] !== '}' ) {
			return false;
		}

		if ( isset( $templateSource[2] ) && $templateSource[2] === "#" ) {
			return false;
		}

		return true;
	}

	/**
	 * Parses the given template sources.
	 *
	 * @param array $templateSources
	 *
	 * @return array
	 */
	protected function parseTemplates( array $templateSources ) : array {
		$templates = [];

		foreach ( $templateSources as $template ) {
			list( $name, $arguments ) = $this->parseTemplate( $template );
			$templates[$name] = $arguments;
		}

		return $templates;
	}

	/**
	 * Parses a single template. It first removes the accolades from the template, then splits the template by
	 * arguments.
	 *
	 * @param string $template
	 *
	 * @return array
	 */
	protected function parseTemplate( string $template ) : array {
		// Reset the anonymous argument pointer
		$this->anonymousArgumentPointer = 1;

		$template = substr( $template,
			2 );
		$template = substr( $template,
			0,
			-2 );

		$templateParts = $this->tokenizeTemplate( $template );
		$templateName = trim( array_shift( $templateParts ) );
		$templateArguments = [];

		foreach ( $templateParts as $argument ) {
			list( $name, $value ) = $this->parseArgument( $argument );
			$templateArguments[$name] = $value;
		}

		return [ $templateName,
			$templateArguments ];
	}

	/**
	 * Parses a template and splits it based on arguments, while respecting (nesting) multiple-instance templates.
	 *
	 * @param string $template
	 *
	 * @return array
	 */
	protected function tokenizeTemplate( string $template ) : array {
		$template = str_split( $template );
		$arguments = [];

		$buffer = '';
		$nestingDepth = 0;

		foreach ( $template as $index => $char ) {
			if ( $char === "{" && $template[$index + 1] === "{" ) { // Check if a template starts
				$nestingDepth++;
			} else {
				if ( $nestingDepth > 0 && $char === "}" && $template[$index + 1] === "}" ) {
					// Check if a template ends
					$nestingDepth--;
				} else {
					if ( $nestingDepth === 0 && $char === "|" ) {
						$arguments[] = $buffer;
						$buffer = '';

						continue;
					}
				}
			}

			$buffer .= $char;
		}

		$arguments[] = $buffer;

		return $arguments;
	}

	/**
	 * Parses a template argument.
	 *
	 * @param string $argument
	 *
	 * @return array
	 */
	protected function parseArgument( string $argument ) : array {
		$parts = explode( "=",
			$argument,
			2 );

		if ( substr( $argument,
				-1 ) === "=" ) {
			trim( $parts[0],
				"=" );
			$parts[1] = ""; // Empty value named argument
		}

		if ( count( $parts ) === 1 ) {
			// Anonymous argument
			return [ strval( $this->anonymousArgumentPointer++ ),
				trim( $parts[0] ) ];
		}

		// Named argument
		$argument_name = trim( array_shift( $parts ) );
		$argument_value = trim( implode( "=",
			$parts ) );

		return [ $argument_name,
			$argument_value ];
	}
}