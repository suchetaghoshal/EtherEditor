<?php
/**
 * Test API module to authenticate user to Etherpad instances
 *
 * @file EtherPadAuthTest.php
 *
 * @group API
 * @group Database
 * @group EtherEditor
 *
 * @since 0.2.5
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

require_once( 'EtherEditorApiTestCase.php' );

class EtherPadAuthTest extends EtherEditorApiTestCase {
	function setUp() {
		parent::setUp();
		$this->epPad = $this->newOrigPad();
	}

	function tearDown() {
		$this->epPad->deleteFromDB();
		unset( $this->epPad );
		parent::tearDown();
	}

	function testUserDoesNotGetAuthedUsually() {
		$this->assertUserNotHasAuth( $this->epPad, $this->userId, $this->userName );
	}

	function testUserGetsAuthed() {
		$data = $this->assertApiCallWorks(
			'EtherPadAuth',
			array(
				'padId' => $this->epPad->getId()
			),
			array(
				'sessionId'
			)
		);

		$this->assertUserHasAuth( $this->epPad, $this->userId, $this->userName );
	}
}
