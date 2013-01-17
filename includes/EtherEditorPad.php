<?php

/**
 * Class that represents a single pad.
 *
 * @file
 * @ingroup Extensions
 *
 * @since 0.1.0
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist < mtraceur@member.fsf.org >
 */

/**
 * Utility function for converting a database result to an array for JSON
 *
 * @since 0.2.2
 *
 * @param ResultWrapper $result the result object
 *
 * @return array an array of associative arrays of the results
 */
function resToArray( $result ) {
	$arr = array();
	foreach ( $result as $row ) {
		$arr[] = $row;
	}
	return $arr;
}

class EtherEditorPad {

	/**
	 * The ID of the pad.
	 * Either this matched a record in the ethereditor_pads table or is null.
	 *
	 * @since 0.1.0
	 * @var integer or null
	 */
	protected $id;

	/**
	 * The etherpad-generated ID of the pad.
	 * This name is used when we need to access the pad via the API.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $epid;

	/**
	 * The etherpad-generated group ID for this pad.
	 * This is used to authenticate users, so it's good to keep it around.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	protected $groupId;

	/**
	 * The admin of this pad.
	 *
	 * @since 0.2.2
	 * @var string
	 */
	protected $adminUser;

	/**
	 * The revision we started with on this pad.
	 *
	 * @since 0.2.3
	 * @var boolean
	 */
	protected $baseRevision;

	/**
	 * The time this pad was created.
	 * Uses the traditional YYYYMMDDHHMMSS format.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	protected $timeCreated;

	/**
	 * Whether the pad is public or not.
	 *
	 * @since 0.1.0
	 * @var boolean
	 */
	protected $publicPad;

	/**
	 * Static EP client.
	 *
	 * @since 0.2.3
	 * @var EtherpadLiteClient
	 */
	protected static $epClient;

	/**
	 * Create a new pad.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $id
	 * @param integer $epid
	 * @param string  $groupId
	 * @param string  $pageTitle
	 * @param string  $adminUser
	 * @param boolean $baseRevision
	 * @param boolean $publicPad
	 */
	public function __construct( $id, $epid, $groupId, $pageTitle, $adminUser, $baseRevision, $publicPad ) {
		$this->id = sha1( sha1( $pageTitle ) . strval( $baseRevision) );
		$this->epid = $epid;
		$this->groupId = $groupId;
		$this->pageTitle = $pageTitle;
		$this->adminUser = $adminUser;
		$this->baseRevision = $baseRevision;
		$this->timeCreated = null;
		$this->publicPad = $publicPad;
		$this->writeToDB();
	}

	/**
	 * Returns an EtherpadLiteClient from the current $wgEtherPadConfig values
	 *
	 * @since 0.2.3
	 *
	 * @return EtherpadLiteClient
	 */
	public static function getEpClient() {
		if ( !self::$epClient ) {
			global $wgEtherpadConfig;
			$apiBackend = 'localhost';
			if ( isset( $wgEtherpadConfig['apiBackend'] ) ) {
				$apiBackend = $wgEtherpadConfig['apiBackend'];
			}
			$apiPort = '9001';
			if ( isset( $wgEtherpadConfig['apiPort'] ) ) {
				$apiPort = $wgEtherpadConfig['apiPort'];
			}
			$apiBaseUrl = '/api';
			if ( isset( $wgEtherpadConfig['apiUrl'] ) ) {
				$apiBaseUrl = $wgEtherpadConfig['apiUrl'];
			}
			$apiUrl = 'http://' . $apiBackend . ':' . $apiPort . $apiBaseUrl;
			$apiKey = $wgEtherpadConfig['apiKey'];
			self::$epClient = new EtherpadLiteClient( $apiKey, $apiUrl );
		}
		return self::$epClient;
	}

	/**
	 * Returns the public pad with specified title,
	 * or a new pad with the specified text if there is no such pad.
	 *
	 * @since 0.2.0
	 *
	 * @param string $pageTitle
	 * @param string $text
	 * @param string $baseRevision
	 *
	 * @return EtherEditorPad
	 */
	public static function newFromNameAndText( $pageTitle, $text='', $baseRevision=0, $forceNewRemote=false ) {
		return self::newFromDB( array(
			'page_title' => $pageTitle,
			'public_pad' => 1
		), $text, $baseRevision, $forceNewRemote );
	}

