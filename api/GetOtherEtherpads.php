<?php
/**
 * API module to list etherpad instances of a page
 *
 * @file GetOtherEtherpads.php
 * @ingroup API
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class GetOtherEtherpads extends ApiBase {
	public function __construct ( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$epPad = EtherEditorPad::newFromId( $params['padId'] );

		$result->addValue(
			array( 'GetOtherEtherpads' ),
			'pads',
			$epPad->getOtherPads()
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
			'API module to list etherpad instances of a page.',
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=GetOtherEtherpads&padId=7',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 0.3.0';
	}
	// @codeCoverageIgnoreEnd
}
