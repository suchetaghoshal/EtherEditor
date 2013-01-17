<?php
/**
 * API module to get the current list of users connected to an Etherpad instance
 *
 * @file GetCurrentUsers.php
 * @ingroup API
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class GetCurrentUsers extends ApiBase {
	public function __construct ( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$epPad = EtherEditorPad::newFromId( $params['padId'] );

		$result->addValue(
			array( 'GetCurrentUsers' ),
			'users',
			$epPad->getConnectedUsers()
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
		);
	}

	public function getParamDescription() {
		return array(
			'padId' => 'The id of the pad (from the database).',
		);
	}

	public function getDescription() {
		return array(
			'API module for listing currently connected users on pads created with EtherEditor.',
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=GetCurrentUsers&padId=7',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 0.4.0';
	}
	// @codeCoverageIgnoreEnd
}