	/**
	 * Returns a new pad with the same text as the old pad.
	 *
	 * @since 0.2.0
	 *
	 * @param string $padId the ID for the old pad
	 * @param User $user the user who will be administrator
	 *
	 * @return EtherEditorPad
	 */
	public static function newFromOldPadId( $padId, $username='' ) {
		$oldPad = self::newFromId( $padId );
		return self::newFromDB( array(
			'pad_id' => -1,
			'page_title' => $oldPad->getPageTitle(),
			'admin_user' => $username,
			'base_revision' => $oldPad->getBaseRevision(),
			'public_pad' => 1
		), $oldPad->getText(), $oldPad->getBaseRevision() );
	}

	/**
	 * Returns the pad with specified ID, or false if there is no such pad.
	 *
	 * @since 0.1.0
	 *
	 * @param integer $id
	 *
	 * @return EtherEditorPad or false
	 */
	public static function newFromId( $id ) {
		return self::newFromDB( array( 'pad_id' => $id ) );
	}

	/**
	 * Returns a new instance of EtherEditorPad built from a database result
	 * obtained by doing a select with the provided conditions on the pads table.
	 * If no pad matches the conditions and the conditions cannot be artifically
	 * met, false will be returned.
	 *
	 * @since 0.1.0
	 *
	 * @param array $conditions the stuff to put into the database
	 * @param string $text the text to put into the pad right away
	 * @param integer $currentRevision the current revision of the page
	 *
	 * @return EtherEditorPad or false
	 */
	protected static function newFromDB( array $conditions, $text='', $baseRevision=0, $forceNewRemote=false ) {
		$dbr = wfGetDB( DB_SLAVE );

		if ( $forceNewRemote ) {
			$conditions[] = 'base_revision >= ' . $baseRevision;
		}
		$pad = $dbr->selectRow(
			'ethereditor_pads',
			array(
				'pad_id',
				'ep_pad_id',
				'group_id',
				'page_title',
				'admin_user',
				'base_revision',
				'public_pad'
			),
			$conditions,
			__METHOD__
		);
		if ( !$pad ) {
			$conditions['extra_title'] = '';
			$conditions['base_revision'] = $baseRevision;
			$extra = 0;
			if ( $forceNewRemote || ( isset( $conditions['pad_id'] ) && $conditions['pad_id'] == -1 ) ) {
				unset( $conditions['pad_id'] );
				$others = self::getOtherPads( 0, $conditions['page_title'] );
				$success = false;
				// Here's how this works:
				// 1. Grab all the existing forks.
				// 2. Try a set of "extra title" information, making sure none of the existing pads have that title.
				// 3. If we find an existing one, that *should be* unique. Remove it, so the dataset is smaller for the next run-through.
				while ( !$success ) {
					$success = true; // test condition
					$extra += 1;
					$tryname = 'copy_' . strval( $extra );
					$found = false;
					foreach ( $others as $i => $other ) {
						$thisname = substr( $other->ep_pad_id, 19 );
						if ( $thisname == $tryname ) {
							unset( $others[$i] );
							$success = false;
							break;
						}
					}
				}
			}
			if ( $extra != 0 ) {
				$conditions['extra_title'] = 'copy_' . strval( $extra );
			} else {
				$conditions['extra_title'] = 'original';
			}
			return self::newRemotePad( $conditions, $text );
		}

		return new self(
			$pad->pad_id,
			$pad->ep_pad_id,
			$pad->group_id,
			$pad->page_title,
			$pad->admin_user,
			$pad->base_revision,
			$pad->public_pad
		);
	}

