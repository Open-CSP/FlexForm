<?php


namespace wsform\edit;

use wsform\validate\validate;

class render {

    /**
     * Render WSEdit
     * Called bij WSEdit()
     *
     * @param $args
     *
     * @return string Formatted HTML
     */
    public static function render_edit( $args ) {
		$wsSlot = false;
        $def = '<input type="hidden" name="mwedit[]" value="';
        $div = '-^^-';
        if ( isset( $args['target'] ) && $args['target'] != "" ) {
            $pid = $args['target'];
        } else {
            return 'No valid target for edit';
        }

        // mwtemplate will become the default, for now we allow both
        if ( ( isset( $args['template'] ) && $args['template'] != "" ) || ( isset( $args['mwtemplate'] ) && $args['mwtemplate'] != "") ) {
        	if( isset( $args['template'] ) ) {
		        $template = $args['template'];
	        }
	        if( isset( $args['mwtemplate'] ) ) {
		        $template = $args['mwtemplate'];
	        }
            $template = str_replace( ' ', '_', $template );
        } else {
            return 'No valid template for edit';
        }


        if ( isset( $args['formfield'] ) && $args['formfield'] != "" ) {
            $formfield = $args['formfield'];
        } else {
            return 'No valid formfield for edit';
        }

        if ( isset( $args['usefield'] ) && $args['usefield'] != "" ) {
            $usefield = $args['usefield'];
        } else {
            $usefield = false;
        }

		if ( isset( $args['mwslot'] ) && $args['mwslot'] !== '' ) {
			//{{#set: Hello=World | Description=These properties are not visible in the content }}
			//mds-metadataslot als test
			$wsSlot = $args['mwslot'];
		} else $wsSlot = false;

        if ( isset( $args['value'] ) && $args['value'] != "" ) {
            $value = $args['value'];
        } else {
            $value = false;
        }

        $val = $pid . $div . $template . $div . $formfield;

        if ( $usefield ) {
            $val .= $div . $usefield;
        } else {
            $val .= $div;
        }

        if ( $value ) {
            $val .= $div . $value;
        } else {
            $val .= $div;
        }

		if ( $wsSlot ) {
			$val .= $div . $wsSlot;
		} else {
			$val .= $div;
		}
        $ret = $def . $val . '">' . "\n";

        return $ret;
    }

}