<?php
/**
 * Test API module to fork Etherpad instances
 *
 * @file ForkEtherPadTest.php
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

class ForkEtherPadTest extends EtherEditorApiTestCase {
	function testForkOccursWithRightText() {
		$testText = 'If a fork is created with this text, the test will pass!';
		$epPad = $this->newOrigPad( $testText );

		$data = $this->assertApiCallWorks(
			'ForkEtherPad',
			array(
				'padId' => $epPad->getId()
			),
			array(
				'padId',
				'dbId',
				'sessionId'
			)
		);

		$dbId = $data['dbId'];
		$this->assertPadExists( $dbId );
		$this->assertPadHasText( $dbId, $testText );
		$this->assertIsAdmin( $dbId, $this->userName );

		$epPad->deleteFromDB();
		$epFork = EtherEditorPad::newFromId( $dbId );
		$epFork->deleteFromDB();
	}
}