	/**
	 * Makes a new remote pad.
	 *
	 * @since 0.1.0
	 *
	 * @param array $conditions the stuff to put into the database
	 * @param string $text the text to put into the pad right away
	 *
	 * @return EtherEditorPad or false
	 */
	protected static function newRemotePad( $conditions, $text='' ) {
		$padId = $conditions['extra_title'];

		$epClient = self::getEpClient();
		$groupId = $epClient->createGroupIfNotExistsFor( $conditions['page_title'] . $padId )->groupID;
		try {
			$epClient->createGroupPad( $groupId, $padId, $text );
		} catch ( Exception $e ) {
			// this is just because the pad already exists, probably nothing to worry about
		}
		if ( !isset( $conditions['admin_user'] ) ) {
			$conditions['admin_user'] = '';
		}
		return new self(
			null,
			$groupId . '$' . $padId,
			$groupId,
			$conditions['page_title'],
			$conditions['admin_user'],
			$conditions['base_revision'],
			$conditions['public_pad']
		);
	}

	/**
	 * Authenticates a user to the group.
	 * Also adds them to the list of collaborators in the database
	 *
	 * @since 0.2.0
	 *
	 * @param User|string $user the user to authenticate, or their username
	 * @param int $uid the ID of the user to authenticate, not used if $user is of type User.
	 *
	 * @return int|bool sessionId or false
	 */
	public function authenticateUser( $user, $uid=0 ) {
		$username = '';
		if ( is_string( $user ) ) {
			$username = $user;
		} else {
			$username = $user->getName();
			$uid = $user->getId();
		}
		if ( !$this->isKicked( $username ) ) {
			$epClient = self::getEpClient();
			$authorId = $epClient->createAuthorIfNotExistsFor( $uid, $username )->authorID;

			$this->addToContribs( $username, $authorId );

			return $epClient->createSession( $this->groupId, $authorId, time() + 3600 )->sessionID;
		}
		return false;
	}

	/**
	 * Kicks a user from the pad.
	 * Also removes them from the list of collaborators in the database
	 *
	 * @since 0.2.2
	 *
	 * @param string $username the user doing the kicking
	 * @param string $kickusername the username to kick
	 * @param int $kickuserid the user id to kick
	 *
	 * @return int|bool sessionId or false
	 */
	public function kickUser( $username, $kickusername, $kickuserid ) {
		$isAdmin = $this->isAdmin( $username );
		if ( $isAdmin ) {
			if ( $this->isKicked( $kickusername ) ) {
				return true;
			}
			$epClient = self::getEpClient();
			$authorId = $epClient->createAuthorIfNotExistsFor( $kickuserid, $kickusername )->authorID;
			$sessions = (array) $epClient->listSessionsOfAuthor( $authorId );
			foreach ( $sessions as $sid => $sess ) {
				if ( $sess->groupID == $this->groupId ) {
					$epClient->deleteSession( $sid );
				}
			}

			$this->removeFromContribs( $kickusername );
		}
		return $isAdmin; // The user not being admin is the only possible error
	}

	/**
	 * Check whether a user is admin
	 *
	 * @since 0.2.2
	 *
	 * @param string $username the user to check
	 *
	 * @return boolean
	 */
	public function isAdmin( $username ) {
		return $this->adminUser == $username;
	}

	/**
	 * Check whether a user is kicked
	 *
	 * @since 0.2.2
	 *
	 * @param string $username the user to check
	 *
	 * @return boolean
	 */
	protected function isKicked( $username ) {
		$dbr = wfGetDB( DB_SLAVE );
		$contrib = $dbr->selectField(
			'ethereditor_contribs',
			'kicked',
			array(
				'pad_id' => $this->id,
				'username' => $username
			)
		);
		return $contrib != 0;
	}

	/**
	 * Adds a user to the list of contributors
	 *
	 * @since 0.2.1
	 *
	 * @param string $username the username to add
	 * @param string $authorId the authorId returned by Etherpad
	 *
	 * @return boolean success indicator
	 */
	protected function addToContribs( $username, $authorId ) {
		$dbr = wfGetDB( DB_SLAVE );
		$contrib = $dbr->selectRow(
			'ethereditor_contribs',
			array(
				'contrib_id',
				'username'
			),
			array(
				'pad_id' => $this->id,
				'username' => $username
			)
		);

		if ( !$contrib ) {
			$dbw = wfGetDB( DB_MASTER );

			return $dbw->insert(
				'ethereditor_contribs',
				array(
					'pad_id' => $this->id,
					'username' => $username,
					'ep_user_id' => $authorId,
					'has_contributed' => 1
				),
				__METHOD__
			);
		}
	}

