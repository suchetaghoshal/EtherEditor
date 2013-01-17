<?php
/**
 * Test API module to get the contributors to a pad
 *
 * @file GetContribsTest.php
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

class GetContribsTest extends EtherEditorApiTestCase {
	function testContribsGet() {
		$epPad = $this->newOrigPad();
		$epPad->authenticateUser( $this->userName, $this->userId );
		$epPad->authenticateUser( 'helper', 50 );

		$data = $this->assertApiCallWorks(
			'GetContribs',
			array(
				'padId' => $epPad->getId()
			),
			array(
				'contribs'
			)
		);

		$contribs = array();
		foreach ( $data['contribs'] as $contrib ) {
			$contribs[] = $contrib->username;
		}

		$this->assertContains( $this->userName, $contribs );
		$this->assertContains( 'helper', $contribs );
		$this->assertNotContains( 'spammer', $contribs );

		$epPad->deleteFromDB();
	}
}
