<?php

namespace FlexForm\Render;

use Composer\Command\ScriptAliasCommand;
use ExtensionRegistry;
use FlexForm\Processors\Files\FilesCore;
use FlexForm\Processors\Utilities\General;
use MediaWiki\MediaWikiServices;
use Parser;
use PPFrame;
use RequestContext;
use FlexForm\Core\Config;
use FlexForm\Core\Core;
use FlexForm\Core\Protect;
use FlexForm\Core\Validate;
use FlexForm\FlexFormException;
use User;

/**
 * Class TagHooks
 *
 * This class is responsible for rendering tags.
 */
class TagHooks {
	/**
	 * @var ThemeStore
	 */
	private $themeStore;

	/**
	 * TagHooks constructor.
	 *
	 * @param ThemeStore $themeStore The theme store to use
	 */
	public function __construct( ThemeStore $themeStore ) {
		$this->themeStore = $themeStore;
	}

	/**
	 * @brief Function to render the Form itself.
	 *
	 * This function will call its subfunction render_form()
	 * It will also add the JavaScript on the loadscript variable
	 * \n Additional parameters
	 * \li loadscript
	 * \li showmessages
	 * \li restrictions
	 * \li no_submit_on_return
	 * \li action
	 * \li changetrigger
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array|string send to the MediaWiki Parser or send to the MediaWiki Parser with the message not a valid
	 *     function
	 * @throws FlexFormException
	 */
	public function renderForm( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $wgUser, $wgEmailConfirmToEdit, $IP, $wgScript;
		$ret = '';

		//$parser->getOutput()->addModuleStyles( 'ext.wsForm.general.styles' );

		// Do we have some messages to show?
		if ( isset( $args['showmessages'] ) ) {
			if ( ! isset ( $_COOKIE['wsform'] ) ) {
				return '';
			}

			$alertTag = \Xml::tags(
				'div',
				[ 'class' => 'wsform alert-' . $_COOKIE['wsform']['type'] ],
				$_COOKIE['wsform']['txt']
			);

			setcookie(
				"wsform[type]",
				"",
				time() - 3600,
				'/'
			);
			setcookie(
				"wsform[txt]",
				"",
				time() - 3600,
				'/'
			);

			return [
				$alertTag,
				'noparse'    => true,
				'markerType' => 'nowiki'
			];
		}

		Core::$securityId = uniqid();
		Core::$chkSums = [];
		Core::includeTagsCSS( Core::getRealUrl() . '/Modules/ext.WSForm.css' );

		if ( isset( $args['messageonsuccess'] ) ) {
			$messageOnSuccess = $parser->recursiveTagParse(
				$args['messageonsuccess'],
				$frame
			);
			unset( $args['messageonsuccess'] );

			Core::includeInlineScript( 'var mwonsuccess = "' . htmlspecialchars( $messageOnSuccess ) . '";' );
		} else {
			$messageOnSuccess = null;
		}

		if ( isset( $args['setwikicomment'] ) ) {
			$wikiComment = $parser->recursiveTagParse(
				$args['setwikicomment'],
				$frame
			);
			unset( $args['setwikicomment'] );
		} else {
			$wikiComment = null;
		}

		if ( isset( $args['mwreturn'] ) ) {
			$mwReturn = self::tagParseIfNeeded( $args['mwreturn'], $parser, $frame );
			$mwReturn = Core::getMWReturn( $mwReturn );
			unset( $args['mwreturn'] );
		} else {
			$mwReturn = $parser->getTitle()->getLinkURL();
		}

		if ( isset( $args['formtarget'] ) ) {
			$formTarget = $args['formtarget'];
			unset( $args['formtarget'] );
		} else {
			$formTarget = null;
		}

		if ( isset( $args['action'] ) ) {
			$action = $parser->recursiveTagParse(
				$args['action'],
				$frame
			);
			unset( $args['action'] );
		} else {
			$action = null;
		}

		if ( isset( $args['extension'] ) ) {
			$extension = $parser->recursiveTagParse(
				$args['extension'],
				$frame
			);
			unset( $args['extension'] );
		} else {
			$extension = null;
		}

		if ( isset( $args['autosave'] ) ) {
			$autosaveType = $parser->recursiveTagParse(
				$args['autosave'],
				$frame
			);
			unset( $args['autosave'] );
		} else {
			$autosaveType = null;
		}

		if ( isset( $args['class'] ) ) {
			$additionalClass = $parser->recursiveTagParse(
				$args['class'],
				$frame
			);
			unset( $args['class'] );
		} else {
			$additionalClass = null;
		}

		if ( isset( $args['show-on-select'] ) && strtolower(
													 $parser->recursiveTagParse(
														 $args['show-on-select'],
														 $frame
													 )
												 ) === 'show-on-select' ) {
			$showOnSelect = true;
			unset( $args['show-on-select'] );

			Core::setShowOnSelectActive();
			$input = Core::checkForShowOnSelectValueAndType( $input );
		} else {
			$showOnSelect = false;
		}

		if ( isset( $args['id'] ) ) {
			$formId = $parser->recursiveTagParse(
				$args['id'],
				$frame
			);

			if ( ! $this->checkValidInput( $formId ) ) {
				return [ 'Invalid form ID.' ];
			}

			unset( $args['id'] );
		} else {
			// Generate a form ID for the user to use in JavaScript snippets and such
			$formId = bin2hex( random_bytes( 16 ) );
		}


		if ( isset( $args['recaptcha-v3-action'] ) ) {
			Core::$reCaptcha = $args['recaptcha-v3-action'];
			unset( $args['recaptcha-v3-action'] );
		}

		// Are there explicit 'restrictions' lifts set?
		// TODO: Allow administrators of a wiki to configure whether lifting restrictions is allowed (useful for public wikis)
		if ( isset( $args['restrictions'] ) ) {
			// Parse the given restriction
			$restrictions = $parser->recursiveTagParse(
				$args['restrictions'],
				$frame
			);

			// Only allow anonymous users if the restrictions are lifted
			$allowAnonymous = strtolower( $restrictions ) === 'lifted';

			unset( $args['restrictions'] );
		} else {
			// By default, deny anonymous users
			$allowAnonymous = false;
		}

		if ( isset( $args['changetrigger'] ) ) {
			$changeCall = $parser->recursiveTagParse(
				$args['changetrigger'],
				$frame
			);
			unset( $args['changetrigger'] );

			if ( $this->checkValidInput( $changeCall ) ) {
				Core::includeInlineScript( "$('#" . $formId . "').change(" . $changeCall . "(this));" );
			}
		}

		// Do we have scripts to load?
		if ( isset( $args['loadscript'] ) && $args['loadscript'] !== '' ) {
			$scriptToLoad = $parser->recursiveTagParse(
				$args['loadscript'],
				$frame
			);
			unset( $args['loadscript'] );

			// Validate the file name
			if ( ! $this->checkValidInput( $scriptToLoad ) ) {
				return [
					'The script specified in "loadscript" could not be loaded because the file name is invalid.',
					'noparse' => true
				];
			}

			// Is this script already loaded?
			if ( ! Core::isLoaded( $scriptToLoad ) ) {
				if ( ! file_exists(
					$IP . '/extensions/FlexForm/Modules/customJS/loadScripts/' . $scriptToLoad . '.js'
				) ) {
					return [ 'The script specified in "loadscript" could not be loaded because it does not exist.' ];
				}

				$scriptContent = @file_get_contents(
					$IP . '/extensions/FlexForm/Modules/customJS/loadScripts/' . $scriptToLoad . '.js'
				);

				if ( $scriptContent === false ) {
					return [ 'The script specified in "loadscript" could not be loaded because it is unreadable.' ];
				}

				if ( $formId !== null ) {
					Core::includeJavaScriptConfig(
						'wsForm_' . $scriptToLoad,
						$formId
					);
				}

				Core::includeInlineScript( $scriptContent );
				Core::addAsLoaded( $scriptToLoad );
			}
		}

		if ( isset( $args['no_submit_on_return'] ) ) {
			unset( $args['no_submit_on_return'] );
			if ( isset( $args['class'] ) ) {
				$args['class'] .= ' ff-nosubmit-onreturn';
			} else {
				$args['class'] = 'ff-nosubmit-onreturn';
			}
			//Core::includeJavaScriptConfig( 'noSubmit', $formId );
			if ( !Core::isLoaded( 'keypress' ) ) {
				$noEnter = <<<SCRIPT
                wachtff( noReturnOnEnter ); 
                SCRIPT;

				Core::includeInlineScript( $noEnter );
				Core::addAsLoaded( 'keypress' );
			}
		}

		$additionalArgs = [];
		foreach ( $args as $name => $argument ) {
			if ( Validate::validFormParameters( $name ) ) {
				$additionalArgs[$name] = $parser->recursiveTagParse(
					$argument,
					$frame
				);
			}
		}

		// Block the request if the user is not logged in and anonymous users are not allowed
		if ( $allowAnonymous === false && !$wgUser->isRegistered() ) {
			return wfMessage( "flexform-anonymous-user" )->parse();
		}

		// If the action is add to wiki, make sure the user has confirmed their email address
		if ( $action === 'addToWiki' && $wgEmailConfirmToEdit === true && $wgUser->isRegistered(
			) && ! $wgUser->isEmailConfirmed() ) {
			return wfMessage( "flexform-unverified-email1" )->parse() . wfMessage(
					"flexform-unverified-email2"
				)->parse();
		}

		if ( Core::getRun() === false ) {
			// FIXME: Move to ResourceLoader
			//Core::includeTagsScript( Core::getRealUrl() . '/Modules/FlexForm.general.js' );
			$ret     = '<script type="text/javascript" charset="UTF-8" src="' . Core::getRealUrl() . '/Modules/FlexForm.general.js"></script>' . "\n";

			Core::setRun( true );
		}

		$actionUrl = $formTarget ?? Core::getAPIurl();
		$output    = $parser->recursiveTagParse(
			trim( $input ),
			$frame
		);

		try {
			$previousTheme = $this->themeStore->getFormThemeName();

			if ( isset( $args['theme'] ) ) {
				$theme = $parser->recursiveTagParse(
					$args['theme'],
					$frame
				);

				try {
					$this->themeStore->setFormThemeName( $theme );
				} catch ( FlexFormException $exception ) {
					// Silently ignore and use the default theme
				}
			}

			// Render the actual contents of the form
			$ret .= $this->themeStore->getFormTheme()->getFormRenderer()->render_form(
				$output,
				$actionUrl,
				$mwReturn,
				$formId,
				$messageOnSuccess,
				$wikiComment,
				$action,
				$extension,
				$autosaveType,
				$additionalClass,
				$showOnSelect,
				$additionalArgs
			);
		} finally {
			$this->themeStore->setFormThemeName( $previousTheme );
		}

		if ( Core::isShowOnSelectActive() ) {
			$ret .= Core::addShowOnSelectJS();
		}

		if ( Core::$reCaptcha !== false && ! Core::isLoaded( 'google-captcha' ) ) {
			$captcha = Recaptcha::render();

			if ( $captcha !== false ) {
				Core::addAsLoaded( 'google-captcha' );
				$ret = $captcha . $ret;
			} else {
				return wfMessage( "flexform-captcha-missing-config" )->parse();
			}
		}

		if ( Core::$reCaptcha !== false ) {
			if ( $formId === null ) {
				return wfMessage( "flexform-recaptcha-no-form-id" )->parse();
			}

			if ( file_exists( $IP . '/extensions/FlexForm/Modules/recaptcha.js' ) ) {
				$rcaptcha = file_get_contents( $IP . '/extensions/FlexForm/Modules/recaptcha.js' );

				$replace = array(
					'%%id%%',
					'%%action%%',
					'%%sitekey%%',
				);

				$with = array(
					$formId,
					Core::$reCaptcha,
					Recaptcha::$rc_site_key
				);

				$rcaptcha = str_replace(
					$replace,
					$with,
					$rcaptcha
				);

				Core::includeInlineScript( $rcaptcha );
				Core::$reCaptcha = false;
			} else {

				return wfMessage( "flexform-recaptcha-no-js" )->parse();
			}
		}

		self::addInlineJavaScriptAndCSS();

		return [
			$ret,
			"markerType" => 'nowiki'
		];
	}

