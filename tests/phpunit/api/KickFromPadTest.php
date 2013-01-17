<?php
/**
 * Test API module to kick users from pads
 *
 * @file KickFromPadTest.php
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

class KickFromPadTest extends EtherEditorApiTestCase {
	function setUp() {
		parent::setUp();
		$this->kickuser = 'spammer';
		$this->kickid = 50;
		$this->epPad = $this->newOrigPad();
		$this->epFork = null;
	}

	function tearDown() {
		unset( $this->kickuser );
		unset( $this->kickid );
		$this->epPad->deleteFromDB();
		unset( $this->epPad );
		if ( $this->epFork !== null ) {
			$this->epFork->deleteFromDB();
		}
		unset( $this->epFork );
		parent::tearDown();
	}

	protected function makeRequest() {
		$this->epFork->authenticateUser( $this->kickuser, $this->kickid );
		$data = $this->assertApiCallWorks(
			'KickFromPad',
			array(
				'padId' => $this->epFork->getId(),
				'user' => $this->kickuser
			),
			array(
				'success'
			)
		);
		return $data['success'];
	}

	function assertRequestSucceeds() {
		$this->assertTrue( $this->makeRequest() );
	}

	function assertRequestFails() {
		$this->assertFalse( $this->makeRequest() );
	}

	function testKickHappensWithIdealConditions() {
		$this->epFork = $this->newFork();
		$this->assertRequestSucceeds();
	}

	function testKickFailsWithoutAdmin() {
		$this->epFork = $this->newFork( null, '' );
		$this->assertRequestFails();
	}
}
