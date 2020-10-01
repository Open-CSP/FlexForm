<?php
# @Author: Sen-Sai
# @Date:   25-05-2018 -- 11:47:13
# @Last modified by:   Charlot
# @Last modified time: 18-06-2018 -- 10:48:39
# @License: Mine
# @Copyright: 2018
# @version : 0.6.9.3.7

//namespace wsform\special;
use wsform\validate\validate as validate;


/**
 * Overview for the WSForm extension
 *
 * @file
 * @ingroup Extensions
 */

class SpecialWSForm extends SpecialPage {


    public $allowEditDocs = true;

	public function __construct() {
		parent::__construct( 'WSForm' );
	}


	function getGroupName() {
			return 'Wikibase';
		}


	public function makeMessage($msg, $type="danger") {
		setcookie("wsform[type]", $type, 0, '/' );
		setcookie("wsform[txt]", $msg, 0, '/' );
	}

    public function MakeTitle() {
        $date = new DateTime('2000-01-01');
        $dt = date('d/m/Y H:i:s');
        $pageTitle = time();
        return $pageTitle;
    }


    /**
     * @brief Get and check $_POST variable
     *
     * @param $var string $_POST variable to check
     * @return mixed Either false or the value of the $_POST variable
     */
	public function getPostString($var) {
		if ( isset( $_POST[$var] ) && !empty($_POST[$var]) ) {
			$template = $_POST[$var];
		} else {
			$template = false;
		}
		return $template;
	}

	public function is_valid_name($file) {
		return preg_match('/^([-\.\w]+)$/', $file) > 0;
	}

	public function is_valid_type($type) {
		$formhooks = WSFormHooks::availableHooks();
		if(in_array($type,$formhooks)) {
			return true;
		} else return false;
	}



