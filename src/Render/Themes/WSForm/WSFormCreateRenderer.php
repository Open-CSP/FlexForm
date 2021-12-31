<?php

namespace WSForm\Render\Themes\WSForm;

use WSForm\Core\Core;
use WSForm\Render\Themes\CreateRenderer;

class WSFormCreateRenderer implements CreateRenderer {
    /**
     * @inheritDoc
     */
    public function render_create( array $args ): string {
        $args = array_map( "htmlspecialchars", $args );

        $template = "";
        $wswrite  = "";
        $wsoption = "";
        $wsfields = "";
        $wsfollow = "";
        $wsSlot   = "";
        $wsCreateId = '';

        if(isset($args['mwfields']) && $args['mwfields'] != '') {
            $div = '-^^-';
            foreach ( $args as $name => $value ) {
                if ( $name == "mwtemplate" ) {
                    $template = $value;
                }

                if( $name == 'id' ){
					$wsCreateId = $value;
				}

                if ( $name == "mwwrite" ) {
                    $wswrite = $value;
                }

				if ( $name == "mwslot" ) {
					$wsSlot = $value;
				}

                if ( $name == "mwoption" ) {
                    $wsoption = $value;
                }

                if ( $name == "mwfields" ) {
                    $wsfields = $value;
                }
	            if ( $name === "mwfollow" ) {
		            $fname = 'mwfollow';

		            if( strlen( $value ) <= 1 ) {
			            $fvalue = "true";
		            } else {
			            $fvalue = $value;
		            }
					$wsfollow = Core::createHiddenField( $fname, $fvalue );
	            }

            }
            if($template === '') {
                return 'No valid template for creating a page.';
            }
            if($wswrite === '') {
                return 'No valid title for creating a page.';
            }

            $createValue =
				$template . $div .
				$wswrite . $div .
				$wsoption . $div .
				$wsfields . $div .
				$wsSlot . $div .
				$wsCreateId;
	        $def = Core::createHiddenField( 'mwcreatemultiple[]', $createValue );

            return $def.$wsfollow ;
        } else {

            foreach ( $args as $name => $value ) {
                if ( $name == "mwtemplate" ) {
                	$template = Core::createHiddenField( 'mwtemplate', $value );
                }

                if ( $name == "mwwrite" ) {
	                $wswrite = Core::createHiddenField( 'mwwrite', $value );
                }

                if ( $name == "mwoption" ) {
	                $wsoption = Core::createHiddenField( 'mwoption', $value );
                }

	            if ( $name == "mwslot" ) {
		            $wsSlot = Core::createHiddenField( 'mwslot', $value );
	            }

                if ( $name === "mwfollow" ) {
                	if( strlen( $value ) <= 1 ) {
		                $wsfollow = Core::createHiddenField( 'mwfollow', 'true' );
	                } else {
		                $wsfollow = Core::createHiddenField( 'mwfollow', $value );
	                }
                }

                if ( $name == "mwleadingzero" ) {
	                $wsleadingzero = Core::createHiddenField( 'mwleadingzero', 'true' );
                } else $wsleadingzero = '';

                /*
                if ( $k == "mwfields" ) {
                    $wsfields = '<input type="hidden" name="mwfields" value="' . $v . '">' . "\n";
                }
                */
            }
        }


        return $template . $wswrite . $wsoption . $wsfollow. $wsleadingzero . $wsSlot;


    }

}