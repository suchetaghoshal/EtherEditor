<?php
/**
 * Test API module to delete Etherpad forks
 *
 * @file DeleteEtherPadTest.php
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

class DeleteEtherPadTest extends EtherEditorApiTestCase {
	function doRequest( $dbId ) {
		$data = $this->assertApiCallWorks(
			'DeleteEtherPad',
			array(
				'padId' => $dbId
			),
			array(
				'authed',
				'success'
			)
		);
		return $data;
	}

	function testDeleteHappensWithIdealConditions() {
		$epPad = $this->newOrigPad();
		$epFork = $this->newFork( $epPad->getId() );

		$data = $this->doRequest( $epFork->getId() );

		$this->assertTrue( $data['authed'] );
		$this->assertEquals( $data['success'], 1 );
		$epPad->deleteFromDB();
		$this->assertEquals( $epFork->deleteFromDB(), 1 );
	}

	function testDeleteFailsWithoutAdmin() {
		$epPad = $this->newOrigPad();
		$epFork = $this->newFork( $epPad->getId(), '' );

		$data = $this->doRequest( $epFork->getId() );

		$this->assertFalse( $data['authed'] );
		$this->assertFalse( $data['success'] );
		$epPad->deleteFromDB();
		$this->assertEquals( $epFork->deleteFromDB(), 1 );
	}
}
