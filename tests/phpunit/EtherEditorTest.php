<?php
/**
 * Test that the basic requirements for EtherEditor are met
 *
 * @file EtherEditorTest.php
 *
 * @since 0.2.5
 *
 * @group EtherEditor
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class EtherEditorTest extends MediaWikiTestCase {

	/**
	 * Verify the global configuration looks sane enough
	 */
	function testHasEtherEditorConfig() {
		global $wgEtherpadConfig;

		$this->assertFalse( is_null( $wgEtherpadConfig ),
			"\$wgEtherpadConfig is not set, please configure the extension!" );
		$this->assertTrue( is_array( $wgEtherpadConfig ),
			"\$wgEtherpadConfig is not an array, please configure the extension properly!" );
		$this->assertArrayHasKey( 'apiKey', $wgEtherpadConfig,
			"\$wgEtherpadConfig does not have the API key, please add it!" );
	}

	/**
	 * @depends testHasEtherEditorConfig
	 */
	function testHasEtherpadLiteRunning() {
		global $wgEtherpadConfig;

		$epClient = EtherEditorPad::getEpClient();
		$this->assertNotNull( $epClient, "EtherpadLiteClient is broken somehow!" );

		$padName = 'This_string_should_almost_never_be_set_anywhere';
		$testText = 'This is a test.';
		$success = false;
		try {
			$epClient->createPad( $padName, $testText );
			$success = true;
		} catch ( Exception $e ) {
			try {
				$epClient->deletePad( $padName );
				$epClient->createPad( $padName, $testText );
				$success = true;
			} catch ( Exception $e ) {
				// this means the test failed miserably, success doesn't get set
			}
		}
		$this->assertTrue( $success, "Etherpad Lite is not running!" );

		$realText = trim( $epClient->getText( $padName )->text );
		$this->assertEquals( $testText, $realText,
			"The EPL API returned a strange value. This shouldn't happen." );
	}
}
