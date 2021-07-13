<?php
# @Author: Sen-Sai <Charlot>
# @Date:   15-05-2018 -- 10:46:23
# @License: Mine
# @Copyright: 2018



/**
 * Class wsi18n
 *  WSForm wsi18n class for reading wiki language files
 *  @author : Sen-Sai
 *  @date : January 2019
 */
class wsi18n {

    public $language = "en";

    public $text = array();

    function __construct() {
        if( file_exists( 'i18n/' . $this->language . '.json') ) {

            $this->text = json_decode( file_get_contents( 'i18n/' . $this->language . '.json' ) );

        } else {
            $this->text = false;
        }
    }

    function wsMessage($code) {
        if(isset($this->text->$code)) {
            return $this->text->$code;
        } else {
            return '<<' . $code . '>>';
        }
    }

}


/**
 * Class wbHandleResponses
 * WSForm api class for reading writing to wiki
 *
 * @author Sen-Sai
 * @date October 2017
 */
class wbHandleResponses {

    public $ajax = false;

    function __construct($output) {
        if($output == 'ajax') {
            $this->ajax = true;
        }
    }

    function handleResonse($ret) {
        if( $ret['type'] !== false && $ret['status'] === 'ok' && $this->ajax === false ) {
            $this->makeMessage($ret['msg'],$ret['type']); // set cookies
        }

        if ( $ret['status'] === 'ok' && $ret['mwreturn'] !== false ) {
            $this->redirect( $ret['mwreturn'] );
        }

        if ($ret['status'] !== 'ok' && $ret['mwreturn'] !== false ) { // Status not ok.. but we have redirect ?
            $this->makeMessage($ret['msg']); // set cookies
            $this->redirect( $ret['mwreturn'] ); // do a redirect or json output
        } else { // Status not ok.. and no redirect
            $this->outputMsg($ret['msg']); // show error on screen or do json output
        }
        exit();
    }

    function makeMessage($msg, $type="danger") {
        if($msg !== '') {
            setcookie("wsform[type]", $type, 0, '/');
            if ($type !== "danger") {
                setcookie("wsform[txt]", $msg, 0, '/');
            } else {
                setcookie("wsform[txt]", 'WSForm :: ' . $msg, 0, '/');
            }
        }
    }

    function outputMsg() {

        $numargs = func_num_args();
        $args = func_get_args();
        if (!$this->ajax) {
            for ($i = 0; $i < $numargs; $i++) {
                if (is_array($args[$i])) {
                    echo "<pre>";
                    print_r($args[$i]);
                    echo "</pre>";
                } else {
                    echo "<p>" . $args[$i] . "</p>";
                }
            }
        } else {
            $tmp = '';
            for ($i = 0; $i < $numargs; $i++) {
                if (is_array($args[$i])) {
                    $tmp .= implode('<BR>',$args[$i]);
                } else {
                    $tmp .= "<p>" . $args[$i] . "</p>";
                }
            }
            $this->outputJson('error',$tmp);
        }
    }


    /**
     * @param $status string : status keyword
     * @param $data string or array : holds the date
     */
    function outputJson($status,$data) {
        $ret = array();
        $ret['status']=$status;
        $ret['message']=$data;
        echo json_encode($ret,JSON_PRETTY_PRINT);
        die();
    }


    function doDie($msg) {
        if (!$this->ajax) {
            makeMessage($msg, $type="danger");
        } else {
            $this->outputJson('error', $msg);
        }
    }

    function redirect($redirect) {
        $parsed = parse_url( $redirect );

        if( isset( $parsed['host'] ) ){
            if( $parsed['host'] !== $_SERVER['HTTP_HOST'] ) {
                $i18n = new wsi18n();
                die( $i18n->wsMessage( 'wsform-return-outside-domain' ) );
            }
        }
        global $pauseBeforeRefresh;
        if( $pauseBeforeRefresh !== false ) {
            sleep ( $pauseBeforeRefresh );
        }
        if ( !$this->ajax ) {
            header( 'Location: ' . $redirect );
        } else {
            $this->outputJson('ok','ok');
        }
    }
}


/**
 * Class wbApi
 * Class to handle the mediaWiki API calls
 * @author : Sen-Sai
 * @date : 24-10-2017
 */
class wbApi {

  public $api = "";
  public $services = "";

  private $status = array();

  public $usr = false;

  public $api_logintoken = '';
  public $api_session_id = '';
  public $api_loggedin_token = '';
  public $api_cookie_prefix = '';

  public $app = array();

  private $i18n;

  function __construct($user = false) {
      if ($user) {
          $this->usr = $user;
      }

    $this->loadSettings();
  }

