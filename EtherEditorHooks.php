<?php
/**
 * Hooks for EtherEditor extension
 *
 * @file
 * @ingroup Extensions
 */

class EtherEditorHooks {
	/**
	 * Abstraction of all possible ways to enable the EtherEditor
	 *
	 * @since 0.1.0
	 *
	 * @param User $user
	 * @return boolean
	 */
	protected static function isUsingEther( $user ) {
		return $user->isLoggedIn();
	}

	/**
	 * Check whether there are other editing sessions happening on this page
	 *
	 * @since 0.2.3
	 *
	 * @param Title $title
	 * @return boolean
	 */
	protected static function areOthersUsingEther( $title ) {
		$epPad = EtherEditorPad::newFromNameAndText( $title->getPrefixedURL(), '' );
		if ( $epPad->getOtherPads() ) {
			return true;
		}
		return false;
	}

	/**
	 * ArticleSaveComplete hook
	 *
	 * @since 0.0.1
	 *
	 * @param Article $article needed to find the title
	 * @param User $user
	 * @param string $text
	 * @param string $summary
	 * @param boolean $minoredit
	 * @param boolean $watchthis
	 * @param $sectionanchor deprecated
	 * @param integer $flags
	 * @param Revision $revision
	 * @param Status $status
	 * @param integer $baseRevId need this to find the padId
	 * @param boolean $redirect
	 *
	 * @return bool true
	 */
	public static function saveComplete( &$article, &$user, $text, $summary, $minoredit,
		$watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId ) {
		global $wgOut;
		$dbId = $wgOut->getRequest()->getInt( 'dbId', -1 );
		if ( $dbId != -1 ) {
			$epClient = EtherEditorPad::getEpClient();
			$epPad = EtherEditorPad::newFromId( $dbId );

			$groupId = $epPad->getGroupId();
			$sessions = $epClient->listSessionsOfGroup( $groupId );
			$authorId = $epClient->createAuthorIfNotExistsFor( $user->getId(), $user->getName() )->authorID;
			foreach ( (array) $sessions as $sess => $sinfo ) {
				if ( $sinfo->authorID == $authorId ) {
					$epClient->deleteSession( $sess );
				}
			}
			if ( $epClient->padUsersCount( $epPad->getEpId() ) == 0 ) {
				$epPad->deleteFromDB();
			}
		}
		return true;
	}

	/**
	 * EditPage::showEditForm:initial hook
	 *
	 * Adds the modules to the edit form
	 * Creates an etherpad if necessary
	 *
	 * @since 0.0.1
	 *
	 * @param EditPage $editPage page being edited
	 * @param OutputPage $output output for the edit page
	 *
	 * @return bool true
	 */
	public static function editPageShowEditFormInitial( $editPage, $output ) {
		global $wgEtherpadConfig, $wgUser, $wgLocalTZoffset;

		if ( self::isUsingEther( $wgUser ) ) {
			$apiPort = $wgEtherpadConfig['apiPort'];
			$apiHost = $wgEtherpadConfig['apiHost'];

			$title = $editPage->getTitle();
			$text = $editPage->getContent();
			$padId = $title->getPrefixedURL();
			if ( $text || $title->exists() ) {
				$baseRev = $editPage->getBaseRevision();
				if ( $baseRev ) {
					$baseRevId = $baseRev->getId();
				}
			} else {
				if ( $text == false )  {
					$text = '';
				}
				$baseRevId = 0;
			}

			$epPad = EtherEditorPad::newFromNameAndText( $padId, $text, $baseRevId );
			if ( $epPad->getBaseRevision() < $baseRevId ) {
				$epClient = EtherEditorPad::getEpClient();
				if ( $epClient->padUsersCount( $epPad->getEpId() )->padUsersCount == 0 ) {
					$epClient->setText( $epPad->getEpId(), $text );
				} else {
					// force creation of a new remote pad with a more different name
					$epPad = EtherEditorPad::newFromNameAndText( $padId, $text, $baseRevId, true );
				}
			}
			$sessionId = $epPad->authenticateUser( $wgUser );

			$output->addJsConfigVars( array(
				'wgEtherEditorDbId' => $epPad->getId(),
				'wgEtherEditorOtherPads' => $epPad->getOtherPads(),
				'wgEtherEditorApiHost' => $apiHost,
				'wgEtherEditorApiPort' => $apiPort,
				'wgEtherEditorPadUrl' => $wgEtherpadConfig['pUrl'],
				'wgEtherEditorPadName' => $epPad->getEpId(),
				'wgEtherEditorSessionId' => $sessionId ) );
			$output->addModules( 'ext.etherEditor' );
		} else if ( $wgUser->getBoolOption( 'ethereditor_enableether' )
			|| $output->getRequest()->getCheck( 'enableether' ) ) {
			throw new UserNotLoggedIn( 'ethereditor-cannot-nologin' );
		}

		return true;
	}

