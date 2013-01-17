<?php
/**
 * API module to kick users from Etherpad instances
 *
 * @file EtherPadAuth.php
 * @ingroup API
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class KickFromPad extends ApiBase {
	public function __construct ( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		global $wgUser;
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$epPad = EtherEditorPad::newFromId( $params['padId'] );
		$kickuser = User::newFromName( $params['user'] );

		$result->addValue(
			array( 'KickFromPad' ),
			'success',
			$epPad->kickUser( $wgUser->getName(), $kickuser->getName(), $kickuser->getId() )
		);
	}

	// @codeCoverageIgnoreStart
	public function getAllowedParams() {
		return array(
			'padId' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => false
			),
			'user' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => false
			),
		);
	}

	public function getParamDescription() {
		return array(
			'padId' => 'The ID (from the database) of the pad we need to kick from.',
			'user' => 'A valid username to kick.',
		);
	}

	public function getDescription() {
		return array(
			'API module for kicking users from pads created with EtherEditor.',
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=KickFromPad&padId=7&user=Trollolol657',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 0.2.2';
	}
	// @codeCoverageIgnoreEnd
}