	/**
	 * @brief Function to render an input field.
	 *
	 * This function will look for the type of input field and will call its subfunction render_<inputfield>
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderField( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $IP;

		$args = $this->filterInputTags( $args );

		if ( !isset( $args['type'] ) ) {
			return [
				wfMessage( "flexform-field-invalid" )->parse(),
				"markerType" => 'nowiki'
			];
		}

		$fieldType = $args['type'];

		if ( !Validate::validInputTypes( $fieldType ) ) {
			return [
				wfMessage( "flexform-field-invalid" )->parse() . ": " . $fieldType,
				"markerType" => 'nowiki'
			];
		}

		if ( isset( $args['parsepost'] ) && isset( $args['name'] ) ) {
			// FIXME: What is this, and can it be removed?
			// CC: No it cannot!
			$parsePost = true;
			$parseName = $args['name'];

			unset( $args['parsepost'] );
		} else {
			$parsePost = false;
		}

		// We always parse the input, unless noparse is set.
		if ( ! isset( $args['noparse'] ) ) {
			$noParse = false;
			$input = $parser->recursiveTagParse(
				$input,
				$frame
			);
		} else {
			unset( $args['noparse'] );
			$noParse = true;
		}

		// Parse the arguments
		/* TODO: This is not a good solution. Sometimes arguments need to be parsed but not always.
		   TODO: Like you want to have {{Template:test}} inside the content of a page!
		*/
		foreach ( $args as $name => $value ) {
			$tempValue = $this->tagParseIfNeeded(
				$value,
				$parser,
				$frame
			);
			if ( $tempValue !== $value ) {
				// If we have had to parse the content, then make sure HTMLPurifier leaves it alone
				$args['html'] = 'all';
			}
			$args[$name] = $tempValue;
		}

		$renderer = $this->themeStore->getFormTheme()->getFieldRenderer();
		switch ( $fieldType ) {
			case 'text':
				if ( isset( $args['mwidentifier'] ) && $args['mwidentifier'] === 'datepicker' ) {
					$parser->getOutput()->addModules( 'ext.wsForm.datePicker.scripts' );
					$parser->getOutput()->addModuleStyles( 'ext.wsForm.datePicker.styles' );
				}

				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"text"
				);
				$ret               = $renderer->render_text( $preparedArguments );

