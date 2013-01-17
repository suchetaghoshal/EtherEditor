<?php
/**
 * Test the main library for dealing with pads.
 *
 * @file EtherEditorPadTest.php
 *
 * @group Database
 *
 * @since 0.2.5
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class EtherEditorPadTest extends MediaWikiTestCase {
	function setUp() {
		parent::setUp();
		$this->nameOfPad = strval( time() );
		$this->username = 'user' . $this->nameOfPad;
		$this->uid = time() % 100;
		$this->testText = 'If this is the text in the pad, then everything is good!';
		$this->epPad = EtherEditorPad::newFromNameAndText( $this->nameOfPad, $this->testText, 40, false );
		$this->epClient = EtherEditorPad::getEpClient();
	}

	function tearDown() {
		unset( $this->nameOfPad );
		unset( $this->username );
		unset( $this->uid );
		if ( isset( $this->epClient ) ) unset( $this->epClient );
		if ( isset( $this->epPad ) ) {
			$this->epPad->deleteFromDB();
			unset( $this->epPad );
		}
		parent::tearDown();
	}

	function testCreatePad() {
		$apparentText = trim( $this->epClient->getText( $this->epPad->getEpId() )->text );
		$this->assertEquals( $this->testText, $apparentText );
		$this->assertEquals( $this->testText, trim( $this->epPad->getText() ) );
	}

	function testAuthToPad() {
		$maybeSession = $this->epPad->authenticateUser( $this->username, $this->uid );
		// Make sure that there's only one instance of the user in contribs list after this call.
		$this->epPad->authenticateUser( $this->username, $this->uid );
		$authorId = $this->epClient->createAuthorIfNotExistsFor( $this->uid, $this->username )->authorID;
		$sessions = $this->epClient->listSessionsOfGroup( $this->epPad->getGroupId() );
		$this->assertObjectHasAttribute( $maybeSession, $sessions );
		$contribs = array();
		foreach ( $this->epPad->getContribs() as $contrib ) {
			if ( $contrib->username == $this->username ) {
				$this->assertArrayNotHasKey( $this->username, $contribs );
			}
			$contribs[$contrib->username] = true;
		}
	}

	function testFetchPad() {
		$maybePad = EtherEditorPad::newFromId( $this->epPad->getId() );
		$this->assertInstanceOf( 'EtherEditorPad', $maybePad );
		$this->assertEquals( $this->testText, trim( $maybePad->getText() ) );
	}

	function testGetters() {
		$this->assertEquals( $this->nameOfPad, $this->epPad->getPageTitle() );
		$this->assertEquals( 40, $this->epPad->getBaseRevision() );
		$this->assertEquals( 1, $this->epPad->getIsPublic() );
	}

	function testForkingAction() {
		$epFork = EtherEditorPad::newFromOldPadId( $this->epPad->getId(), $this->username );
		$this->assertArrayHasKey( 0, $this->epPad->getOtherPads(), "The original pad doesn't show up in the list of pads. Wrong." );
		$this->assertArrayHasKey( 1, $this->epPad->getOtherPads(), "The fork didn't happen, or wasn't reported in the database, or getOtherPads is broken." );
		$this->assertArrayNotHasKey( 2, $this->epPad->getOtherPads() );
		$this->assertEquals( trim( $this->epPad->getText() ), trim( $epFork->getText() ) );
		$this->assertTrue( $epFork->isAdmin( $this->username ) );
		$this->assertTrue( $epFork->kickUser( $this->username, 'doesnotexist', 50 ) );
		$this->assertTrue( $epFork->authenticateUser( 'doesexist', 51 ) != false );
		$this->assertTrue( $epFork->kickUser( $this->username, 'doesexist', 51 ) );
		$this->assertTrue( $epFork->kickUser( $this->username, 'doesexist', 51 ) );
		$this->assertFalse( $epFork->authenticateUser( 'doesexist', 51 ) );
		$this->epClient->deletePad( $epFork->getEpId() );
		$this->assertEquals( '', $epFork->getText() );
		$otherEpFork = EtherEditorPad::newFromOldPadId( $this->epPad->getId(), $this->username );
		$this->assertNotEquals( $otherEpFork->getEpId(), $epFork->getEpId() );
		$this->assertEquals( $epFork->deleteFromDB(), 1 );
		$this->assertEquals( $otherEpFork->deleteFromDB(), 1 );
	}

	function testAutoFork() {
		$epAutoFork = EtherEditorPad::newFromNameAndText( $this->nameOfPad, $this->testText, 42, true );
		$apparentText = trim( $this->epClient->getText( $epAutoFork->getEpId() )->text );
		$this->assertEquals( $this->testText, $apparentText );
		$this->assertEquals( $this->testText, trim( $epAutoFork->getText() ) );
		$this->assertEquals( $epAutoFork->deleteFromDB(), 1 );
	}

	function testGetAllByPageTitle() {
		$pads = EtherEditorPad::getAllByPageTitle();
		$this->assertNotEquals( $pads, array() );
		foreach ( $pads as $pad ) {
			$this->assertObjectHasAttribute( 'users_connected', $pad );
			break;
		}
		$oldPad = EtherEditorPad::newFromNameAndText( $this->nameOfPad, '', $this->epPad->getBaseRevision() - 1, true );
		$pads = EtherEditorPad::getAllByPageTitle( $this->nameOfPad );
		$this->assertNotEquals( $pads, array() );
		foreach ( $pads as $pad ) {
			if ( $pad->pad_id == $oldPad->getId() ) {
				$this->assertEquals( $pad->base_revision, wfMessage( 'ethereditor-outdated' )->text() );
			} else if ( $pad->pad_id == $this->epPad->getId() ) {
				$this->assertEquals( $pad->base_revision, wfMessage( 'ethereditor-current' )->text() );
			}
		}
		$this->assertEquals( $oldPad->deleteFromDB(), 1 );
	}
}