    /**
     * @brief WSForm Docs menu.
     * Builds and renders the WSForm Docs menu
     *
     * @param $path string Path to docs
     * @param $examplePath string Path to examples
     * @param $purl string uri to Docs
     * @param $eurl string uri to Examples
     * @param $wgServer object Wiki server information
     * @param $out object Wiki Out variable
     * @param $wsformpurl string WSForm url
     * @param $ver string WSForm version
     */
	public function renderMenu($path, $examplePath, $purl, $eurl, $wgServer, $out, $wsformpurl, $ver, $newVersionAvailable) {
        $fileList = glob($path.'*.json');
        $exampleList = glob($examplePath.'*.json');
        global $IP, $wgParser;
        $menuPath = "$IP/extensions/WSForm/modules/coreNav/";
        $data = array();
        $exampleData = array();
        $createExample = $wgServer.'/index.php/Special:WSForm/Docs/Create Example';
		$createDocumentation = $wgServer.'/index.php/Special:WSForm/Docs/Create';
		$formBuilderUrl = $wgServer.'/index.php/Special:WSForm/Formbuilder';
		$changeLogUrl = $wgServer.'/index.php/Special:WSForm/Docs/ChangeLog';
		$changeLogUrl = '<li><a href="' . $changeLogUrl . '"> '.wfMessage("wsform-docs-changelog")->text().'</a></li>';


        // Get normal documentation
        foreach( $fileList as $file ) {
            $type = explode('_',basename( $file ),2 );
            $t = $type[0];
            $n = $type[1];
            $data[$t][]=$n;
        }
        // Get examples
        foreach( $exampleList as $example ) {
            $type = explode('_',basename( $example ),2 );
            $t = $type[0];
            $n = $type[1];
            $exampleData[$t][]=$n;
        }
        //Load menu JavaScript
        //$ret = '<link rel="stylesheet" href="'.$wsformpurl.'modules/coreNav/coreNavigation-1.1.3.css">';
        //$ret .= '<link rel="stylesheet" href="'.$wsformpurl.'modules/coreNav/assets/css/custom.css">';
        $ret = '<style>';
        $ret .= file_get_contents($menuPath.'coreNavigation-1.1.3.css');
        $ret .= file_get_contents($menuPath.'assets/css/custom.css');
        $ret .= '</style>';
        //$ret .= '<script src="' . $wsformpurl . 'modules/coreNav/coreNavigation-1.1.3.js"></script>';
        $ret .= '<script src="https://unpkg.com/feather-icons@4.7.3/dist/feather.min.js"></script>';
        $nav = file_get_contents($menuPath.'menu.php' );
        //%%wsformpurl%%
        $navItem = file_get_contents($menuPath.'menu-item-main.php' );
        $items = '';
        $eitems = '';

        foreach ($data as $k=>$v) {
            $mItem = str_replace('%%menuName%%', $k, $navItem);
            $sItem = '';
            foreach ($v as $doc) {
                $docname = substr($doc,0,-5);
                $docContent = json_decode(file_get_contents($path.$k.'_'.$docname.'.json' ), true );
                //$sItem .= '<li><a href="'.$purl.'/'.$k.'_'.$docname.'">'.$docname.'</a>';
                //$sItem .= '<span>'.$docContent['doc']['synopsis'].'</span></p></li>';
	            $sItem .= '<li class="nfo"><a href="'.$purl.'/'.$k.'_'.$docname.'"><i data-feather="list"></i>' . $docname . '<br>';
	            $sItem .= '<span><strong>' . wfMessage("wsform-docs-information")->text() . ': </strong>'.$docContent['doc']['synopsis'].'</span></a></li>';
            }
            $mItem = str_replace('%%items%%',$sItem,$mItem);
            $items .= $mItem;
        }
        $back = $wgServer.'/index.php/Special:WSForm/Docs';
        if( $this->allowEditDocs ) {
            $new = '<li><a href="' . $createDocumentation . '">' . wfMessage("wsform-docs-create-new-doc")->text() . '</a></li>';
            $new .= '<li><a href="' . $createExample . '">' . wfMessage("wsform-docs-create-new-example")->text() . '</a></li>';
        } else $new = '<li>'. wfMessage("wsform-docs-editing-disabled")->text() . '</li>';

        if( $newVersionAvailable === false ) {
	        $changeLogUrl = '';
        }

		$index = $wgServer.'/index.php/Special:WSForm/Docs/Index';
        $wsformpurl = $wgServer."/extensions/WSForm/";
        $search = array('%items%', '%url%', '%back%', '%version%', '%new%', '%index%', '%%wsformpurl%%', '%fb%', '%changelog%');
        $replace = array($items, $wsformpurl . "WSForm-logo.png", $back, $ver, $new, $index, $wsformpurl, $formBuilderUrl, $changeLogUrl );
        $nav = str_replace($search, $replace, $nav);

        //$out->addHTML($ret.$nav);
        //return;
        // Add example list
        foreach ($exampleData as $k=>$v) {
            $mItem = str_replace('%%menuName%%', $k, $navItem);
            $sItem = '';
            foreach ($v as $example) {
                $exampleName = substr($example,0,-5);
                $exampleContent = json_decode(file_get_contents($examplePath.$k.'_'.$exampleName.'.json' ), true );
                $sItem .= '<li class="nfo"><a href="'.$eurl.'/'.$k.'_'.$exampleName.'"><i data-feather="list"></i>' . $exampleName . '<br>';
				$sItem .= '<span><strong>' . wfMessage("wsform-docs-information")->text() . ': </strong>'.$exampleContent['example']['synopsis'].'</span></a></li>';
            }
            $mItem = str_replace('%%items%%',$sItem,$mItem);
            $eitems .= $mItem;
        }
        $nav = str_replace('%eitems%', $eitems, $nav);
        $out->addHTML($ret.$nav);
        return;
    }

	private function get_string_between($string, $start, $end){
		$string = " ".$string;
		$ini = strpos($string,$start);
		if ($ini == 0) return "";
		$ini += strlen($start);
		$len = strrpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}

    private function getChangeLog( $bitbucketChangelog, $currentVersion ) {
	    $readme = file_get_contents( $bitbucketChangelog );
	    if( $readme === false ) {
	    	return "not found";
	    }
	    $changeLog = $this->get_string_between( $readme, '### Changelog', '* ' . $currentVersion );
	    $changeLog = ltrim( $changeLog, "\n");
	    $changeLog = str_replace( "\n", "<br>", $changeLog);
	    return $changeLog;
    }

