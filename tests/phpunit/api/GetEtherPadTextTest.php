<?php
/**
 * API module to export data from Etherpad instances
 *
 * @file GetEtherPadTextTest.php
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

class GetEtherPadTextTest extends EtherEditorApiTestCase {
	function testTextGetsReturned() {
		$testText = 'If this text gets properly returned from the server, this test will pass!';
		$epPad = $this->newOrigPad( $testText );

		$data = $this->assertApiCallWorks(
			'GetEtherPadText',
			array(
				'padId' => $epPad->getId()
			),
			array(
				'text'
			)
		);

		$this->assertEquals(
			$testText,
			trim( $data['text'] )
		);
		$epPad->deleteFromDB();
	}
}
