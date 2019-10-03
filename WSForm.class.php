<?php

/**
 *
 * WSForm rendering class
 * Sen-Sai
 */

class WSForm {
  public $pageID   = 0;
  public $userID   = 0;
  public $userName = null;
  private $dbName = 'WBRate';

  // get all the details
  public function __construct( $pageID ) {
      global $wgUser;

      $this->pageID = $pageID;
      $this->userName = $wgUser->getName();
      $this->userID = $wgUser->getID();
  }


}

?>