  function setStatus( $state, $msg ) {
      $this->status['state'] = $state;
      $this->status['msg'] = $msg;
  }

  function setConfigVar( $name, $config ) {
      if( isset( $config[$name] ) ) {
          $this->app[$name] = $config[$name];
      } else $this->app[$name] = "";
  }

  function getStatus( $returnMessage = false ) {
      if( $returnMessage === false ) {
          return $this->status['state'];
      } else {
          return $this->status['msg'];
      }
  }

  function isSecure(){
      if( $this->app['sec'] === true ) {
          return true;
      } else return false;
  }

  function getCheckSumKey(){
      if( isset( $this->app['sec-key'] ) ) {
          return $this->app['sec-key'];
      } else return false;
  }


  function loadSettings() {

      // Version
      $this->app["version"] = "0.0.1-dev";
      $i18n = new wsi18n();

      // Last modified
      date_default_timezone_set( "UTC" );
      $this->app["lastmod"] = date( "Y-m-d H:i", getlastmod() ) . " UTC"; // Example: 2010-04-15 18:09 UTC

      // User-Agent used for loading external resources
      $this->app["useragent"] = "api stuff " . $this->app["version"] . " (LastModified: " . $this->app["lastmod"] . ") Contact: charlot (at) wikibase (.) nl";


      // Getting the configuration file
      $this->app['IP'] = rtrim( $_SERVER['DOCUMENT_ROOT'], '/') . '/';
      $IP              = $this->app['IP'];
      $serverName      = strtolower( $_SERVER['SERVER_NAME'] );
      if ( file_exists( __DIR__ . '/config/config.php' ) ) {
          include( __DIR__ . '/config/config.php' );
      } else {
          $this->setStatus( false, $i18n->wsMessage( 'wsform-config-not-found' ) );

          return;
      }
      if ( isset( $config['api-username'] ) && $config['api-username'] !== '' ) {
          $this->app['username'] = $config['api-username'];
      } else {
          $this->app['username'] = false;
      }

      if ( isset( $config['api-cookie-path'] ) && $config['api-cookie-path'] !== '' ) {
          $this->app["cookiefile"] = $config['api-cookie-path'];
      } else {
          $this->app["cookiefile"] = "/tmp/CURLCOOKIE";
      }

      if ( isset( $config['use-api-user-only'] ) && $config['use-api-user-only'] !== '' ) {
          $this->app['use-api-user-only'] = strtolower( $config['use-api-user-only'] );
      } else {
          $this->app['use-api-user-only'] = 'yes';
      }

      if ( isset( $config['api-password'] ) && $config['api-password'] !== '' ) {
          $this->app['password'] = $config['api-password'];
      } else {
          $this->app['password'] = false;
      }

      if ( isset( $config['use-smtp'] ) && $config['use-smtp'] !== '' ) {
          $this->app['use-smtp'] = $config['use-smtp'];
      } else {
          $this->app['use-smtp'] = "no";
      }
      if ( isset( $config['wgScript'] ) && $config['wgScript'] !== '' ) {
          $this->app['wgScript'] = rtrim( $config['wgScript'], '/' );
          // /w/index.php -> /w
          $withoutIndex = str_replace('/index.php', '',  $this->app['wgScript'] );
          if( !empty( $withoutIndex ) ) {
              $this->app['IP'] =  $this->app['IP'] .  ltrim($withoutIndex, '/' );
          }
      } else {
          $this->app['wgScript'] = "/index.php";
      }

      $this->setConfigVar( 'smtp-host', $config );
      $this->setConfigVar( 'smtp-authentication', $config );
      $this->setConfigVar( 'smtp-username', $config );
      $this->setConfigVar( 'smtp-password', $config );
      $this->setConfigVar( 'smtp-secure', $config );
      $this->setConfigVar( 'smtp-port', $config );
      $this->setConfigVar( 'sec', $config );
      $this->setConfigVar( 'sec-key', $config );
      $this->setConfigVar( 'form-timeout-limit', $config );

      $this->setConfigVar( 'is-bot', $config );
      if( $this->app['is-bot'] !== true ) {
          $this->app['is-bot'] = false;
      }


      if ( !isset( $config['api-url-overrule'] ) || $config['api-url-overrule'] === '' ) {

          // Here we are trying to create the url for the API.
          // Although this should work on most servers, it might not.
          // If you experience any problems, just uncomment the last line and fill it the correct
          // url for WSform.api.php.
          $tmp_uri = $url = "http" . ( ! empty( $_SERVER['HTTPS'] ) ? "s" : "" ) . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
          $parts   = explode( '/', $tmp_uri );
          $dir     = "";
          for ( $i = 0; $i < count( $parts ) - 3; $i ++ ) {
              $dir .= $parts[ $i ] . "/";
          }
          $this->app["baseURL"] = $dir;
          $this->app["apiURL"]  = $dir . "api.php";
      } else {
          $this->app['apiURL'] = rtrim( $config['api-url-overrule'], '/' ) . '/api.php';
      }
      if ( isset( $config['wgAbsoluteWikiPath'] ) && $config['wgAbsoluteWikiPath'] !== '' ) {
          $wgAbsoluteWikiPath = rtrim( $config['wgAbsoluteWikiPath'], '/' );
          if ( file_exists( "$wgAbsoluteWikiPath/WSFormSettings.php" ) ) {
              require_once "$wgAbsoluteWikiPath/WSFormSettings.php";
          }
      }

      if( $this->app['password'] === false || $this->app['username'] === false ) {
          $this->setStatus( false, $i18n->wsMessage( 'wsform-config-credentials-not-found' ) );

          return;
      }

      // cURL to avoid repeating ourselfs
      $this->app["curloptions"]      =
          array(
              CURLOPT_COOKIEFILE     => $this->app["cookiefile"],
              CURLOPT_COOKIEJAR      => $this->app["cookiefile"],
              CURLOPT_RETURNTRANSFER => 1,
              CURLOPT_USERAGENT      => $this->app["useragent"],
              CURLOPT_SSL_VERIFYPEER => 0,
              CURLOPT_SSL_VERIFYHOST => false,
              CURLOPT_POST           => true,
              CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1
          );
      $this->app["logincurloptions"] =
          array(
              CURLOPT_CONNECTTIMEOUT => 30,
              CURLOPT_COOKIEJAR      => $this->app["cookiefile"],
              CURLOPT_RETURNTRANSFER => 1,
              CURLOPT_USERAGENT      => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
              CURLOPT_SSL_VERIFYPEER => 0,
              CURLOPT_FOLLOWLOCATION => 1,
              CURLOPT_SSL_VERIFYHOST => false,
              CURLOPT_POST           => true,
              CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1
          );


      $this->setStatus( true, '' );

  }