	/**
	 * @brief Show the page to the user.
     * Also used for handling Documentation
	 *
	 * @param string $sub The subpage string argument (if any).
	 *  [[Special:HelloWorld/subpage]].
	 */
	public function execute( $sub ) {
		global $IP, $wgUser, $wgExtensionCredits, $wgScript;

		$realUrl = str_replace( '/index.php', '', $wgScript );
		$ver = "";
		$bitbucketSource = 'https://api.bitbucket.org/2.0/repositories/wikibasesolutions/mw-wsform/src/master/extension.json';
		$bitbucketChangelog = 'https://api.bitbucket.org/2.0/repositories/wikibasesolutions/mw-wsform/src/master/README.md';
		$extJson = file_get_contents( $bitbucketSource );
		if( $extJson !== false ) {
			$extJson = json_decode( $extJson, true );
			if( isset( $extJson['version'] ) ) {
				$sourceVersion = $extJson['version'];
			} else $sourceVersion = false;
		}
		foreach($wgExtensionCredits['parserhook'] as $ext) {
			if($ext['name'] == 'WSForm') {
				$ver = "v<strong>".$ext['version']."</strong>";
				if( $sourceVersion !== false ) {
					if( $sourceVersion != $ext['version'] ) {
						$newVersionAvailable = true;
						$currentVersion = $ext['version'];
						$ver .= ' <br><span style="font-size:11px; color:red;">NEW : v' . $sourceVersion .'</span>';
						//$ver .= $this->getChangeLog( $bitbucketChangelog, $ext['version'] );
					} else $newVersionAvailable = false;
				}
			}
		};
        $path = "$IP/extensions/WSForm/docs/";
        $wsformpurl = $realUrl . "/extensions/WSForm/";
        $examplePath = $path.'examples/';
        $purl = $realUrl . "/index.php/Special:WSForm/Docs";
        $eurl = $realUrl . "/index.php/Special:WSForm/Docs/examples";
        $out = $this->getOutput();
		$out->addHTML( '<h1 class="hit-the-floor" style="text-align:center;">WSForm ' . wfMessage("wsform-docs-documentation")->text() . '</h1><hr class="brace">');
        $this->renderMenu($path, $examplePath, $purl, $eurl, $realUrl, $out, $wsformpurl, $ver, $newVersionAvailable);
		if ( ! $wgUser->isLoggedIn() ) {
			$out->addHTML( '<p>' . wfMessage("wsform-docs-log-in")->text() . '</p>' );
			return;
		}
		$back = '<div class="ws-documentation-back"><a href="'.$realUrl.'/index.php/Special:WSForm/Docs">' . wfMessage("wsform-docs-back-documentation")->text() . '</a></div>';
		$args = $this->getArgumentsFromSpecialPage($sub);
		if ($args !== false) {
			if ( strtolower($args[0]) == 'formbuilder' ) {
				$path = "$IP/extensions/WSForm/formbuilder/";
				$out->addHTML( $back );
				include ($path.'php/formbuilder.php');
				return true;
			}




            $loadJS = '<script src="'.$wsformpurl.'docs/waitForJQ.js"></script>';
            $loadJS .= '<link rel="stylesheet" href="'.$wsformpurl.'modules/wysiwyg/ui/trumbowyg.min.css">';
            $loadJS .= "
<script>
    function getTrumbo() {
	    $.getScript( '".$wsformpurl."modules/wysiwyg/trumbowyg.js' ).done( function () {
		    $.when(
			    $.getScript( '".$wsformpurl."modules/wysiwyg/plugins/colors/trumbowyg.colors.min.js' ),
			    $.getScript( '".$wsformpurl."modules/wysiwyg/plugins/fontfamily/trumbowyg.fontfamily.min.js' ),
			    $.getScript( '".$wsformpurl."modules/wysiwyg/plugins/fontsize/trumbowyg.fontsize.min.js' ),
			    $.getScript( '".$wsformpurl."modules/wysiwyg/plugins/table/trumbowyg.table.min.js' ),
			    //$.getScript( '".$wsformpurl."modules/wysiwyg/plugins/cleanpaste/trumbowyg.cleanpaste.min.js' ),
			    $.getScript( '".$wsformpurl."modules/wysiwyg/plugins/preformatted/trumbowyg.preformatted.min.js' ),
			    //$.getScript( '".$wsformpurl."modules/wysiwyg/plugins/wiki/trumbowyg.wiki.min.js' ),
		    ).done( function () {
			    $( 'textarea.wsdocs-wysiwyg' ).trumbowyg( {
				    svgPath: '".$wsformpurl."modules/wysiwyg/ui/icons.svg', autogrow: true, removeformatPasted: false,
				    btns: [['viewHTML'], ['undo', 'redo'], ['formatting'], ['strong', 'em', 'del'], ['superscript', 'subscript'], ['foreColor', 'backColor'], ['link'], ['insertImage'], ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'], ['unorderedList', 'orderedList'], ['horizontalRule'],['table'], ['removeformat'],['preformatted'], ['fullscreen']]
			    } )
		    } )
	    } );
    }";
            $loadJS .= '

    document.addEventListener("DOMContentLoaded", function() {
	    wachtff(getTrumbo);
	    wachtff(initializeMenu, "menus");
    });
</script>';
            $out->addHTML( $loadJS );

			$back = '<div class="ws-documentation-back"><a href="'.$realUrl.'/index.php/Special:WSForm/Docs">' . wfMessage("wsform-docs-back-documentation")->text() . '</a></div>';
			if ( strtolower($args[0]) == 'docs' ) {
				$css = file_get_contents($path.'docs.css');
				$out->addHTML( '<style>'.$css.'</style>' );
				// Did we have Posts ????
				$create = $this->getPostString('create');
				if ( $create && $create == 'create' ) {
						unset( $_POST['create'] );

						$name = $this->getPostString('name');
						$type = $this->getPostString('type');

						if ( ( $name === false || !$this->is_valid_name($name) ) || ($type === false || !$this->is_valid_type($type)) ) {
							$out->addHTML( '<p>Sorry we need a valid name for this function (no spaces and other special characters).</p>' );
							return;
						}
						if ( file_exists($path.$type."_".$name.'.json') ){
							$out->addHTML( '<p>The Create Documentation page is for creating documentation.</p><p>This function ('.$name.') is already in our documentation.</p>'.$back );
							return;
						}
						$data['doc'] = $_POST;
						$data['doc']['created'] = date("d-m-Y H:i:s");
						$data['doc']['created by'] = $wgUser->getName();
					if (file_put_contents($path.$type."_".$name.'.json',json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ) ) {
							$this->makeMessage('Documentation for <strong>'.$name.'</strong> stored.','success');
							$out->redirect($realUrl.'/index.php/Special:WSForm/Docs');
						} else $out->addHTML( '<p>Could NOT store documentation for <strong>'.$name.'</strong>.</p>'.$back );

						return ;
				}
				if ( $create && $create == 'createExample' ) {
					unset( $_POST['create'] );
					unset( $_POST['pf'] );

					$name = $this->getPostString('name');
					$type = $this->getPostString('type');

					if ( ( $name === false || !$this->is_valid_name($name) ) || ($type === false || !$this->is_valid_type($type)) ) {
						$out->addHTML( '<p>Sorry we need a valid name for this example (no spaces and other special characters).</p>' );
						return;
					}
					if ( file_exists($path.$type."_".$name.'.json') ){
						$out->addHTML( '<p>The Create Example page is for creating documentation.</p><p>This example ('.$name.') is already in our list of examples.</p>'.$back );
						return;
					}
					$data['example'] = $_POST;
					$data['example']['created'] = date("d-m-Y H:i:s");
					$data['example']['created by'] = $wgUser->getName();
					if (file_put_contents($examplePath.$type."_".$name.'.json',json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ) ) {
						$this->makeMessage('Example for <strong>'.$name.'</strong> stored.','success');
						$out->redirect($realUrl.'/index.php/Special:WSForm/Docs');
					} else $out->addHTML( '<p>Could NOT store example for <strong>'.$name.'</strong>.</p>'.$back );

					return ;
				}
				if ( $create && $create == 'edit' ) {
						unset( $_POST['create'] );

						$name = $this->getPostString('name');
						$type = $this->getPostString('type');
						$pf = $this->getPostString('pf');

						if ( ( $name === false || !$this->is_valid_name($name) ) || ($type === false || !$this->is_valid_type($type)) ) {
							$out->addHTML( '<p>Sorry we need a valid name for this function (no spaces and other special characters)['.$name.'].</p>' );
							return;
						}
						//echo "<BR><BR><BR><BR><BR><BR>".$path.$pf.'.json';
						if ( !file_exists($path.$pf.'.json') ){
							$out->addHTML( '<p>We cannot find this documentation ('.$name.').</p>'.$back );
							return;
						}
						$delete = $this->getPostString('delete');
						if($delete == 'delete') {
							unlink ( $path.$type."_".$name.'.json' );
							$this->makeMessage('Documentation for <strong>'.$name.'</strong> deleted.','notice');
							$out->redirect($realUrl.'/index.php/Special:WSForm/Docs');
							return;
						}
						unlink ($path.$pf.'.json');
						unset($_POST['pf']);

						$data['doc'] = $_POST;
						$data['doc']['last modified'] = date("d-m-Y H:i:s");
						$data['doc']['modified by'] = $wgUser->getName();
						if (file_put_contents($path.$type."_".$name.'.json',json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ) ) {
							$this->makeMessage('Documentation for <strong>'.$name.'</strong> stored.','success');
							$out->redirect($realUrl.'/index.php/Special:WSForm/Docs');
							$out->addHTML( '<p>Documentation for <strong>'.$name.'</strong> stored.</p>'.$back );
							return;
						} else {
							$out->addHTML( '<p>Could NOT store documentation for <strong>'.$name.'</strong>.</p>'.$back );
							return;
						}
				}
				if ( $create && $create == 'editExample' ) {
					unset( $_POST['create'] );

					$name = $this->getPostString('name');
					$type = $this->getPostString('type');
					$pf = $this->getPostString('pf');

					if ( ( $name === false || !$this->is_valid_name($name) ) || ($type === false || !$this->is_valid_type($type)) ) {
						$out->addHTML( '<p>Sorry we need a valid name for this example (no spaces and other special characters)['.$name.'].</p>' );
						return;
					}
					//echo "<BR><BR><BR><BR><BR><BR>".$path.$pf.'.json';
					if ( !file_exists($examplePath.$pf.'.json') ){
						$out->addHTML( '<p>We cannot find this example ('.$name.').</p>'.$back );
						return;
					}
					$delete = $this->getPostString('delete');
					if($delete == 'delete') {
						unlink ( $examplePath.$type."_".$name.'.json' );
						$this->makeMessage('Example for <strong>'.$name.'</strong> deleted.','notice');
						$out->redirect($realUrl.'/index.php/Special:WSForm/Docs');
						return;
					}
					unlink ($examplePath.$pf.'.json');
					unset($_POST['pf']);

					$data['example'] = $_POST;
					$data['example']['last modified']=date("d-m-Y H:i:s");
					$data['example']['modified by']=$wgUser->getName();
					if (file_put_contents($examplePath.$type."_".$name.'.json',json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ) ) {
						$this->makeMessage('Example for <strong>'.$name.'</strong> stored.','success');
						$out->redirect($realUrl.'/index.php/Special:WSForm/Docs');
						$out->addHTML( '<p>Example for <strong>'.$name.'</strong> stored.</p>'.$back );
						return;
					} else {
						$out->addHTML( '<p>Could NOT store example for <strong>'.$name.'</strong>.</p>'.$back );
						return;
					}
				}
				// Are we on the subpage CREATE ?
				if( isset($args[1]) && strtolower($args[1] ) == 'create' ) {
					$back = '<div class="ws-documentation-back"><a href="'.$realUrl.'/index.php/Special:WSForm/Docs">' . wfMessage("wsform-docs-documentation")->text() . '</a></div>';
					$out->addHTML( $back );
					$form = file_get_contents($path.'create.html');
					$form = str_replace('%%url%%',$purl,$form);
					$formhooks = WSFormHooks::availableHooks();
					$options = "";
					foreach ($formhooks as $option) {
						$options .= '<option value="'.$option.'">'.$option.'</option>';
					}
					$form = str_replace('%%options%%',$options,$form);
					$out->addHTML( $form );
					return;
				}
				// Are we on the subpage CREATEEXAMPLE ?
				if( isset($args[1]) && strtolower($args[1] ) == 'create_example' ) {
					$back = '<div class="ws-documentation-back"><a href="'.$realUrl.'/index.php/Special:WSForm/Docs">' . wfMessage("wsform-docs-documentation")->text() . '</a></div>';
					$out->addHTML( $back );
					$form = file_get_contents($path.'example.html');
					$form = str_replace('%%url%%',$purl,$form);
					$form = str_replace('%%exampleaction%%','createExample',$form);
					$fields = array (
						'name',
						'type',
						'synopsis',
						'description',
						'example',
						'note',
						'links',
						'delete',
						'created',
						'created by'
					);
					foreach ($fields as $field) {
						$form = str_replace('%%'.$field.'%%','',$form);
					}
					$formhooks = WSFormHooks::availableHooks();
					$options = "";
					foreach ($formhooks as $option) {
						$options .= '<option value="'.$option.'">'.$option.'</option>';
					}
					$form = str_replace('%%options%%',$options,$form);
					$out->addHTML( $form );
					return;
				}
				// Are we on the subpage EDIT ?
				if( isset($args[1]) && strtolower($args[1] ) == 'edit' ) {
					$example = false;
					$name = $this->getPostString('name');
					$type = $this->getPostString('type');
					if($type == 'example') {
						$example = true;
						if ( $name === false || !file_exists($examplePath.$name.'.json') ) {
							$out->addHTML( '<p>Example for <strong>'.$name.'</strong> not found.</p>'.$back );
							return;
						}
						$form = file_get_contents($path.'example.html');
						$form = str_replace('%%exampleaction%%','editExample',$form);
						$doc = json_decode(file_get_contents($examplePath.$name.'.json' ), true );
					} else {
						if ( $name === false || ! file_exists( $path . $name . '.json' ) ) {
							$out->addHTML( '<p>Documentation for <strong>' . $name . '</strong> not found.</p>' . $back );
							return;
						}
						$form = file_get_contents($path.'edit.html');
						$doc = json_decode(file_get_contents($path.$name.'.json' ), true );
					}


					$form = str_replace('%%url%%',$purl,$form);
					$form = str_replace('%%pf%%',$name,$form);
					$formhooks = WSFormHooks::availableHooks();
					$options = "";
					$type = explode('_',basename( $name ),2 );
					$type = $type[0];
					$fields = array (
						'name',
						'type',
						'synopsis',
						'description',
						'parameters',
						'example',
						'note',
						'links',
						'created',
						'created_by'
					);


					foreach ($fields as $field) {
						if($example) {
							$tmp = $doc['example'][$field];
						} else {
							$tmp = $doc['doc'][ $field ];
						}
						$form = str_replace('%%'.$field.'%%', $tmp, $form);
					}

					foreach ($formhooks as $option) {
						if ($type == $option) {
						$options .= '<option value="'.$option.'" selected>'.$option.'</option>';
						} else {
						$options .= '<option value="'.$option.'">'.$option.'</option>';
						}
					}
					$form = str_replace('%%'.$field.'%%',$options,$form);



					$form = str_replace('%%options%%',$options,$form);
					if( $example ) {
						$rpl = '<fieldset style="padding:20px;">
        <legend>Delete</legend>
        <label style="display:inline-block !important; margin-top:0px !important;" for="delete">Delete this example</label>
        <input style="display:inline-block !important; width:auto;" type="checkbox" name="delete" id="delete" value="delete">
    </fieldset>';
						$form = str_replace('%%delete%%',$rpl,$form);
					}
					$out->addHTML( $form.$back );
					return;
				}
				// Changelog new version info
				if ( isset($args[1]) && strtolower($args[1] ) == 'changelog' ) {

					$changeLogTemplate = file_get_contents($path.'changelog.html');
					$repl = array( '%version%', '%changelog%');
					$with = array( $sourceVersion, $this->getChangeLog( $bitbucketChangelog, $currentVersion ) );
					$out->addHTML( str_replace( $repl, $with, $changeLogTemplate) );
					return;
				}

				// Show the index page
//if( isset($args[1]) && strtolower($args[1] ) == 'create' ) {
				if ( isset($args[1]) && strtolower($args[1] ) == 'index' ) {
					$fileList = glob($path.'*.json');
					$exampleList = glob($examplePath.'*.json');

					foreach( $fileList as $file ) {
						$type = explode('_',basename( $file ),2 );
						$t = $type[0];
						$n = $type[1];
						$data[$t][]=$n;
					}
					foreach( $exampleList as $example ) {
						$type = explode('_',basename( $example ),2 );
						$t = $type[0];
						$n = $type[1];
						$exampleData[$t][]=$n;
					}
					$ret = '<script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>';
					$ret .= '<div class="grid ws-documentation-show-index" data-masonry=\'{ "itemSelector": ".grid-item", "columnWidth": 200 }\'>';
					foreach ($data as $k=>$v) {
						$ret .= '<div class="ws-documentation-index grid-item">';
						$ret .= '<h3>'.$k.'</h3>';
						foreach ($v as $doc) {
							$docname = substr($doc,0,-5);
							$docContent = json_decode(file_get_contents($path.$k.'_'.$docname.'.json' ), true );
							$ret .= '<p><a href="'.$purl.'/'.$k.'_'.$docname.'">'.$docname.'</a><BR>';
							$ret .= '<span>'.$docContent['doc']['synopsis'].'</span></p>';
						}
						$ret .= '</div>';
					}
					// Add example list
					$ret .= '<div class="ws-documentation-index grid-item">';
					$ret .= '<h3>Complete examples</h3>';
					foreach ($exampleData as $k=>$v) {
						foreach ($v as $example) {
							$exampleName = substr($example,0,-5);
							$exampleContent = json_decode(file_get_contents($examplePath.$k.'_'.$exampleName.'.json' ), true );
							$ret .= '<p><a href="'.$eurl.'/'.$k.'_'.$exampleName.'">'.$exampleName.'</a><BR>';
							$ret .= '<span>'.$exampleContent['example']['synopsis'].'</span></p>';
						}
					}
					if( $this->allowEditDocs ) {
                        $createExample = '<div class="ws-documentation-back"><a href="' . $realUrl . '/Special:WSForm/Docs/Create Example">Create a new example</a></div>';
                        $ret .= $createExample;
                    }
					$ret .= $createExample;
					$ret .= '</div>';
					// End example list
					$ret .= '</div>';
					if( $this->allowEditDocs ) {
                        $ret .= '<div class="ws-documentation-back"><a href="' . $realUrl . '/Special:WSForm/Docs/Create">Create Documentation</a></div>';
                    }
					$out->addHTML( $ret );

					return;
				}

				if ( !isset( $args[1] ) ) {
					$out->addHTML( '<div><p><BR><BR><BR></p></div>' );
				}

				if( isset($args[1]) && strlen($args[1]) > 2 ) {
					if( strtolower( $args[1] ) == 'examples') {
						if( isset($args[2]) && strlen($args[2]) > 2 ) {
							if( file_exists( $examplePath.$args[2].'.json' ) ) {
								$doc = json_decode( file_get_contents( $examplePath . $args[2] . '.json' ), true );
								if($this->allowEditDocs) {
                                    $editButton = '<div class="ws-documentation-edit"><form action="' . $purl . '/Edit" method="post">';
                                    $editButton .= '<input type="hidden" name="name" value="' . $args[2] . '">';
                                    $editButton .= '<input type="hidden" name="type" value="example">';
                                    $editButton .= '<input type="submit" value="edit"></form></div>';
                                } else $editButton = '';
								$documentation = '<div class="ws-documentation-show">';
								foreach ( $doc['example'] as $k => $v ) {
									if ( $k == "example" ) {
										$documentation .= $out->parse( '<h3>' . ucfirst( $k ) . '</h3>' . "\n<p><pre>" . $v . "</pre></p>\n" );
										$documentation .= $out->parse( '<h3>Rendered example</h3>' . "\n<p>" . $v . "</p>\n" );
										//$documentation .= '<h3>Rendered example</h3>' . "\n<p>" . $v . "</p>\n";
									} elseif ($k=='last modified' || $k=='created' || $k=='created_by' || $k=='created_by' || $k=='modified by') {
										if($k == 'created_by') {
											$k = 'created_by';
										}
										$documentation .= '<p class="info"><strong>' . ucfirst( $k ) . '</strong> : ' . "$v</p>\n"; }
									else {
										$documentation .= '<h3>' . ucfirst( $k ) . '</h3>' . "\n<p>$v</p>\n";
									}
								}
							}
						} else {
							return;
						}
					} else {
						if ( file_exists( $path . $args[1] . '.json' ) ) {
							$doc = json_decode( file_get_contents( $path . $args[1] . '.json' ), true );
							if( $this->allowEditDocs ) {
                                $editButton = '<div class="ws-documentation-edit"><form action="' . $purl . '/Edit" method="post">';
                                $editButton .= '<input type="hidden" name="name" value="' . $args[1] . '">';
                                $editButton .= '<input type="hidden" name="type" value="doc">';
                                $editButton .= '<input type="submit" value="edit"></form></div>';
                            } else $editButton = '';
							$documentation = '<div class="ws-documentation-show">';
							foreach ( $doc['doc'] as $k => $v ) {
								if ( $k == "example" ) {
									$documentation .= $out->parse( '<h3>' . ucfirst( $k ) . '</h3>' . "\n<p><pre>" . $v . "</pre></p>\n" );
								} elseif ($k=='last modified' || $k=='created' || $k=='created_by' || $k=='created_by' || $k=='modified by') {
									if($k == 'created_by') {
										$k = 'created_by';
									}
									$documentation .= '<p class="info"><strong>' . ucfirst( $k ) . '</strong> : ' . "$v</p>\n";
								} else {
									$documentation .= '<h3>' . ucfirst( $k ) . '</h3>' . "\n<p>$v</p>\n";
								}
							}
						} else return;
					}


					$documentation .= '</div>';
					$out->addHTML( $editButton );
					$out->addHTML( $documentation );
					$out->addHTML( '<div class="ws-documentation-back"><a href="'.$realUrl.'/index.php/Special:WSForm/Docs">Back to Documentation</a></div>' );

					return;
					}
			}
			return;
		}



    if(isset($_POST['wstemplate']) && $_POST['wstemplate']!=="") {
			$template = $_POST['wstemplate'];
		} else $template = false;

		if(isset($_POST['wswrite']) && $_POST['wswrite']!=="") {
			$writepage = $_POST['wswrite'];
		} else $writepage = false;

		if(isset($_POST['wsaction']) && $_POST['wsaction']!=="") {
			$action = $_POST['wsaction'];
		} else $action = false;

		if($template) {
            $ret = "{{".$_POST['wstemplate']."<BR>";
            $out->setPageTitle( "We got posts" );
            $out->addHTML("<h2>Example of sourcecode written</h2>");
	        foreach ($_POST as $k=>$v) {
	            if(is_array($v)) {
	                $ret .= "|".$k."=";
	                foreach($v as $multiple) {
                        $ret .= $multiple.',';
                    }
                    $ret=rtrim($ret,',')."<BR>";
                } else {
	                if($k!=="wstemplate" && $k!=="wsaction" && $k!=="wswrite" && $k!=="wsreturn") {
                        $ret .= '|' . $k . '=' . $v . '<BR>';
                    }
                }
            }
            $ret.="}}";
			if($action == 'add_random' && $writepage) {
				$title = $writepage.$this->MakeTitle();
				$out->addHTML('The new file to be written will be : '.$title."<BR>");
			}
            $out->addHTML($ret);
        } else {
			$out->redirect($realUrl.'/index.php/Special:WSForm/Docs');
		    return;
		    //echo $IP . '/extensions/WSForm/classes/validate.php';
            error_reporting( -1 );
            ini_set( 'display_errors', 1 );
            //include_once( $IP . '/extensions/WSForm/classes/loader.php' );

						$table = "<table class=\"table table-striped\"><tr><td class=\"center\" colspan=\"6\">Valid input types</td></tr>";
						$table .= "<tr><td>Formparameters</td><td>Parameters</td><td>Input types</td><td>Form Hooks</td><td>File input</td><td>Email</td></tr>";
						$formParameters = validate::validFormParameters("",true);
						$emailParameters = wsform\validate\validate::validEmailParameters("",true);
						$parameters = validate::validParameters("",true);
						$inputTypes = validate::validInputTypes("",true);
						$fInputTypes = validate::validFileParameters("",true);
						$formhooks = WSFormHooks::availableHooks();
						$table .= "<tr><td><table>";
						foreach ( $formParameters as $params ) {
							$table .= "<tr><td>$params</td></tr>";
						}
						$table .= "</table></td><td><table>";
						foreach ( $parameters as $params ) {
							$table .= "<tr><td>$params</td></tr>";
						}
						$table .= "</table></td><td><table>";
						foreach ( $inputTypes as $params ) {
							$table .= "<tr><td>$params</td></tr>";
						}
						$table .= "</table></td><td><table>";
						foreach ( $formhooks as $params ) {
							$table .= "<tr><td>$params</td></tr>";
						}
						$table .= "</table></td><td><table>";
						foreach ( $fInputTypes as $params ) {
							$table .= "<tr><td>$params</td></tr>";
						}
						$table .= "</table></td><td><table>";
						foreach ( $emailParameters as $params ) {
							$table .= "<tr><td>$params</td></tr>";
						}
						$table .= "</table></td></tr></table>";
            //$out->addHTML("<p>Move along.. nothing to see here..</p>");
            $out->addHTML($table);
        }




        return;
	}

	public function getArgumentsFromSpecialPage($sub) {
		if ( isset( $sub ) && $sub !== "" ) {
			$args = explode( '/', $sub );
			return $args;
		} else return false;
	}
}
