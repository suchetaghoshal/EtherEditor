<?php
/**
 * API module to export data from Etherpad instances
 *
 * @file GetEtherPadText.php
 * @ingroup API
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class GetEtherPadText extends ApiBase {
	public function __construct ( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$epPad = EtherEditorPad::newFromId( $params['padId'] );

		$result->addValue(
			array( 'GetEtherPadText' ),
			'text',
			$epPad->getText()
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
			'padId' => 'The name of the pad to export (from the database).',
		);
	}

	public function getDescription() {
		return array(
			'API module for exporting pads created with EtherEditor.',
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=GetEtherPadText&padId=7',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 0.2.1';
	}
	// @codeCoverageIgnoreEnd
}