	/**
	 * GetPreferences hook
	 *
	 * Adds EtherEditor-releated items to the preferences
	 *
	 * @param $user User current user
	 * @param $preferences array list of default user preference controls
	 *
	 * @return bool true
	 */
	public static function getPreferences( $user, array &$preferences ) {
		$preferences['ethereditor_enableether'] = array(
			'type' => 'check',
			'label-message' => 'ethereditor-prefs-enable-ether',
			'section' => 'editing/advancedediting'
		);
		return true;
	}
	/**
	 * Schema update to set up the needed database tables.
	 *
	 * @since 0.1.0
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return bool true
	 */
	public static function onSchemaUpdate( $updater = null ) {
		$updater->addExtensionTable( 'ethereditor_pads', dirname( __FILE__ ) . '/sql/EtherEditor.sql' );

		// Add the group_id field
		$updater->addExtensionUpdate( array( 'addField', 'ethereditor_pads', 'group_id',
			dirname( __FILE__ ) . '/sql/AddGroupId.patch.sql', true ) );

		// Add the admin field
		$updater->addExtensionUpdate( array( 'addField', 'ethereditor_pads', 'admin_user',
			dirname( __FILE__ ) . '/sql/AddAdminField.patch.sql', true ) );

		// Add the kicked field
		$updater->addExtensionUpdate( array( 'addField', 'ethereditor_contribs', 'kicked',
			dirname( __FILE__ ) . '/sql/AddKickedField.patch.sql', true ) );

		// Add the is_old field
		$updater->addExtensionUpdate( array( 'addField', 'ethereditor_pads', 'base_revision',
			dirname( __FILE__ ) . '/sql/AddBaseRevision.patch.sql', true ) );

		// Make various changes for dealing with sessions (not pads)
		$updater->addExtensionUpdate( array( 'addField', 'ethereditor_pads', 'time_created',
			dirname( __FILE__ ) . '/sql/UpdateForSessions.patch.sql', true ) );

		return true;
	}

	/**
	 * Register unit tests.
	 *
	 * @since 0.2.5
	 */
	public static function registerUnitTests( &$files ) {
		$testDir = dirname( __FILE__ ) . '/tests/phpunit/';
		$files[] = $testDir . 'EtherEditorTest.php';
		$files[] = $testDir . 'api/GetEtherPadTextTest.php';
		$files[] = $testDir . 'api/EtherPadAuthTest.php';
		$files[] = $testDir . 'api/ForkEtherPadTest.php';
		$files[] = $testDir . 'api/KickFromPadTest.php';
		$files[] = $testDir . 'api/GetContribsTest.php';
		$files[] = $testDir . 'api/DeleteEtherPadTest.php';
		$files[] = $testDir . 'api/GetOtherEtherpadsTest.php';
		$files[] = $testDir . 'api/CreateNewPadFromPageTest.php';
		$files[] = $testDir . 'includes/EtherEditorPadTest.php';
		return true;
	}

}
