<?php
/**
 * API module to delete Etherpads
 *
 * @file DeleteEtherPad.php
 * @ingroup API
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class DeleteEtherPad extends ApiBase {
	public function __construct ( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		global $wgUser;
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$padId = $params['padId'];
		$epPad = EtherEditorPad::newFromId( $padId );

		$isAdmin = $epPad->isAdmin( $wgUser );
		$result->addValue(
			array( 'DeleteEtherPad' ),
			'authed',
			$isAdmin
		);
		$result->addValue(
			array( 'DeleteEtherPad' ),
			'success',
			$isAdmin ? $epPad->deleteFromDB() : false
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
			'padId' => 'The name of the pad to fork (from the database).',
		);
	}

	public function getDescription() {
		return array(
			'API module for exporting pads created with EtherEditor.',
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=ApiEtherEditor&padId=7',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 0.2.1';
	}
	// @codeCoverageIgnoreEnd
}