				break;
			case 'hidden':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"hidden"
				);
				$ret               = $renderer->render_hidden( $preparedArguments );

				break;
			case 'secure':
				if ( ! Config::isSecure() ) {
					return [ wfMessage( 'flexform-field-secure-not-available' )->parse() ];
				}

				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"secure"
				);
				$ret               = $renderer->render_secure( $preparedArguments );

				break;
			case 'search':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"search"
				);
				$ret               = $renderer->render_search( $preparedArguments );

				break;
			case 'number':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"number"
				);
				$ret               = $renderer->render_number( $preparedArguments );

				break;
			case 'radio':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"radio"
				);
				$ret               = $renderer->render_radio(
					$preparedArguments,
					$args['show-on-checked'] ?? ''
				);
				break;
			case 'checkbox':

				// Added in v0.8.0.9.6.2. Allowing for a default value for a checkbox
				// for when the checkbox is not checked.
				$default = false;
				$defaultName = false;
				if ( isset( $args['default'] ) && $args['default'] !== '' ) {
					$default = $args['default'];
					$defaultName = false;
					if ( isset( $args['name'] ) ) {
						$defaultName = "wsdefault_" . $args['name'];
						if ( strpos( $defaultName, "[]" ) ) {
							$defaultName = rtrim( $defaultName, '[]' );
						}
					}
				}
				// END default checkbox

				$preparedArguments = Validate::doCheckboxParameters( $args );

				$ret = $renderer->render_checkbox(
					$preparedArguments,
					$args['show-on-checked'] ?? '',
					$args['show-on-unchecked'] ?? '',
					$default,
					$defaultName
				);

				break;
			case 'file':
				// TODO: Move most of the render_file logic to here
				// TODO: Can you do this @Charlot?
				$ret = trim( $renderer->render_file( $this->renderFileUpload( $args ) ) );

				break;
			case 'date':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"date"
				);
				$ret               = $renderer->render_date( $preparedArguments );

				break;
			case 'month':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"month"
				);
				$ret               = $renderer->render_month( $preparedArguments );

				break;
			case 'week':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"week"
				);
				$ret               = $renderer->render_week( $preparedArguments );

				break;
			case 'time':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"time"
				);
				$ret               = $renderer->render_time( $preparedArguments );

				break;
			case 'datetime':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"datetime"
				);
				$ret               = $renderer->render_datetime( $preparedArguments );

				break;
			case 'datetime-local':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"datetime-local"
				);
				$ret               = $renderer->render_datetimelocal( $preparedArguments );

				break;
			case 'password':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"password"
				);
				$ret               = $renderer->render_password( $preparedArguments );

				break;
			case 'email':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"email"
				);
				$ret               = $renderer->render_email( $preparedArguments );

				break;
			case 'color':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"color"
				);
				$ret               = $renderer->render_color( $preparedArguments );

				break;
			case 'range':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"range"
				);
				$ret               = $renderer->render_range( $preparedArguments );

				break;
			case 'image':
				$imageArguments = [];

				foreach ( $args as $name => $value ) {
					if ( Validate::validParameters( $name ) ) {
						continue;
					}

					$imageArguments[$name] = $value;

					Core::addCheckSum(
						"image",
						$name,
						$value
					);
				}

				$ret = $renderer->render_image( $args );

				break;
			case 'url':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"url"
				);
				$ret               = $renderer->render_url( $preparedArguments );

				break;
			case 'tel':
				$preparedArguments = Validate::doSimpleParameters(
					$args,
					"tel"
				);
				$ret               = $renderer->render_tel( $preparedArguments );

				break;
			case 'option':
				// Get the value for the option, or give an error if no value is set
				if ( isset( $args['value'] ) ) {
					$value = $args['value'];
					unset( $args['value'] );
				} else {
					return [ 'Missing value attribute.' ];
				}

				// Check whether show-on-select is enabled
				if ( isset( $args['show-on-select'] ) ) {
					$showOnSelect = $args['show-on-select'];
					unset( $args['show-on-select'] );
				} else {
					$showOnSelect = null;
				}

				// Check if a 'for' option is set, to determine whether the field should be rendered as 'selected'
				if ( isset( $args['for'] ) ) {
					$selectedParameterName = $args['for'];

					$selectedValues = $_GET[$selectedParameterName] ?? '';
					$selectedValues = explode(
						',',
						$selectedValues
					);
					$selectedValues = array_map(
						'trim',
						$selectedValues
					);

					$isSelected = in_array(
						$value,
						$selectedValues
					);

					unset( $args['for'] );
				} else {
					$isSelected = false;
				}

				$additionalArguments = [];
				foreach ( $args as $name => $v ) {
					if ( ! Validate::check_disable_readonly_required_selected(
							$name,
							$v
						) || ! Validate::validParameters( $name ) ) {
						$additionalArguments[$name] = $v;
					}
				}

				/* Input is already parse
				$ret = $renderer->render_option(
					$parser->recursiveTagParse(
						$input,
						$frame
					),
					$value,
					$showOnSelect,
					$isSelected,
					$additionalArguments
				);
				*/
				$ret = $renderer->render_option(
					$input,
					$value,
					$showOnSelect,
					$isSelected,
					$additionalArguments
				);

				break;
			case 'submit':
				$identifier     = false;
				$callBack       = 0;
				$beforeCallBack = 0;
				$validArgs      = [];
				$additionalHtml = '';
				foreach ( $args as $k => $v ) {
					switch ( strtolower( $k ) ) {
						case "mwidentifier" :
							if ( strtolower( $v ) === 'ajax' ) {
								$additionalHtml .= Core::createHiddenField(
									'mwidentifier',
									$v
								);
								if ( ! Core::isLoaded( 'wsform-ajax' ) ) {
									if ( file_exists( $IP . '/extensions/FlexForm/Modules/wsform-ajax.js' ) ) {
										Core::includeTagsScript( Core::getRealUrl() . '/Modules/wsform-ajax.js' );
										/*
										$additionalHtml .= '<script src="' . wfGetServerUrl(
												null
											) . '/extensions/FlexForm/Modules/wsform-ajax.js"></script>' . "\n";
										*/
										Core::addAsLoaded( 'wsform-ajax' );
									}
								}

								$identifier = true;
							}
							break;
						case "mwpausebeforerefresh":
							$additionalHtml .= '<input type="hidden" name="mwpause" value="' . $v . '">' . PHP_EOL;
							break;
						case "callback":
							$callBack = trim( $v );
							break;
						case "beforecallback":
							$beforeCallBack = trim( $v );
							break;
					}

					if ( Validate::validParameters( $k ) ) {
						$validArgs[$k] = $v;
					}
				}
				if ( $callBack !== 0 && $identifier === true ) {
					if ( ! Core::isLoaded( $callBack ) ) {
						if ( file_exists( $IP . '/extensions/FlexForm/Modules/customJS/' . $callBack . '.js' ) ) {
							$lf             = file_get_contents(
								$IP . '/extensions/FlexForm/Modules/customJS/' . $callBack . '.js'
							);
							$additionalHtml .= "<script>$lf</script>\n";
							Core::addAsLoaded( $callBack );
						}
					}
				}

				if ( $identifier ) {
					$validArgs['onclick'] = 'wsform(this,' . $callBack . ',' . $beforeCallBack . ');';
				}

				$output = $this->themeStore->getFormTheme()->getFieldRenderer()->render_submit(
					$validArgs,
					$identifier
				);

				return [
					$output . $additionalHtml,
					'markerType' => 'nowiki'
				];

				break;
			case 'button':
				if ( isset( $args['buttontype'] ) ) {
					$buttonType = $args['buttontype'];
					unset( $args['buttontype'] );
				} else {
					$buttonType = 'button';
				}

				$additionalArguments = [];
				foreach ( $args as $name => $value ) {
					if ( ! Validate::validButtonParameters( $name ) && ! Validate::validParameters( $name ) ) {
						continue;
					}
					$additionalArguments[$name] = $value;
				}

				$ret = $this->themeStore->getFormTheme()->getFieldRenderer()->render_button(
					$input,
					$buttonType,
					$additionalArguments
				);

				break;
			case 'reset':
				$additionalArguments = [];
				foreach ( $args as $name => $value ) {
					if ( ! Validate::validParameters( $name ) ) {
						continue;
					}

					$additionalArguments[$name] = $value;
				}

				$ret = $renderer->render_reset( $additionalArguments );

				break;
			case 'textarea':
				if ( isset( $args['value'] ) ) {
					// Use the value attribute as the input of the tag
					$input = $args['value'];
					unset( $args['value'] );
				}

				//FIXME: Where comes this from @Marijn?
				if ( isset( $args['name'] ) ) {
					$tagName = $args['name'];
					unset( $args['name'] );
				} else {
					//$tagName = bin2hex( random_bytes( 16 ) );
					$tagName = "";
				}

				if ( isset( $args['class'] ) ) {
					$class = $args['class'];
					unset( $args['class'] );
				} else {
					$class = null;
				}

				if ( isset( $args['editor'] ) ) {
					$editor = $args['editor'];
					unset( $args['editor'] );
				} else {
					$editor = null;
				}

				$additionalArguments = [];
				foreach ( $args as $name => $value ) {
					if ( ! Validate::validParameters( $name ) || Validate::check_disable_readonly_required_selected(
							$name,
							$value
						) ) {
						continue;
					}

					$additionalArguments[$name] = $value;
				}

				$htmlType = Validate::validHTML( $args );

				if ( $input !== '' ) {

					/*
					if ( $noParse === false ) {
						$input = $parser->recursiveTagParse(
							$input,
							$frame
						);
					}
					*/
					// We want to purify the input based on the form's HTML type
					//echo "<pre>";
					//var_dump( $input );
					$input = Protect::purify(
						$input,
						$htmlType,
						Config::isSecure()
					);
					//var_dump( $input );
					//echo "</pre>";
				} elseif ( Core::getValue( $tagName ) !== '' ) {
					// No input is given in the field, but we might have input through GET parameters
					$input = Protect::purify(
						Core::getValue( $tagName ),
						$htmlType,
						Config::isSecure()
					);
				}
				$ret = '';
				if ( isset( $editor ) && $editor === "ve" ) {
					if ( ExtensionRegistry::getInstance()->isLoaded( 'VEForAll' ) ) {
						$parser->getOutput()->addModules( 'ext.veforall.main' );
						$class .= ' load-editor ';
						$ret = '<span class="ve-area-wrapper">';
					}
				}

				Core::addCheckSum(
					'textarea',
					$tagName,
					$input,
					$htmlType
				);

				$ret .= $this->themeStore->getFormTheme()->getFieldRenderer()->render_textarea(
					$input,
					$tagName,
					$class,
					$editor,
					$additionalArguments,
					$htmlType
				);
				if ( isset( $editor ) && $editor === "ve" ) {
					if ( ExtensionRegistry::getInstance()->isLoaded( 'VEForAll' ) ) {
						$ret .= '</span>' . PHP_EOL;
						Core::includeInlineScript( 'var WSFormEditor = "VE";' );
						global $wgScript;
						$gifUrl = str_replace( '/index.php', '', $wgScript ) . '/extensions/FlexForm/Modules/load-editor.gif';
						$cssVE = '.load-editor{ 
								background: url("' . $gifUrl . '") no-repeat bottom right #fff;
								background-size: 50px; 
							}';
						Core::includeInlineCSS( $cssVE );
					}
				}






				break;
			case 'signature':
				if ( isset( $args['fname'] ) ) {
					$fileName = $args['fname'];
				} else {
					return [ 'Missing attribute "fname" for signature field.' ];
				}

				if ( isset( $args['pagecontent'] ) ) {
					$pageContent = $args['pagecontent'];
				} else {
					return [ 'Missing attribute "pagecontent" for signature field.' ];
				}

				$fileType         = $args['ftype'] ?? 'svg';
				$class            = $args['class'] ?? null;
				$clearButtonClass = $args['clearbuttonclass'] ?? null;
				$clearButtonText  = $args['clearbuttontext'] ?? 'Clear';
				$required         = isset( $args['required'] ) && $args['required'] === 'required';

				$javascriptOptions = [
					'syncField: "#wsform_signature_data"',
					'syncFormat: "' . htmlspecialchars( strtoupper( $fileType ) ) . '"'
				];

				if ( isset( $args['background'] ) ) {
					$javascriptOptions[] = sprintf(
						'background: "%s"',
						htmlspecialchars(
							$args['background'],
							ENT_QUOTES
						)
					);
				}

				if ( isset( $args['drawcolor'] ) ) {
					$javascriptOptions[] = sprintf(
						'color: "%s"',
						htmlspecialchars(
							$args['drawcolor'],
							ENT_QUOTES
						)
					);
				}

				if ( isset( $args['thickness'] ) ) {
					$javascriptOptions[] = sprintf(
						'thickness: "%s"',
						htmlspecialchars(
							$args['thickness'],
							ENT_QUOTES
						)
					);
				}

				if ( isset( $args['guideline'] ) && $args['guideline'] === 'true' ) {
					$javascriptOptions[] = sprintf( 'guideline: true' );

					if ( isset( $args['guidelineoffset'] ) ) {
						$javascriptOptions[] = sprintf(
							'guidelineOffset: "%s"',
							htmlspecialchars(
								$args['guidelineoffset'],
								ENT_QUOTES
							)
						);
					}

					if ( isset( $args['guidelineindent'] ) ) {
						$javascriptOptions[] = sprintf(
							'guidelineIndent: "%s"',
							htmlspecialchars(
								$args['guidelineindent'],
								ENT_QUOTES
							)
						);
					}

					if ( isset( $args['guidelinecolor'] ) ) {
						$javascriptOptions[] = sprintf(
							'guidelineColor: "%s"',
							htmlspecialchars(
								$args['guidelinecolor'],
								ENT_QUOTES
							)
						);
					}

					if ( isset( $args['notavailablemessage'] ) ) {
						$javascriptOptions[] = sprintf(
							'notAvailable: "%s"',
							htmlspecialchars(
								$args['notavailablemessage'],
								ENT_QUOTES
							)
						);
					}
				}

				$javascriptOptions = implode(
					',',
					$javascriptOptions
				);
				Core::includeInlineScript(
					<<<SCRIPT
                    function doWSformActions() {
                        $("#wsform-signature").signature({
                            $javascriptOptions
                        });
                        
                        $("#wsform_signature_clear").click(function() {
                            $("#wsform-signature").signature("clear");
                        });
                    }
                SCRIPT
				);

				if ( ! file_exists( $IP . '/extensions/FlexForm/Modules/signature/css/jquery.signature.css' ) ) {
					throw new FlexFormException( 'Missing jquery.signature.css' );
				}

				Core::includeInlineCSS(
					file_get_contents( $IP . '/extensions/FlexForm/Modules/signature/css/jquery.signature.css' )
				);

				$ret = '<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/south-street/jquery-ui.css" rel="stylesheet">';
				$ret .= '<script type="text/javascript" charset="UTF-8" src="/extensions/FlexForm/Modules/signature/js/do-signature.js"></script>';
				$ret .= \Xml::input( 'wsform_signature_filename',
									 false,
									 $fileName,
									 [ 'type' => 'hidden' ] );
				$ret .= \Xml::input( 'wsform_signature_type',
									 false,
									 $fileType,
									 [ 'type' => 'hidden' ] );
				$ret .= \Xml::input( 'wsform_signature_page_content',
									 false,
									 $pageContent,
									 [ 'type' => 'hidden' ] );

				$signatureDataAttributes = [
					'id'   => 'wsform_signature_data',
					'type' => 'hidden'
				];

				if ( $required ) {
					$signatureDataAttributes['required'] = 'required';
				}

				$ret .= \Xml::input(
					'wsform_signature',
					false,
					'',
					$signatureDataAttributes
				);
				$ret .= \Xml::tags(
					'div',
					[
						'id'    => 'wsform-signature',
						'class' => 'wsform-signature ' . $class ?? ''
					],
					''
				);
				$ret .= \Xml::tags(
					'button',
					[
						'type'  => 'button',
						'id'    => 'wsform_signature_clear',
						'class' => 'wsform-signature-clear ' . $clearButtonClass ?? ''
					],
					htmlspecialchars( $clearButtonText )
				);

				// TODO: Make this theme-able

				break;
			case 'mobilescreenshot': // TODO: Implement 'mobilescreenshot'
			default:
				return [ 'The field type "' . htmlspecialchars( $fieldType ) . '" is currently not supported.' ];
		}

		if ( $parsePost === true && isset( $parseName ) ) {
			$ret .= '<input type="hidden" name="wsparsepost[]" value="' . htmlspecialchars( $parseName ) . "\">\n";
		}

		self::addInlineJavaScriptAndCSS();

		return array(
			$ret,
			"markerType" => 'nowiki'
		);
	}

	/**
	 * @brief This is the initial call from the MediaWiki parser for the WSFieldset
	 *
	 * @param string $input Received from parser from begin till end
	 * @param array $args List of argmuments for the Fieldset
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderFieldset( $input, array $args, Parser $parser, PPFrame $frame ) {
		$input = $parser->recursiveTagParseFully(
			$input,
			$frame
		);
		$args = $this->filterInputTags( $args );

		foreach ( $args as $name => $value ) {
			if ( ( strpos(
					   $value,
					   '{'
				   ) !== false ) && ( strpos(
										  $value,
										  "}"
									  ) !== false ) ) {
				$args[$name] = $parser->recursiveTagParse(
					$value,
					$frame
				);
			}
		}

		$output = $this->themeStore->getFormTheme()->getFieldsetRenderer()->render_fieldset(
			$input,
			$args
		);

		return [
			$output,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief renderes the html legend (for use with fieldset)
	 *
	 * @param string $input Received from parser from begin till end
	 * @param array $args List of argmuments for the Legend
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderLegend( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( isset( $args['class'] ) ) {
			$class = $parser->recursiveTagParse(
					$args['class'],
					$frame
				) ?? '';
		} else {
			$class = '';
		}
		if ( isset( $args['align'] ) ) {
			$align = $parser->recursiveTagParse(
					$args['align'],
					$frame
				) ?? '';
		} else {
			$align = '';
		}
		$input = $parser->recursiveTagParse(
			$input,
			$frame
		);

		$output = $this->themeStore->getFormTheme()->getLegendRenderer()->render_legend(
			$input,
			$class,
			$align
		);

		return [
			$output,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @param string $content
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return string
	 */
	private function tagParseIfNeeded( $content, Parser $parser, PPFrame $frame ) {
		if ( ( strpos(
				   $content,
				   '{'
			   ) !== false ) && ( strpos(
									  $content,
									  '}'
								  ) !== false ) ) {
			return $parser->recursiveTagParse(
				$content,
				$frame
			);
		} else {
			return $content;
		}
	}

	/**
	 * @param $tags
	 *
	 * @return array
	 */
	private function filterInputTags( array $tags ): array {
		$skipped = [
			'src',
			'value'
		];
		if ( Config::isFilterTags() ) {
			foreach ( $tags as $k => $v ) {
				if ( !in_array( $k, $skipped ) && ( substr( $k, 0, 4 ) !== 'data' ) ) {
					$k        = Protect::purify(
						$k,
						'nohtml',
						true
					);
					$v        = Protect::purify(
						$v,
						'nohtml',
						true
					);
					$tags[$k] = $v;
				}
			}
		}
		return $tags;
	}

	/**
	 * @brief renders the html label
	 *
	 * @param string $input Received from parser from begin till end
	 * @param array $args List of arguments for a Label
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderLabel( $input, array $args, Parser $parser, PPFrame $frame ) {
		$args = $this->filterInputTags( $args );
		if ( isset( $args['for'] ) ) {
			$for = $args['for'];
			unset( $args['for'] );
		} else {
			// A label MUST have a for according to the HTML specification
			$for = '';
		}

		$inputArguments = [];
		foreach ( $args as $name => $value ) {
			if ( Validate::validParameters( $name ) ) {
				$inputArguments[$name] = $value;
			}
		}

		// We always parse the input, unless noparse is set.
		if ( ! isset( $args['noparse'] ) ) {
			$noParse = false;
			$input = $parser->recursiveTagParse(
				$input,
				$frame
			);
		} else {
			unset( $args['noparse'] );
			$noParse = true;
		}

		$output = $this->themeStore->getFormTheme()->getLabelRenderer()->render_label(
			$input,
			$for,
			$inputArguments
		);

		return [
			$output,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief This is the initial call from the MediaWiki parser for the WSSelect
	 *
	 * @param $input string Received from parser from begin till end
	 * @param array $args List of argmuments for the selectset
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderSelect( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( isset( $args['placeholder'] ) ) {
			$placeholder = $parser->recursiveTagParse(
				$args['placeholder'],
				$frame
			);
			unset( $args['placeholder'] );
		} else {
			$placeholder = null;
		}

		if ( isset( $args['selected'] ) ) {
			$selectedValues = explode(
				',',
				$args['selected']
			);
			$selectedValues = array_map(
				'trim',
				$selectedValues
			);
			unset( $args['selected'] );
		} else {
			$selectedValues = [];
		}

		if ( isset( $args['options'] ) ) {
			$options = explode(
				',',
				$args['options']
			);
			$options = array_map(
				'trim',
				$options
			);
			unset( $args['options'] );
		} else {
			$options = [];
		}

		$additionalArgs = [];
		foreach ( $args as $name => $value ) {
			if ( ! Validate::validParameters( $name ) ) {
				continue;
			}

			if ( $name === "name" && strpos(
										 $value,
										 '[]'
									 ) === false ) {
				$value .= '[]';
			}

			$additionalArgs[$name] = $parser->recursiveTagParse(
				$value,
				$frame
			);
		}

		$input  = $parser->recursiveTagParseFully(
			$input,
			$frame
		);
		$select = $this->themeStore->getFormTheme()->getSelectRenderer()->render_select(
			$input,
			$placeholder,
			$selectedValues,
			$options,
			$additionalArgs
		);

		return [
			$select,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief This is the initial call from the MediaWiki parser for the WSToken
	 *
	 * @param $input string Received from parser from begin till end
	 * @param array $args List of arguments for the Fieldset
	 * @param Parser $parser MediaWiki parser
	 * @param PPFrame $frame MediaWiki pframe
	 *
	 * @return array with full rendered html for the parser to add
	 * @throws FlexFormException
	 */
	public function renderToken( $input, array $args, Parser $parser, PPFrame $frame ) {
		$parsedInput = $parser->recursiveTagParseFully(
			$input,
			$frame
		);

		if ( isset( $args['placeholder'] ) ) {
			$placeholder = $parser->recursiveTagParse(
				$args['placeholder'],
				$frame
			);
			unset( $args['placeholder'] );
		} else {
			$placeholder = null;
		}

		if ( isset( $args['multiple'] ) ) {
			$multiple = $parser->recursiveTagParse(
					$args['multiple'],
					$frame
				) === "multiple";
			unset( $args['multiple'] );
		} else {
			$multiple = false;
		}

		if ( isset( $args['id'] ) ) {
			$id = $parser->recursiveTagParse(
				$args['id'],
				$frame
			);

			// Make sure ID is valid
			if ( ! $this->checkValidInput( $id ) ) {
				return [
					'Invalid ID as it does not match pattern',
					'noparse' => true
				];
			}

			unset( $args['id'] );
		} else {
			try {
				// Generate a random fallback ID
				$id = bin2hex( random_bytes( 16 ) );
			} catch ( \Exception $exception ) {
				return [
					'Could not get enough entropy to generate random ID',
					'noparse' => true
				];
			}
		}

		if ( isset( $args['input-length-trigger'] ) ) {
			$inputLengthTrigger = $parser->recursiveTagParse(
				$args['input-length-trigger'],
				$frame
			);
			$inputLengthTrigger = intval( trim( $inputLengthTrigger ) );
			unset( $args['input-length-trigger'] );
		} else {
			$inputLengthTrigger = 3;
		}

		if ( isset( $args['query'] ) ) {
			$smwQuery = $args['query'];
			unset( $args['query'] );
		} else {
			$smwQuery = null;
		}

		if ( isset( $args['json'] ) ) {
			$json = strpos(
				$args['json'],
				'semantic_ask'
			) ? $args['json'] : $parser->recursiveTagParse(
				$args['json'],
				$frame
			);
			unset( $args['json'] );
		} else {
			$json = null;
		}

		if ( isset( $args['callback'] ) ) {
			$callback = $parser->recursiveTagParse(
				$args['callback'],
				$frame
			);

			// Make sure callback is valid
			if ( ! $this->checkValidInput( $callback ) ) {
				return [
					'Invalid callback as it does not match pattern [a-zA-Z0-9_]+',
					'noparse' => true
				];
			}

			unset( $args['callback'] );
		} else {
			$callback = null;
		}

		if ( isset( $args['template'] ) ) {
			$template = $parser->recursiveTagParse(
				$args['template'],
				$frame
			);

			// Make sure template is valid
			if ( ! $this->checkValidInput( $template ) ) {
				return [
					'Invalid template as it does not match pattern [a-zA-Z0-9_ ]+',
					'noparse' => true
				];
			}

			unset( $args['template'] );
		} else {
			$template = null;
		}

		if ( isset( $args['allowtags'] ) ) {
			$allowTags = true;
			unset( $args['allowtags'] );
		} else {
			$allowTags = false;
		}

		if ( isset( $args['allowclear'] ) ) {
			$allowClear = true;
			unset( $args['allowclear'] );
		} else {
			$allowClear = false;
		}

		if ( isset( $args['allowsort'] ) ) {
			$allowSort = true;
			unset( $args['allowsort'] );
		} else {
			$allowSort = false;
		}

		$additionalArguments = [];

		foreach ( $args as $name => $value ) {
			if ( Validate::validParameters( $name ) ) {
				$additionalArguments[$name] = $parser->recursiveTagParse( $value, $frame );
			}
		}

		$output = $this->themeStore->getFormTheme()->getTokenRenderer()->render_token(
			$parsedInput,
			$id,
			$inputLengthTrigger,
			$placeholder,
			$smwQuery,
			$json,
			$callback,
			$template,
			$multiple,
			$allowTags,
			$allowClear,
			$allowSort,
			$additionalArguments
		);

		self::addInlineJavaScriptAndCSS();

		return [
			$output,
			"markerType" => 'nowiki'
		];
	}

	/**
	 * @param string $input
	 *
	 * @return bool
	 */
	private function checkValidInput( string $input ) : bool {
		if ( ! preg_match(
			'/[\w\-\:\.]+$/',
			$input
		) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @brief Function to render the Page Edit options.
	 *
	 * This function will call its subfunction render_edit()
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderEdit( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( ! isset( $args['target'] ) || $args['target'] === '' ) {
			return [
				'No valid target for edit',
				'noparse' => true
			];
		}

		$target = $parser->recursiveTagParse(
			$args['target'],
			$frame
		);

		if ( ! isset( $args['template'] ) && ! isset( $args['mwtemplate'] ) ) {
			return [
				'No valid template for edit',
				'noparse' => true
			];
		}

		$template = isset( $args['mwtemplate'] ) ? $args['mwtemplate'] : $args['template'];
		$template = str_replace(
			' ',
			'_',
			$parser->recursiveTagParse(
				$template,
				$frame
			)
		);

		if ( ! isset( $args['formfield'] ) || $args['formfield'] === '' ) {
			return [
				'No valid formfield for edit',
				'noparse' => true
			];
		}

		$formfield = $parser->recursiveTagParse(
			$args['formfield'],
			$frame
		);

		$usefield = isset( $args['usefield'] ) ? $parser->recursiveTagParse(
			$args['usefield'],
			$frame
		) : '';
		$slot     = isset( $args['mwslot'] ) ? $parser->recursiveTagParse(
			$args['mwslot'],
			$frame
		) : '';
		$value    = isset( $args['value'] ) ? $parser->recursiveTagParse(
			$args['value'],
			$frame
		) : '';

		$output = $this->themeStore->getFormTheme()->getEditRenderer()->render_edit(
			$target,
			$template,
			$formfield,
			$usefield,
			$slot,
			$value
		);

		return [
			$output,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief Function to render the Page Create options.
	 *
	 * This function will call its subfunction render_create()
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderCreate( $input, array $args, Parser $parser, PPFrame $frame ) {
		$template = isset( $args['mwtemplate'] ) ? $parser->recursiveTagParse(
			$args['mwtemplate'],
			$frame
		) : null;
		$createId = isset( $args['id'] ) ? $parser->recursiveTagParse(
			$args['id'],
			$frame
		) : null;
		$write    = isset( $args['mwwrite'] ) ? $parser->recursiveTagParse(
			$args['mwwrite'],
			$frame
		) : null;
		$slot     = isset( $args['mwslot'] ) ? $parser->recursiveTagParse(
			$args['mwslot'],
			$frame
		) : null;
		$option   = isset( $args['mwoption'] ) ? $parser->recursiveTagParse(
			$args['mwoption'],
			$frame
		) : null;
		$fields   = isset( $args['mwfields'] ) ? $parser->recursiveTagParse(
			$args['mwfields'],
			$frame
		) : null;
		$follow   = isset( $args['mwfollow'] ) ? $parser->recursiveTagParse(
			$args['mwfollow'],
			$frame
		) : null;

		$leadingZero = isset( $args['mwleadingzero'] );

		$noOverWrite = isset( $args['nooverwrite'] );

		if ( $fields !== null && $template === null ) {
			return [
				'No valid template for creating a page.',
				'noparse' => true
			];
		}

		if ( $fields !== null && $write === null ) {
			return [
				'No valid title for creating a page.',
				'noparse' => true
			];
		}

		$output = $this->themeStore->getFormTheme()->getCreateRenderer()->render_create(
			$follow,
			$template,
			$createId,
			$write,
			$slot,
			$option,
			$fields,
			$leadingZero,
			$noOverWrite
		);

		return [
			$output,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief Function to render the Page Create options.
	 *
	 * This function will call its subfunction render_create()
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser
	 * @throws FlexFormException
	 */
	public function renderCreateUser( $input, array $args, Parser $parser, PPFrame $frame ) {
		$canWedCreateUser = Config::getConfigVariable( 'can_create_user' );
		if ( $canWedCreateUser !== true ) {
			return [
				wfMessage( 'flexform-createuser-disabled' )->text(),
				'noparse' => true
			];
		}
		$username = isset( $args['username'] ) ? $parser->recursiveTagParse(
			$args['username'],
			$frame
		) : null;
		$emailAddress = isset( $args['email'] ) ? $parser->recursiveTagParse(
			$args['email'],
			$frame
		) : null;
		$realName = isset( $args['realname'] ) ? $parser->recursiveTagParse(
			$args['realname'],
			$frame
		) : null;
		//formFields

		if ( $username === null || $emailAddress === null ) {
			return [
				'No username or email address',
				'noparse' => true
			];
		}

		if ( !MediaWikiServices::getInstance()->getUserNameUtils()->isValid( $username ) ) {
			return [
				'Not a valid username according to MediaWiki',
				'noparse' => true
			];
		}

		$output = $this->themeStore->getFormTheme()->getCreateUserRenderer()->render_createUser(
			$username,
			$emailAddress,
			$realName
		);

		return [
			$output,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief Function to render the email options.
	 *
	 * This function will call its subfunction render_mail()
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser or
	 * @throws FlexFormException
	 */
	public function renderEmail( $input, array $args, Parser $parser, PPFrame $frame ) {
		$mailArguments = [];

		if ( isset( $args['to'] ) ) {
			$mailArguments["mwmailto"] = $parser->recursiveTagParse(
				$args['to'],
				$frame
			);
		}

		if ( isset( $args['from'] ) ) {
			$mailArguments["mwmailfrom"] = $parser->recursiveTagParse(
				$args['from'],
				$frame
			);
		}

		if ( isset( $args['cc'] ) ) {
			$mailArguments["mwmailcc"] = $parser->recursiveTagParse(
				$args['cc'],
				$frame
			);
		}

		if ( isset( $args['bcc'] ) ) {
			$mailArguments["mwmailbcc"] = $parser->recursiveTagParse(
				$args['bcc'],
				$frame
			);
		}

		if ( isset( $args['replyto'] ) ) {
			$mailArguments["mwmailreplyto"] = $parser->recursiveTagParse(
				$args['replyto'],
				$frame
			);
		}

		if ( isset( $args['subject'] ) ) {
			$mailArguments["mwmailsubject"] = $parser->recursiveTagParse(
				$args['subject'],
				$frame
			);
		}

		if ( isset( $args['type'] ) ) {
			$mailArguments["mwmailtype"] = $parser->recursiveTagParse(
				$args['type'],
				$frame
			);
		}

		if ( isset( $args['content'] ) ) {
			$mailArguments["mwmailcontent"] = $parser->recursiveTagParse(
				$args['content'],
				$frame
			);
		}

		if ( isset( $args['job'] ) ) {
			$mailArguments["mwmailjob"] = $parser->recursiveTagParse(
				$args['job'],
				$frame
			);
		}

		if ( isset( $args['header'] ) ) {
			$mailArguments["mwmailheader"] = $parser->recursiveTagParse(
				$args['header'],
				$frame
			);
		}

		if ( isset( $args['footer'] ) ) {
			$mailArguments["mwmailfooter"] = $parser->recursiveTagParse(
				$args['footer'],
				$frame
			);
		}

		if ( isset( $args['html'] ) ) {
			$mailArguments["mwmailhtml"] = $parser->recursiveTagParse(
				$args['html'],
				$frame
			);
		}

		if ( isset( $args['attachment'] ) ) {
			$mailArguments["mwmailattachment"] = $parser->recursiveTagParse(
				$args['attachment'],
				$frame
			);
		}

		if ( isset( $args['template'] ) ) {
			$mailArguments["mwmailtemplate"] = $parser->recursiveTagParse(
				$args['template'],
				$frame
			);
		}

		if ( isset( $args['parselast'] ) ) {
			$mailArguments["mwparselast"] = "true";
		}

		$base64content = base64_encode(
			$parser->recursiveTagParse(
				$input,
				$frame
			)
		);
		$output        = $this->themeStore->getFormTheme()->getEmailRenderer()->render_mail(
			$mailArguments,
			$base64content
		);

		return [
			$output,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * @brief Function to render a WSInstance.
	 *
	 * @param string $input Parser Between beginning and end
	 * @param array $args Arguments for the field
	 * @param Parser $parser MediaWiki Parser
	 * @param PPFrame $frame MediaWiki PPFrame
	 *
	 * @return array send to the MediaWiki Parser or
	 * @throws FlexFormException
	 */
	public function renderInstance( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $IP, $wgScript;

		// Add move, delete and add button with classes
		//$parser->getOutput()->addModuleStyles( 'ext.FlexForm.Instance.styles' );
		Core::includeTagsCSS( Core::getRealUrl() . '/Modules/instances/instance-style.css' );

		if ( ! Core::isLoaded( 'wsinstance-initiated' ) ) {
			Core::addAsLoaded( 'wsinstance-initiated' );
		}

		$content = $parser->recursiveTagParseFully(
			$input,
			$frame
		);

		if( isset( $args['default-content'] ) ) {
			//var_dump( "parsing content", $args['default-content'] );
			$args['default-content'] = $parser->recursiveTagParse(
				$args['default-content'],
				$frame
			);
		}

		// TODO: Move some of the logic from "render_instance" to here
		$ret = $this->themeStore->getFormTheme()->getInstanceRenderer()->render_instance(
			$content,
			$args
		);

		Core::removeAsLoaded( 'wsinstance-initiated' );

		if ( ! Core::isLoaded( 'multipleinstance' ) && file_exists(
				$IP . '/extensions/FlexForm/Modules/instances/wsInstance.js'
			) ) {
			Core::includeTagsScript( Core::getRealUrl() . '/Modules/instances/wsInstance.js' );
			/*
			$scriptPath = $realUrl . '/extensions/FlexForm/Modules/instances/wsInstance.js';
			$scriptTag  = \Xml::tags(
				'script',
				[
					'type'    => 'text/javascript',
					'charset' => 'UTF-8',
					'src'     => $scriptPath
				],
				''
			);
			*/

			//$ret = $scriptTag . $ret;

			Core::addAsLoaded( 'multipleinstance' );
		}

		return [
			$ret,
			'noparse'    => true,
			'markerType' => 'nowiki'
		];
	}

	/**
	 * Helper function to add the currently configured inline JavaScript and CSS to the OutputPage.
	 *
	 * @param bool $parentConfig
	 */
	private function addInlineJavaScriptAndCSS( $parentConfig = false ) {
		return;
		$scripts   = array_unique( Core::getJavaScriptToBeIncluded() );
		$csss      = array_unique( Core::getCSSToBeIncluded() );
		$jsconfigs = Core::getJavaScriptConfigToBeAdded();
		$out       = RequestContext::getMain()->getOutput();

		if ( ! empty( $scripts ) ) {
			foreach ( $scripts as $js ) {
				$out->addInlineScript( $js );
			}

			Core::cleanJavaScriptList();
		}

		if ( ! empty( $csss ) ) {
			foreach ( $csss as $css ) {
				$out->addInlineStyle( $css );
			}

			Core::cleanCSSList();
		}

		if ( ! empty( $jsconfigs ) ) {
			if ( $parentConfig ) {
				$out->addJsConfigVars( array( $jsconfigs ) );
			} else {
				$out->addJsConfigVars( array( 'wsformConfigVars' => $jsconfigs ) );
			}

			Core::cleanJavaScriptConfigVars();
		}
	}

	/**
	 * @param array $args
	 *
	 * @return array|string
	 */
	private function renderFileUpload( array $args ) {
		// FIXME: Can you (attempt to) rewrite this @Charlot?

		$filesCore = new FilesCore();

		$result             = [];
		$ret                = '<input type="file" ';
		$br                 = "\n";
		$attributes         = [];
		$hiddenFiles        = [];
		$attributes['name'] = FilesCore::FILENAME . '[]';
		$id                 = false;
		$target             = false;
		$drop               = false;
		$verbose_id         = false;
		$error_id           = false;
		$comment            = false;
		$presentor          = false; // Holds name of external presentor, e.g. Slim
		$pagecontent        = "";
		$use_label          = false;
		$force              = false;
		$parseContent       = false;
		$canvasSourceId     = false;
		$canvasRenderId     = uniqid();
		$canvasDiv			= '';
		foreach ( $args as $k => $v ) {
			if ( validate::validParameters( $k ) || validate::validFileParameters( $k ) ) {
				// going through specific extra's.
				switch ( $k ) {
					case "presentor":
						$presentor = $v;
						break;
					case "pagecontent" :
						$pagecontent = $v;
						break;
					case "parsecontent" :
						$parseContent = true;
						break;
					case "dropzone" :
						$drop = true;
						break;
					case "comment" :
						$comment = $v;
						break;
					case "target":
						$target = $v;
						break;
					case "use_label":
						$use_label = true;
						break;
					case "force":
						$force = $v;
						break;
					case "id":
						$id               = $v;
						$attributes['id'] = $v;
						break;
					case "name":
						break;
					case "verbose_id":
						$verbose_id = $v;
						break;
					case "error_id":
						$error_id = $v;
						break;
					case "canvas_source_id":
						$canvasSourceId = $v;
						break;
					case "canvas_render_id":
						$canvasRenderId = $v;
						break;
					default:
						$attributes[$k] = $v;
				}
			}
		}
		global $IP;
		if ( ! $id ) {
			$ret = 'You cannot upload files without adding an unique id.';

			return $ret;
		}
		if ( ! $target ) {
			$ret = 'You cannot upload files without a target.';

			return $ret;
		} else {
			//$hiddenFiles[] = '<input type="hidden" name="wsform_file_target" value="' . $target . '">';
			$hiddenFiles[] = Core::createHiddenField( "wsform_file_target", $target );
		}
		if ( $pagecontent ) {
			//$hiddenFiles[] = '<input type="hidden" name="wsform_page_content" value="' . $pagecontent . '">';
			$hiddenFiles[] = Core::createHiddenField( "wsform_page_content", $pagecontent );
		}
		if ( $comment ) {
			$hiddenFiles[] = '<input type="hidden" name="wsform-upload-comment" value="' . $comment . '">';
		}
		if ( $parseContent ) {
			$hiddenFiles[] = '<input type="hidden" name="wsform_parse_content" value="true">';
		}
		if ( $force ) {
			$hiddenFiles[] = '<input type="hidden" name="wsform_image_force" value="' . $force . '">';
		}

		if ( ! $presentor ) {
			if ( $verbose_id === false ) {
				$verbose_id       = 'verbose_' . $id;
				$verboseDiv['id'] = $verbose_id;
				if ( $drop && ! $use_label ) {
					$verboseDiv['class'][] = 'wsform-dropzone';
				}
				$verboseDiv['class'][] = 'wsform-verbose';
				// $ret .= '<div id="' . $verbose_id . '" class="wsform-verbose"></div>';
			} else {
				$verboseDiv['id']    = false;
				$verboseDiv['class'] = false;
			}

			if ( ! $error_id ) {
				$error_id          = 'error_' . $id;
				$errorDiv['id']    = $error_id;
				$errorDiv['class'] = [ "wsform-error" ];
				//$ret      .= '<div id="' . $error_id . '" class="wsform-error"></div>';
			} else {
				$errorDiv['id']    = false;
				$errorDiv['class'] = false;
			}
			$random         = round( microtime( true ) * 1000 );
			$onChangeScript = 'function WSFile' . $random . '(){' . "\n" . '$("#' . $id . '").on("change", function(){' . "\n" . 'wsfiles( "';
			$onChangeScript .= $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label;
			$onChangeScript .= '");' . "\n" . '});' . "\n";
			if ( $drop && ! $use_label ) {
				$onChangeScript .= "\n" . '$("#' . $verbose_id . '").on("dragleave", function(e) {
				event.preventDefault();
    			$(".br_dropzone").removeClass("dragover");
    			})';
				$onChangeScript .= "\n" . '$("#' . $verbose_id . '").on("dragover drop", function(e) { 
				e.preventDefault();  
				$("#' . $verbose_id . '").addClass("dragover")
			}).on("drop", function(e) {
				
				$("#' . $id . '").prop("files", e.originalEvent.dataTransfer.files)
				$("#' . $id . '").trigger("change"); 
		});';
			}
			if ( $drop && $use_label ) {
				$onChangeScript .= "\n";
				$onChangeScript .= 'var label = $("label[for=\'' . $id . '\']");';
				$onChangeScript .= "\n" . 'label.on("dragover drop", function(e) { 
				e.preventDefault();  
				$("#' . $id . '").addClass("dragover")
			}).on("drop", function(e) {
				
				$("#' . $id . '").prop("files", e.originalEvent.dataTransfer.files)
				$("#' . $id . '").trigger("change"); 
		});';
			}
			$onChangeScript .= '};';
			$jsChange       = $onChangeScript . "\n";
			//$ret .= "<script>\n" . $onChangeScript . "\n";
			$jsChange .= "\n" . "wachtff(WSFile" . $random . ");\n";
			Core::includeInlineScript( $jsChange );
			//$ret     .= '<script>$( document ).ready(function() { $("#' . $random . '").on("change", function(){ wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '", "' . $verbose_custom . '", "' . $error_custom . '");});});</script>';
			$css     = file_get_contents( "$IP/extensions/FlexForm/Modules/WSForm_upload.css" );
			$replace = array(
				'{{verboseid}}',
				'{{errorid}}',
				'<style>',
				'</style>'
			);
			$with    = array(
				$verbose_id,
				$error_id,
				'',
				''
			); //wsfiles( "file-upload2", "hiddendiv2", "error_file-upload2", "", "yes", "none");
			$css     = str_replace(
				$replace,
				$with,
				$css
			);
			Core::includeInlineCSS( $css );
			//$ret     .= $css;
			if ( ! Core::isLoaded( 'WSFORM_upload.js' ) ) {
				Core::addAsLoaded( 'WSFORM_upload.js' );
				Core::includeTagsScript( Core::getRealUrl() . '/Modules/WSForm_upload.js' );
				//$js = file_get_contents( "$IP/extensions/FlexForm/Modules/WSForm_upload.js" );
				$js = '';
				//Core::includeInlineScript( $js );
			} else {
				$js = '';
			}
			// As of MW 1.35+ we get errors here. It's replacing spaces with &#160; So now we put the js in the header
			//echo "\n<script>" . $js . "</script>";

			$js           = "";
			$wsFileScript = "\nfunction wsfilesFunc" . $random . "(){\n";
			$wsFileScript .= "\n" . 'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");' . "\n";
			$wsFileScript .= "}\n";
			//$ret .= '<script>'. "\n".'wsfiles( "' . $id . '", "' . $verbose_id . '", "' . $error_id . '", "' . $use_label . '");</script>';

			Core::includeInlineScript( "\n" . $wsFileScript . "\n" . 'wachtff(wsfilesFunc' . $random . ');' );
		} elseif ( $presentor == "slim" ) {
			/*
			if ( $slim_image !== false ) {
				$slim_image = '<img src="' . $slim_image . '">';
			} else {
				$slim_image = "";
			}
			$ret = $slim . $ret . $slim_image . "</div>$br";

			// TODO: Move this logic to the caller
			$parser->getOutput()->addModuleStyles( 'ext.wsForm.slim.styles' );
			$parser->getOutput()->addModules( 'ext.wsForm.slim.scripts' );
			*/
		} elseif ( $presentor === "canvas" ) {
			if ( !$canvasSourceId || !$canvasRenderId ) {
				return "Missing canvas_source_id and/or canvas_render_id";
			}
			$verboseDiv = '';
			$errorDiv = '';
			if ( ! Core::isLoaded( 'WSFORM_upload.js' ) ) {
				Core::addAsLoaded( 'WSFORM_upload.js' );
				Core::includeTagsScript( Core::getRealUrl() . '/Modules/WSForm_upload.js' );
			}
			if ( ! Core::isLoaded( 'htmltocanvas' ) ) {
				Core::addAsLoaded( 'htmltocanvas' );
				Core::includeTagsScript( Core::getRealUrl() . '/Modules/htmlToCanvas/html2canvas.min.js' );
			}
			$canvasDiv = '<div style="display:none;" data-canvas-source="' . $canvasSourceId . '" id="canvas_' . $canvasRenderId . '"></div>';
		}
		$result['verbose_div']     = $verboseDiv;
		$result['error_div']       = $errorDiv;
		$result['attributes']      = $attributes;
		$result['function_fields'] = $hiddenFiles;
		$result['canvas']          = $canvasDiv;

		return $result;
	}
}