	/**
	 * Removes a user from the list of contributors
	 *
	 * @since 0.2.2
	 *
	 * @param string $username the username to remove
	 *
	 * @return boolean success indicator
	 */
	protected function removeFromContribs( $username ) {
		$dbw = wfGetDB( DB_MASTER );

		$d1 = $dbw->update(
			'ethereditor_contribs',
			array(
				'kicked' => 1
			),
			array(
				'pad_id' => $this->id,
				'username' => $username
			),
			__METHOD__
		);

		return $d1;
	}

	/**
	 * Returns the ID of the pad.
	 *
	 * @since 0.1.0
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Returns the Etherpad-generated ID of the pad.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function getEpId() {
		return $this->epid;
	}

	/**
	 * Returns the title of the page being edited in the pad.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function getPageTitle() {
		return $this->pageTitle;
	}

	/**
	 * Returns the base revision for the pad.
	 *
	 * @since 0.2.3
	 *
	 * @return boolean
	 */
	public function getBaseRevision() {
		return $this->baseRevision;
	}

	/**
	 * Returns if the pad is public.
	 *
	 * @since 0.1.0
	 *
	 * @return boolean
	 */
	public function getIsPublic() {
		return $this->publicPad;
	}

	/**
	 * Returns the group ID for the pad.
	 *
	 * @since 0.1.0
	 *
	 * @return boolean
	 */
	public function getGroupId() {
		return $this->groupId;
	}

	/**
	 * Returns the text of the pad.
	 *
	 * @since 0.2.0
	 *
	 * @return string the text of the pad
	 */
	public function getText() {
		$epClient = self::getEpClient();
		try {
			return $epClient->getText( $this->epid )->text;
		} catch ( Exception $e ) {
			// the pad was somehow lost, not a problem, return empty
			return '';
		}
	}

	/**
	 * Write the pad to the DB.
	 * If it's already there, it'll be updated, else it'll be inserted.
	 *
	 * @since 0.1.0
	 *
	 * @return boolean Success indicator
	 */
	public function writeToDB() {
		if ( is_null( $this->id ) ) {
			return $this->insertIntoDB();
		}
		else {
			return $this->updateInDB();
		}
	}

	/**
	 * Insert the pad into the DB.
	 *
	 * @since 0.1.0
	 *
	 * @return boolean Success indicator
	 */
	protected function insertIntoDB() {
		$dbw = wfGetDB( DB_MASTER );

		$this->timeCreated = wfTimestamp( TS_MW );

		$success = $dbw->insert(
			'ethereditor_pads',
			array(
				'ep_pad_id' => $this->epid,
				'group_id' => $this->groupId,
				'page_title' => $this->pageTitle,
				'admin_user' => $this->adminUser,
				'base_revision' => $this->baseRevision,
				'time_created' => $this->timeCreated,
				'public_pad' => $this->publicPad,
			),
			__METHOD__,
			array( 'pad_id' => $this->id )
		);

		if ( $success ) {
			$this->id = $dbw->insertId();
		}

		return $success;
	}

	/**
	 * Update the pad in the DB.
	 *
	 * @since 0.1.0
	 *
	 * @return boolean Success indicator
	 */
	protected function updateInDB() {
		$dbw = wfGetDB( DB_MASTER );

		$success = $dbw->update(
			'ethereditor_pads',
			array(
				'ep_pad_id' => $this->epid,
				'group_id' => $this->groupId,
				'page_title' => $this->pageTitle,
				'admin_user' => $this->adminUser,
				'base_revision' => $this->baseRevision,
				'public_pad' => $this->publicPad,
			),
			array( 'pad_id' => $this->id ),
			__METHOD__
		);

		return $success;
	}

