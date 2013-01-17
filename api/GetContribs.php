<?php
/**
 * API module to export data from Etherpad instances
 *
 * @file GetContribs.php
 * @ingroup API
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class GetContribs extends ApiBase {
	public function __construct ( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$epPad = EtherEditorPad::newFromId( $params['padId'] );

		$result->addValue(
			array( 'GetContribs' ),
			'contribs',
			$epPad->getContribs()
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
			'API module for listing contributors to pads created with EtherEditor.',
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=GetContribs&padId=7',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 0.3.0';
	}
	// @codeCoverageIgnoreEnd
}

