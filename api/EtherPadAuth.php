<?php
/**
 * API module to authenticate to Etherpad instances
 *
 * @file EtherPadAuth.php
 * @ingroup API
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class EtherPadAuth extends ApiBase {
	public function __construct ( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		global $wgUser;
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$epPad = EtherEditorPad::newFromId( $params['padId'] );

		$result->addValue(
			array( 'EtherPadAuth' ),
			'sessionId',
			$epPad->authenticateUser( $wgUser )
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
			'padId' => 'The ID (from the database) of the pad for which authentication is needed.',
		);
	}

	public function getDescription() {
		return array(
			'API module for authenticating to pads created with EtherEditor.',
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=EtherPadAuth&padId=7',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 0.2.0';
	}
	// @codeCoverageIgnoreEnd
}