    /**
     * Strips the "data:image..." part of the base64 data string so PHP can save the string as a file
     * @param $data
     * @return string
     */
    function getBase64Data($data) {
        return base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data));
    }

    /**
     * http://stackoverflow.com/a/2021729
     * Remove anything which isn't a word, whitespace, number
     * or any of the following characters -_~,;[]().
     * If you don't need to handle multi-byte characters
     * you can use preg_replace rather than mb_ereg_replace
     * @param $str
     * @return string
     */
    function sanitizeFileName($str) {
        // Basic clean up
        $str = preg_replace('([^\w\s\d\-_~,;\[\]\(\).])', '', $str);
        // Remove any runs of periods
        $str = preg_replace('([\.]{2,})', '', $str);
        return $str;
    }


    /**
     * Search for a PageTitle and return parsed content of page
     *
     * @param $title string page Title
     * @return string return "" when not found else return parsed content of page
     */
      function parseWikiPageByTitle($title) {

          $postdata = http_build_query([
              "action" => "parse",
              "format" => "json",
              "page" => $title,
              "disablelimitreport" => "1"
          ]);
          $result=$this->apiPost($postdata,true);
            if( isset ($result['received']['parse']['text']['*']) ) {
                $ret = $result['received']['parse']['text']['*'];
            } else $ret ="";
          return $ret;
      }

    /**
     * Search for a PageTitle and return raw content of page
     *
     * @param $title string page Title
     * @return string return "" when not found else return raw content of page
     */
      function getWikiPageByTitle($title) {

          $postdata = http_build_query([
              "action" => "query",
              "format" => "json",
              "prop" => "revisions",
              "rvprop" => "content",
              "titles" => $title
          ]);
          $result=$this->apiPost($postdata,true);
          $ret=array();
          $page = $result['received']['query']['pages'];
          $id = key($page);
          if ( isset ( $result['received']['query']['pages'][$id]['revisions'][0]['*'] ) ) {
              $ret = $result['received']['query']['pages'][$id]['revisions'][0]['*'];
              return $ret;
          } else return "";
      }


    /**
     * Use the MediaWiki API to parse Wikitext
     *
     * @param $txt string Wikitext to parse
     * @return string return "" when unsuccessful else return parsed wikitext
     */
    function parseWikiText($txt) {
        $postdata = http_build_query([
            "action" => "parse",
            "format" => "json",
            "text" => $txt,
            "contentmodel" => "wikitext",
            "disablelimitreport" => "1"
        ]);
        $result=$this->apiPost($postdata,true);
        if( isset ($result['received']['parse']['text']['*']) ) {
            $ret = $result['received']['parse']['text']['*'];
        } else $ret ="";
        return $ret;
    }

    /**
     * If there are more results from the API, get the next results
     *
     * @param $result array of previous API results
     * @return bool when no further results
     * @return string where to start next API call
     */
    function getApiContinue($result) {
        if( isset( $result['received']['continue']['apcontinue'] ) ) {
            $appContinue = $result['received']['continue']['apcontinue'];
        } else $appContinue = false;
        return $appContinue;
    }

    /**
     * Get the ID of the given namespace name
     *
     * @param string $ns
     * @return bool|mixed Either the ID of the namespace or false when not found
     */
    function getIdForNameSpace( $ns ) {
        $ns = strtolower( $ns );
        $id = false;
        $postdata = http_build_query([
            "action" => "query",
            "format" => "json",
            "meta" => "siteinfo",
            "siprop" => "namespaces"
        ]);
        $lst = $this->apiPost($postdata, true);
        if( isset( $lst['received']['query']['namespaces'] ) ) {
            $lst = $lst['received']['query']['namespaces'];
        } else return false;
        foreach( $lst as $nameSpace ) {
            if( isset( $nameSpace['canonical'] ) ) {
                $can   = strtolower( $nameSpace['canonical'] );
                $alias = strtolower( $nameSpace['*'] );
                if ( $can === $ns || $alias === $ns ) {
                    $id = $nameSpace['id'];
                    break;
                }
            }
        }
        return $id;
        //echo "ns id is :" . $id;

    }


    /**
     * Get a list of pages that start with a certain name and take multiple results into account
     *
     * @param $nameStartsWith string Start title of a page
     * @param $appContinue string returned from getApiContinue()
     * @param bool $range void
     * @return mixed API results
     */
    function getDataForWikiList( $nameStartsWith, $appContinue, $range = false ) {


        if( strpos( $nameStartsWith, ':' ) !== false ) {
            $split = explode(':', $nameStartsWith);
            $nameStartsWith = $split[1];
            $nameSpace = $split[0];
            $id = $this->getIdForNameSpace( $nameSpace );
        } else $id = 0;


        if( $id === false ) {
            return false;
        }
        if (!$range) {
            if ($appContinue === false) {

                $postdata = http_build_query([
                    "action" => "query",
                    "format" => "json",
                    "list" => "allpages",
                    "aplimit" => "max",
                    "apprefix" => $nameStartsWith,
                    "apnamespace" => $id
                ]);
            } else {
                $postdata = http_build_query([
                    "action" => "query",
                    "format" => "json",
                    "aplimit" => "max",
                    "apcontinue" => $appContinue,
                    "list" => "allpages",
                    "apprefix" => $nameStartsWith,
                    "apnamespace" => $id
                ]);
            }
        } else {
            $trimmedStart = rtrim($range['start'], "0");
            $trimmedStart = $range['start'];
            if( $appContinue === false ) {
                $postdata = http_build_query([
                    "action" => "query",
                    "format" => "json",
                    "aplimit" => "max",
                    "list" => "allpages",
                    "apnamespace" => $id,
                    "apprefix" => $nameStartsWith
                ]);
            } else {
                $postdata = http_build_query([
                    "action" => "query",
                    "format" => "json",
                    "list" => "allpages",
                    "aplimit" => "max",
                    "apcontinue" => $appContinue,
                    "apnamespace" => $id,
                    "apprefix" => $nameStartsWith
                ]);
            }
        }
        //echo "<pre>";
        //print_r($postdata);
        $result = $this->apiPost($postdata, true);
        //var_dump($result);
        //die();
        return $result;
    }


    function getNextAvailable( $nameStartsWith ){
        $postdata = http_build_query([
            "action" => "wsform",
            "format" => "json",
            "what" => "nextAvailable",
            "titleStartsWith" => $nameStartsWith
        ]);
        $result = $this->apiPost( $postdata, true );
        if( isset( $result['received']['wsform']['error'] ) ) {
            return(array('status' => 'error', 'message' => $result['received']['wsform']['error']['message']));
        } else {
            return(array('status' => 'ok', 'result' => $result['received']['wsform']['result']));
        }
        die();
    }

    function getFromRange( $nameStartsWith, $range ){
         $postdata = http_build_query([
            "action" => "wsform",
            "format" => "json",
            "what" => "getRange",
            "titleStartsWith" => $nameStartsWith,
            "range" => $range
        ]);
        $result = $this->apiPost( $postdata, true );

        if( isset( $result['received']['wsform']['error'] ) ) {
            return(array('status' => 'error', 'message' => $result['received']['wsform']['error']['message']));
        } else {
            return(array('status' => 'ok', 'result' => $result['received']['wsform']['result']));
        }
        die();
    }




    /**
     * @brief Get the next available pagetitle in a range of titles.
     * This function will find the next available title in a range.
     * E.g. if you have pages like invoices, quotes, customers etc that have a increasing pagetitle number
     * like : /Customers/00199. Then this function will return 200
     *
     * @param $nameStartsWith string Start of the page Title
     * @param $range array Optional range to use a fill range['start'], range['end']
     * @return int next available number
     * @return bool false when not successful
     */
    function getWikiListNumber($nameStartsWith, $range = false) {
        $number = array();
        $continue = true;
        $appContinue = false;
        $cnt = 0;
        while( $continue ) {
            $result = $this->getDataForWikiList( $nameStartsWith, $appContinue, $range );

            $appContinue = $this->getApiContinue( $result );

            if( !isset( $result['received']['query'] ) ) {
                return false;
            }

            $pages = $result['received']['query']['allpages'];

            if( is_null( $pages ) || $pages === false ) {
                return false;
            }

            $thisCnt = count( $pages );

            $cnt = $cnt + $thisCnt;

            if ( $cnt < 1 && $appContinue === false ) {
                if (!$range) {
                    return 1;
                } else {
                    return $range['start'] + 1;
                }
            }

            if($thisCnt > 0) {
                foreach ($pages as $page) {

                    $tempTitle = str_replace( $nameStartsWith, '', $page['title'] );

                    if( is_numeric( $tempTitle ) ) {
                        $number[] = $tempTitle;
                    }
                }
            }
            if( $appContinue === false ) {
                $continue = false;
            }

        }


        if(!$range) {
            rsort($number);
            $nr = intval($number[0]);
            return $nr + 1;
        } else {
            if (count($pages) < 1) {
                return $range['start'] + 1;
            }
            $s = $range['start'];
            $e = $range['end'];

            for( $t=$s; $t < $e; $t++ ) {

                if(!in_array($t, $number)) {
                    return $t;
                }
            }

            // TODO:  Still need a procedure what to do if range is full
            return false;
        }
    }


    /**
     * Get the content of a Wikipage by page id
     *
     * @param $id int ID of the page
     * @return array Mediawiki response
     */
      function getWikiPage( $id, $slot = false ) {

          if( $slot !== false ) {
              $postdata = http_build_query(
                  [
                      "action"  => "query",
                      "format"  => "json",
                      "prop"    => "revisions",
                      "rvprop"  => "content",
                      "rvslots" => $slot,
                      "pageids" => $id
                  ]
              );
          } else {
              $postdata = http_build_query(
                  [
                      "action"  => "query",
                      "format"  => "json",
                      "prop"    => "revisions",
                      "rvprop"  => "content",
                      "pageids" => $id
                  ]
              );
          }
          $result=$this->apiPost($postdata,true);
          $title = $result['received']['query']['pages'][$id]['title'];
          $ret=array();
          $ret['title']=$title;
          if( false === $slot ) {
              $ret['content'] = $result['received']['query']['pages'][$id]['revisions'][0]['*'];
          } else {
              $ret['content'] = $result['received']['query']['pages'][$id]['revisions'][0]['slots'][$slot]['*'];
          }
          return $ret;
      }

    function maintenanceUploadFileToWiki( $name, $fileAndPath, $content, $summary, $uid ){
        if(isset($_POST['mwdb'])) {
            $server = str_replace('_', '.', $_POST['mwdb'] );
        } else die('No server name found');
        $details = base64_encode( $content );
        if( $summary === false ) {
            $summary = "Uploaded with WSForm";
        }
        /*
		 * $this->addOption( 'summary', 'Additional text that will be added to the files imported History. [optional]', false, true, "s" );
	  $this->addOption( 'action', 'What to do', true, true, "a" );
	  $this->addOption( 'content', 'Page content', true, true );
	  $this->addOption( 'title', 'Page title', true, true );
      $this->addOption( 'fileincpath', "Filename and path", false, true );
	  $this->addOption( 'user', 'Your username. Will be added to the import log. [mandatory]', true, true, "u" );
	  $this->addOption( 'use-timestamp', 'Use the modification date of the page as the timestamp for the edit, instead of time of import' );
	  $this->addOption( 'rc', 'Place revisions in RecentChanges.' );
		 */
        $cmd = 'SERVER_NAME=' . $server . ' php ' . __DIR__ . '/modules/maintenance/handlePostsToWiki.maintenance.php --action uploadFileToWiki';
        $cmd .= ' --content "' . $details.'"';
        $cmd .= ' --rc --title "' . $name.'"';
        $cmd .= ' --user '. $uid;
        $cmd .= ' --fip "'. $fileAndPath . '"';
        $cmd .= ' --summary "' . $summary . '"';
        //echo $cmd;

        $result = shell_exec( $cmd );
        $res = explode('|', $result);
        if($res[0] === 'ok' ) return true;
        if($res[0] === 'error' ) die($res[1]);
    }

      function maintenanceSavePageToWiki( $name, $details, $summary, $uid, $slot = false ){
          if(isset($_POST['mwdb'])) {
              $server = str_replace('_', '.', $_POST['mwdb'] );
          }

          $details = base64_encode( $details );

          //$summary = "Edited with WSForm";
          /*
           * $this->addOption( 'summary', 'Additional text that will be added to the files imported History. [optional]', false, true, "s" );
		$this->addOption( 'action', 'What to do', true, true, "a" );
		$this->addOption( 'content', 'Page content', true, true );
		$this->addOption( 'title', 'Page title', true, true );
		$this->addOption( 'user', 'Your username. Will be added to the import log. [mandatory]', true, true, "u" );
		$this->addOption( 'use-timestamp', 'Use the modification date of the page as the timestamp for the edit, instead of time of import' );
		$this->addOption( 'rc', 'Place revisions in RecentChanges.' );
           */
          $cmd = 'SERVER_NAME=' . $server . ' php ' . __DIR__ . '/modules/maintenance/handlePostsToWiki.maintenance.php --action addPageToWiki';
          $cmd .= ' --content "' . $details.'"';
          $cmd .= ' --rc --title "' . $name.'"';
          $cmd .= ' --user '. $uid;
          if( $slot !== false ){
              $cmd .= ' --slot "' . $slot . '"';
          }
          $cmd .= ' --summary "' . $summary . '"';
          //echo $cmd;
          //die();
          $result = shell_exec( $cmd );
          //var_dump( $result );
//die();
          $res = explode('|', $result);
        // print_r($res);
         //die('ok');

          if($res[0] === 'ok' ) return array(true);
          if($res[0] === 'error' ) {
              $result = array();
              $result['received']['error'] = $res[1];
              return $result;
          }
      }

      function removeCarriageReturnFromContent( $content ){
          return str_replace("\r", '' , $content );
      }


    /**
     * Main function to save a Page into MediaWiki
     *
     * @param $name string Name/Title of the page
     * @param $details string PageContent
     * @param mixed $summary optional summary for a page. Default to false
     * @return array MediaWiki result
     */
    function savePageToWiki( $name, $details, $summary = false, $slot = false ) {
        global $wsuid;
        $details = $this->removeCarriageReturnFromContent( $details );
        if( $wsuid !== false && $this->app['use-api-user-only'] === 'no' ) {

            // We have a user, lets use Maintenance script to save page to wiki
            return $this->maintenanceSavePageToWiki( $name, $details, $summary, $wsuid, $slot );

        } else {
            $postdata = http_build_query([
                "action" => "query",
                "format" => "json",
                "meta" => 'tokens',
            ]);
            $result = $this->apiPost($postdata);
            if ($result['error']) {
                echo $result['error'];
                exit;
            }
            $result = $result['received'];
            $token = $result['query']['tokens']['csrftoken'];
        }
        if($summary === false) {
            $postdata = http_build_query([
                "action" => "edit",
                "format" => "json",
                "title" => $name,
                "text" => $details,
                "token" => $token,
                "bot" => $this->app['is-bot']
            ]);
        } else {
            $postdata = http_build_query([
                "action" => "edit",
                "format" => "json",
                "title" => $name,
                "text" => $details,
                "summary" => $summary,
                "token" => $token,
                "bot" => $this->app['is-bot']
            ]);
        }
          $result=$this->apiPost($postdata);
          if ($result['error'])  {
              echo $result['error'];
              exit;
          }
          return $result;
    }


    /**
     * @brief Upload a file on the server into MediaWiki.
     * This if the most easiest way to get a file into a Wiki
     *
     * @param $name string Name of the pagetitle file
     * @param $url string URL of the file on the server (no path, real url)
     * @param $details string Text that will place on the filepage in the Wiki
     * @param $comment string Comment attached to a file
     * @return array API result
     */
      function uploadFileToWiki( $name, $url, $details, $comment, $fileAndPath = false ) {
          global $wsuid;
          if ( $wsuid !== false && $this->app['use-api-user-only'] === 'no' ) {

              //$name, $fileAndPath, $content, $summary, $uid
              // We have a user, lets use Maintenance script to upload file to wiki
              $this->maintenanceUploadFileToWiki( $name, $fileAndPath, $details, $comment, $wsuid );
              return array(true);

          } else {
              $postdata = http_build_query( [
                  "action" => "query",
                  "format" => "json",
                  "meta"   => 'tokens',
              ] );
              $result   = $this->apiPost( $postdata );
              if ( $result['error'] ) {
                  echo $result['error'];
                  exit;
              }
              $result   = $result['received'];
              $token    = $result['query']['tokens']['csrftoken'];
              $postdata = http_build_query( [
                  "action"         => "upload",
                  "format"         => "json",
                  "comment"        => $comment,
                  "text"           => $details,
                  "filename"       => $name,
                  'url'            => $url,
                  "ignorewarnings" => true,
                  "token"          => $token
              ] );

              $result = $this->apiPost( $postdata );

              return $result;
          }
      }

      function clearJSON($tmp) {
        for ($i = 0; $i <= 31; ++$i) {
          $tmp = str_replace(chr($i), "", $tmp);
        }
        $tmp = str_replace(chr(127), "", $tmp);
        if (0 === strpos(bin2hex($tmp), 'efbbbf')) {
           $tmp = substr($tmp, 3);
        }
        return $tmp;
      }

    function googleSiteVerify( $secret, $token, $action ) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $secret, 'response' => $token)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode( $response, true );
        // verify the response
        if( $result["success"] == '1' && $result["action"] == $action && $result["score"] >= 0.5 ) {
            return array( "status" => true, "result" => $result );
        } else {
            return array( "status" => false, "result" => $result );
        }
    }

    /**
     * Make an actual POST/GET to the NediaWiki API
     *
     * @param array|string array or string of preconfigured data
     * @param bool $useGet if true do a GET otherwise a POST
     * @return mixed MediaWiki API result
     */
    function apiPost( $data, $useGet=false ){

        $ch = curl_init();

        if($this->usr !== false) {
            $tmp =
            array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_USERAGENT => $this->app["useragent"],
                CURLOPT_POST => true
            );
            curl_setopt_array($ch, $tmp);
        } else {
            curl_setopt_array($ch, $this->app["curloptions"]);
        }

        if($useGet) {
          curl_setopt($ch, CURLOPT_POST, 0);
        }
        curl_setopt($ch, CURLOPT_URL, $this->app["apiURL"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result['received']=json_decode($this->clearJSON(curl_exec($ch)), true);
        if(curl_errno($ch)) {
           $result['error'] =  "Error 003: " . curl_error($ch);
        } else $result['error']=false;

        return $result;
    }

    /**
     * Not used
     */
    function setLoginTokenCookie() {
        $postdata = http_build_query([
            "action" => "login",
            "format" => "json",
            "meta" => 'tokens',
        ]);
        $result=$this->apiPost($postdata);
        if ($result['error'])  {
            echo $result['error'];
            exit;
        }
        $_SESSION["logintoken"] = $result['query']['tokens']['logintoken'];
    }

    /**
     * Not used
     */
    function apiLogin($data) {
        $this->setLoginTokenCookie();
        $post_data['wpName']=$data['username'];
        $post_data['wpPassword']=$data['password'];
        $post_data['wploginattempt']="Log in";
        $post_data['wpEditToken'] = '+\\';
        $post_data['title']='Special:UserLogin';
        $post_data['authAction']='login';
        $post_data['force']="";
        $post_data['wpLoginToken']=$_SESSION["logintoken"];

        foreach ( $post_data as $key => $value) {
	        $post_items[] = $key . '=' . $value;
	    }
        $post_string = implode ('&', $post_items);
        $ch = curl_init();
        curl_setopt_array($ch, $this->app["logincurloptions"]);
        curl_setopt($ch, CURLOPT_URL, $data['login_url']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $result=json_decode(curl_exec($ch));
        if(curl_errno($ch)) {
            $result['error'] =  "Error 003: " . curl_error($ch);
        } else $result['error']=false;
        curl_close($ch);



    }


    /**
     * @brief Login to MediaWiki for use with the API
     *
     * All Params come from Class Variables
     *
     * @param bool $user Not used
     * @param bool $pass Not used
     * @param bool $rurl Not used
     *
     * @return array|mixed|void
     */
    function logMeIn ( $user=false, $pass=false, $rurl=false ) {
        if ( $this->app["username"] === false || $this->app["password"] === false ){
            $result = array();
            $result['error'] = 'No WSForm API username or password defined';
            return $result;
        }
        if($this->usr !== false) {
            return;
        } else {
            $postdata = http_build_query([
                "action" => "query",
                "format" => "json",
                "meta" => "tokens",
                "type" => "login",
            ]);
            $result = $this->apiPost($postdata);
            if ($result['error']) {
                echo $result['error'];
                exit;
            }
            $result = $result['received']['query'];
            if (!empty($result["tokens"]["logintoken"])) {
                $_SESSION["logintoken"] = $result["tokens"]["logintoken"];
                $postdata = http_build_query([
                    "action" => "login",
                    "format" => "json",
                    "lgname" => $this->app["username"],
                    "lgpassword" => $this->app["password"],
                    "lgtoken" => $_SESSION["logintoken"],
                ]);
                $result = $this->apiPost($postdata);
                if ($result['error']) {
                    echo $result['error'];
                    exit;
                }

            }
        }
    }


  function get_string_between_until_last($string, $start, $end){
       $string = " ".$string;
       $ini = strpos($string,$start);
        if ($ini == 0) return "";
        $ini += strlen($start);
         $len = strrpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
  }

	function get_string_between($string, $start, $end){
		$string = " ".$string;
		$ini = strpos($string,$start);
		if ($ini == 0) return "";
		$ini += strlen($start);
		$len = strrpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}

  function getEndPos($start,$txt) {
    $pos=false;
    $brackets=2;
    for($i=$start;$i<strlen($txt)+1;$i++) {
      if($txt[$i]=='{') $brackets++;
      if($txt[$i]=='}') $brackets--;
      if($brackets == 0) {
        $pos = $i;
        break;
      }
    }
    return $pos;
  }

  function getTemplateValueAndDelete( $name, $template ) {
      //echo "searching for $name";
      $regex= '#%ws_' . $name . '=(.*?)%#';
      preg_match( $regex, $template, $tmp);
      //echo "<pre>";
      //print_r($tmp);
      //echo "</pre>";
      if( isset( $tmp[1] ) ) {
        $tmp = $tmp[1];
      } else {
          $ret['val'] = false;
          $ret['tpl'] = $template;
      }
      //$tmp = $this->get_string_between( $template, '%ws_' . $name . '=' , '%' );
      //echo "<p>found : $tmp</p>";
      $ret = array();
      if ( $tmp !== "" ) {
          $ret['val'] = $tmp;
          $ret['tpl'] = str_replace( '%ws_' . $name . '=' . $tmp . '%', '', $template);
      } else $ret['val'] = false;

      return $ret;
  }

  function getStartPos($string,$start,$offset=0) {
  	$ini = strpos($string,$start,$offset);
  	if ($ini === false) return false;
  	$ini += strlen($start);
    return $ini;
  }

  function clearWhiteSpacePlusEOLs($txt) {
        return str_replace(array("\n", "\r", " "), '', $txt);
  }

  function checkTemplateValue($source,$start,$end,$find,$value) {
    if( substr_count( $source,$find.'='.$value,$start,( $end-$start-1 ) ) ) {

      return true;
    } else return false;
  }

    /**
     * Function used by the Edit page functions
     *
     * @param $source
     * @param $template
     * @param bool $find
     * @param bool $value
     * @return bool|string
     */
  function getTemplate($source,$template,$find=false,$value=false) {
    $multiple = substr_count($source, '{{'.$template );
    // template not found
    if($multiple == 0) return false;

    // 1 template found and no specific argument=value is needed
    if($multiple == 1 && $find===false) {
      $startPos = $this->getStartPos( $source, '{{'.$template );
      $endPos = $this->getEndPos( $startPos,$source );
      if($startPos !== false && $endPos !== false) {
        return substr($source,$startPos,($endPos-$startPos-1));
      } else return false;
    }

    // 1 template found, but we need to check for argument=value
    if( $multiple == 1 && $find !== false && $value !== false ) {
      $startPos = $this->getStartPos( $source, '{{'.$template );
      $endPos = $this->getEndPos( $startPos,$source );
      if($startPos !== false && $endPos !== false) {
          if($this->checkTemplateValue($source,$startPos,$endPos,$find,$value) !== false) {
            return substr($source,$startPos,($endPos-$startPos-1));
          } else return false;
      } else return false;
    }

    // We have multiple templates on the page, but no identifier
    if($multiple > 1 && $find===false) {
      return false;
    }

    // We have multiple templates on the page and we have an identifier
    if($multiple > 1 && $find!== false && $value !== false) {
      $offset = 0;
      for($t=0;$t<$multiple;$t++) {
        $startPos = $this->getStartPos( $source, '{{'.$template, $offset );
        $endPos = $this->getEndPos( $startPos, $source );
        if($startPos !== false && $endPos !== false) {
            if($this->checkTemplateValue($source,$startPos,$endPos,$find,$value) !== false) {
              return substr($source,$startPos,($endPos-$startPos-1));
            } else {
              $offset = $endPos;
            }
        } else return false;
      }
    }
    return false;
  }
}

?>