	/**
	 * Delete the pad from the DB (when present).
	 *
	 * @since 0.1.0
	 *
	 * @return boolean Success indicator
	 */
	public function deleteFromDB() {
		$epClient = self::getEpClient();
		try {
			$epClient->deletePad( $this->epid );
		} catch ( Exception $e ) {
			// this just means the pad already got deleted. No problem.
		}

		$dbw = wfGetDB( DB_MASTER );

		$d1 = $dbw->delete(
			'ethereditor_pads',
			array( 'pad_id' => $this->id ),
			__METHOD__
		);

		$dbw = wfGetDB( DB_MASTER );

		$d1 &= $dbw->delete(
			'ethereditor_contribs',
			array(
				'pad_id' => $this->id
			),
			__METHOD__
		);

		return $d1;
	}

	/**
	 * Get other pads for this page, if they exist
	 *
	 * @since 0.2.0
	 *
	 * @param integer the minimum base revision to have (usually the current one, default 0)
	 *
	 * @return array Array of pad IDs
	 */
	public function getOtherPads( $minRevision=0, $pageTitle='' ) {
		$dbr = wfGetDB( DB_SLAVE );

		$results = resToArray( $dbr->select(
			'ethereditor_pads',
			array(
				'pad_id',
				'ep_pad_id',
				'admin_user',
				'group_id',
				'time_created'
			),
			array(
				'base_revision >= ' . $minRevision,
				'page_title' => $pageTitle == '' ? $this->pageTitle : $pageTitle,
				'public_pad' => '1'
			),
			__METHOD__
		) );
		$epClient = self::getEpClient();
		foreach ( $results as $session ) {
			$session->users_connected = $epClient->padUsersCount( $session->ep_pad_id )->padUsersCount;
		}
		array_splice( $results, 10 );
		return $results;
	}

	/**
	 * Get the current list of contributors to this pad. Could be big!
	 *
	 * @since 0.2.1
	 *
	 * @return array Array of contributors
	 */
	public function getContribs() {
		$dbr = wfGetDB( DB_SLAVE );

		return resToArray( $dbr->select(
			'ethereditor_contribs',
			array(
				'contrib_id',
				'username'
			),
			array(
				'pad_id' => $this->id,
				'has_contributed' => 1
			),
			__METHOD__
		) );
	}

	/**
	 * Get the current list of contributors to this pad. Could be big!
	 *
	 * @since 0.4
	 *
	 * @return array Array of contributors
	 */
	public function getConnectedUsers() {
		$epClient = self::getEpClient();
		return $epClient->padUsers( $this->getEpId() )->padUsers;
	}

	/**
	 * Get all pads by the page title, or just all pads if no title
	 *
	 * @since 0.3.0
	 *
	 * @return array Array of pads
	 */
	 public static function getAllByPageTitle( $pageTitle=null ) {
		$dbr = wfGetDB( DB_SLAVE );

		$epClient = self::getEpClient();

		$conditions = array(
			'public_pad' => '1'
		);

		if ( $pageTitle != null ) {
			$conditions['page_title'] = $pageTitle;
		}

		$pads = resToArray( $dbr->select(
			'ethereditor_pads',
			array(
				'pad_id',
				'page_title',
				'base_revision',
				'ep_pad_id',
				'admin_user',
				'group_id'
			),
			$conditions,
			__METHOD__
		) );

		$pages = array();

		foreach ( $pads as $pad ) {
			$pad->users_connected = $epClient->padUsersCount( $pad->ep_pad_id )->padUsersCount;
			if ( !isset( $pages[$pad->page_title] ) ) {
				$title = Title::newFromText( $pad->page_title );
				$pages[$pad->page_title] = $title->getLatestRevID();
			}
			if ( $pad->base_revision == $pages[$pad->page_title] ) {
				$pad->base_revision = wfMessage( 'ethereditor-current' )->text();
			} else {
				$pad->base_revision = wfMessage( 'ethereditor-outdated' )->text();
			}
		}

		return $pads;
	 }
}
