<?php
/**
 * Created by  : Open CSP
 * Project     : FlexForm
 * Filename    : SpecialMessages.php
 * Description :
 * Date        : 7-2-2024
 * Time        : 15:10
 */

namespace FlexForm\Specials\SpecialHelpers;

use FlexForm\Core\Messaging;
use MediaWiki\MediaWikiServices;

class SpecialMessages {

	/**
	 * @var validForms
	 */
	private validForms $vf;

	/**
	 * @param validForms $vF
	 */
	public function __construct( validForms $vF ) {
		$this->vf = $vF;
	}

	/**
	 * @return string
	 */
	public function renderTable(): string {
		$messaging = new Messaging();
		$messages = $messaging->getAllMessages();
		$title = wfMessage( 'flexform-messaging-list-title' )->text();
		$caption = wfMessage( 'flexform-messaging-list-caption', count( $messages ) );
		$headers = [];
		$h1 = wfMessage( 'flexform-messaging-list-header-user' )->text();
		$h2 = wfMessage( 'flexform-messaging-list-header-from' )->text();
		$h3 = wfMessage( 'flexform-messaging-list-header-type' )->text();
		$h4 = wfMessage( 'flexform-messaging-list-header-message-title' )->text();
		$h5 = wfMessage( 'flexform-messaging-list-header-message' )->text();
		$h6 = wfMessage( 'flexform-messaging-list-header-persistent' )->text();
		$h7 = wfMessage( 'flexform-messaging-list-header-added' )->text();
		$h8 = wfMessage( 'flexform-messaging-list-header-action' )->text();
		$headers[$h1] = false;
		$headers[$h2] = false;
		$headers[$h3] = 'uk-text-center';
		$headers[$h4] = false;
		$headers[$h5] = false;
		$headers[$h6] = 'uk-text-center';
		$headers[$h7] = 'uk-text-center';
		$headers[$h8] = 'uk-text-center';
		$rowCount = 0;

		$formHeader = '<form style="display:inline-block;" method="post">';
		$data = [];
		foreach ( $messages as $singleMessage ) {
			$form = $formHeader . '<input type="hidden" name="mId" value="' . $singleMessage['id'] . '">';
			$form .= $this->vf->renderGenericBtn(
				'',
				'minus-circle',
				wfMessage( 'flexform-messaging-list-action-delete' )->text()
			);
			$form .= '</form> ';
			$fUser = MediaWikiServices::getInstance()->getUserFactory()->newFromId( $singleMessage['user'] );
			$fFrom = MediaWikiServices::getInstance()->getUserFactory()->newFromId( $singleMessage['from'] );
			$fromUser = $fUser->getRealName() . '<br><i>' . $fUser->getName() . '</i>';
			$fromFrom = $fFrom->getRealName() . '<br><i>' . $fFrom->getName() . '</i>';
			$data[$rowCount] = [];
			$data[$rowCount][0]['value'] = $fromUser;
			$data[$rowCount][0]['class'] = false;
			$data[$rowCount][1]['value'] = $fromFrom;
			$data[$rowCount][1]['class'] = false;
			$data[$rowCount][2]['value'] = $singleMessage['type'];
			$data[$rowCount][2]['class'] = 'uk-text-center';
			$data[$rowCount][3]['value'] = $singleMessage['title'];
			$data[$rowCount][3]['class'] = false;
			$data[$rowCount][4]['value'] = $singleMessage['message'];
			$data[$rowCount][4]['class'] = false;
			if ( $singleMessage['persistent'] == 1 ) {
				$persistent = wfMessage( 'flexform-messaging-list-yes' );
			} else {
				$persistent = wfMessage( 'flexform-messaging-list-no' );
			}
			$data[$rowCount][5]['value'] = $persistent;
			$data[$rowCount][5]['class'] = 'uk-text-center';
			$data[$rowCount][6]['value'] = $singleMessage['added'];
			$data[$rowCount][6]['class'] = 'uk-text-center';
			$data[$rowCount][7]['value'] = $form;
			$data[$rowCount][7]['class'] = 'uk-text-center';
			$rowCount++;
		}
		$footer = [];
		$footer[0] = '';
		$footer[1] = '';
		$footer[2] = '';
		$footer[3] = '';
		$footer[4] = '';
		$footer[5] = '';
		$footer[6] = '';
		$footer[7] = '';
		return $this->vf->renderDefaultTable( $title, $caption, $headers, $data, $footer, null );
	}
}