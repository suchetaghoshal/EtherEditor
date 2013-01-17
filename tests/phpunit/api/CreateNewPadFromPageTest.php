<?php
/**
 * Test API module to create a new pad from a page title.
 *
 * @file CreateNewPadFromPageTest.php
 *
 * @group API
 * @group Database
 * @group EtherEditor
 *
 * @since 0.3.0
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

require_once( 'EtherEditorApiTestCase.php' );

class CreateNewPadFromPageTest extends EtherEditorApiTestCase {
	function assertWeGetPad() {
		$data = $this->assertApiCallWorks(
			'CreateNewPadFromPage',
			array(
				'pageTitle' => $this->nameOfPad
			),
			array(
				'padId',
				'dbId'
			)
		);

		return EtherEditorPad::newFromId( $data['dbId'] );
	}

	function testPadCreateWithoutText() {
		$pad = $this->assertWeGetPad();

		$this->assertEquals( '', trim( $pad->getText() ) );

		$pad->deleteFromDB();
	}

	function testPadCreateWithText() {
		$title = Title::newFromText( $this->nameOfPad );
		$page = WikiPage::factory( $title );
		$page->doEdit( 'Some random text that will not exist later', '' );
		$text = $page->getRawText();

		$pad = $this->assertWeGetPad();

		$this->assertEquals( $text, trim( $pad->getText() ) );

		$pad->deleteFromDB();
	}
}
