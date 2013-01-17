<?php
/**
 * Test that the basic requirements for EtherEditor are met
 *
 * @file EtherEditorApiTestCase.php
 *
 * @since 0.2.5
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class EtherEditorApiTestCase extends ApiTestCase {
	protected function userHasAuth( $epPad, $uid, $username ) {
		$epClient = EtherEditorPad::getEpClient();

		$initialString = 'The session did not get set';
		$actualSession = $initialString;
		$actualAuthor = $epClient->createAuthorIfNotExistsFor( $uid, $username )->authorID;
		$sessions = $epClient->listSessionsOfGroup( $epPad->getGroupId() );
		if ( !is_null( $sessions ) ) {
			foreach ( $sessions as $key => $value ) {
				if ( $value->authorID == $actualAuthor ) {
					$actualSession = $key;
				}
			}
		}
		return $actualSession != $initialString;
	}

	function setUp() {
		parent::setUp();
		$this->doLogin();
		$this->nameOfPad = strval( microtime( true ) );
		global $wgUser;
		$this->userId = $wgUser->getId();
		$this->userName = $wgUser->getName();
	}

	function tearDown() {
		unset( $this->nameOfPad );
		unset( $this->userId );
		unset( $this->userName );
		parent::tearDown();
	}

	function newOrigPad( $text='' ) {
		return EtherEditorPad::newFromNameAndText( $this->nameOfPad, $text, 0, false );
	}

	function newFork( $epPadId=null, $username=null) {
		if ( $username === null ) {
			$username = $this->userName;
		}
		if ( $epPadId === null and isset( $this->epPad ) ) {
			$epPadId = $this->epPad->getId();
		} elseif ( $epPadId === null ) {
			// If you're trying to fork null, you get a new pad. Congrats.
			return $this->newOrigPad();
		}
		return EtherEditorPad::newFromOldPadId( $epPadId, $username );
	}

	function assertPadExists( $dbId ) {
		$epPad = EtherEditorPad::newFromId( $dbId );
		$this->assertFalse( is_null( $epPad ) );
	}

	function assertPadHasText( $dbId, $padText ) {
		$epPad = EtherEditorPad::newFromId( $dbId );
		$this->assertEquals(
			$padText,
			trim( $epPad->getText() )
		);
	}

	function assertIsAdmin( $dbId, $userName ) {
		$epPad = EtherEditorPad::newFromId( $dbId );
		$this->assertTrue(
			$epPad->isAdmin( $userName )
		);
	}

	function assertApiCallWorks( $apiMethod, $args, $expected ) {
		$args['action'] = $apiMethod;

		$data = $this->doApiRequest( $args );

		$this->assertArrayHasKey( $apiMethod, $data[0] );
		foreach ( $expected as $ex ) {
			$this->assertArrayHasKey( $ex, $data[0][$apiMethod] );
		}
		return $data[0][$apiMethod];
	}

	function assertUserHasAuth( $epPad, $uid, $username ) {
		$this->assertTrue( $this->userHasAuth( $epPad, $uid, $username ) );
	}

	function assertUserNotHasAuth( $epPad, $uid, $username ) {
		$this->assertFalse( $this->userHasAuth( $epPad, $uid, $username ) );
	}
}
