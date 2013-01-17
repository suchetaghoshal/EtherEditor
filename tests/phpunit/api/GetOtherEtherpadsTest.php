<?php
/**
 * Test API module to get the other pads from a pad ID.
 *
 * @file GetOtherEtherpadsTest.php
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

class GetOtherEtherpadsTest extends EtherEditorApiTestCase {
	function setUp() {
		parent::setUp();
		$this->epPad = $this->newOrigPad();
		$this->epFork = $this->newFork();
	}

	function tearDown() {
		$this->epPad->deleteFromDB();
		$this->epFork->deleteFromDB();
		unset( $this->epPad );
		unset( $this->epFork );
		parent::tearDown();
	}

	function testPadsGet() {
		$data = $this->assertApiCallWorks(
			'GetOtherEtherpads',
			array(
				'padId' => $this->epPad->getId()
			),
			array(
				'pads'
			)
		);

		$pads = array();
		foreach ( $data['pads'] as $pad ) {
			$pads[] = $pad->ep_pad_id;
		}

		$this->assertContains( $this->epFork->getEpId(), $pads );
		$this->assertNotContains( 'nonexistent', $pads );
	}
}
