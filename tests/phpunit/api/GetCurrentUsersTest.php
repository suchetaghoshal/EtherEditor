<?php
/**
 * Test API module to get the current users in a pad
 *
 * @file GetCurrentUsersTest.php
 *
 * @group API
 * @group Database
 * @group EtherEditor
 *
 * @since 0.4.0
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

require_once( 'EtherEditorApiTestCase.php' );

class GetCurrentUsersTest extends EtherEditorApiTestCase {
	function testEmptyUsersGet() {
		$epPad = $this->newOrigPad();
		$epPad->authenticateUser( $this->userName, $this->userId );
		$epPad->authenticateUser( 'helper', 50 );

		$data = $this->assertApiCallWorks(
			'GetCurrentUsers',
			array(
				'padId' => $epPad->getId()
			),
			array(
				'users'
			)
		);

		$users = array();
		foreach ( $data['users'] as $user ) {
			$users[] = $user;
		}

		$this->assertEmpty( $users );

		$epPad->deleteFromDB();
	}
}
