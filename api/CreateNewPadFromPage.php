<?php
/**
 * API module to create a new pad for a page.
 *
 * @file CreateNewPadFromPage.php
 * @ingroup API
 *
 * @license GNU GPL v2+
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 */

class CreateNewPadFromPage extends ApiBase {
	public function __construct ( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$result = $this->getResult();
		$title = Title::newFromText( $params['pageTitle'] );
		$page = Article::newFromID( $title->getArticleID() );
		if ( isset( $page ) )  {
			$text = $page->fetchContent();
		} else {
			$text = '';
		}
		$baseRevision = $title->getLatestRevID();
		$epPad = EtherEditorPad::newFromNameAndText( $params['pageTitle'], $text, $baseRevision, true );

		$result->addValue(
			array( 'CreateNewPadFromPage' ),
			'padId',
			$epPad->getEpId()
		);
		$result->addValue(
			array( 'CreateNewPadFromPage' ),
			'dbId',
			$epPad->getId()
		);
	}

	// @codeCoverageIgnoreStart
	public function getAllowedParams() {
		return array(
			'pageTitle' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => false
			),
		);
	}

	public function getParamDescription() {
		return array(
			'pageTitle' => 'The title of the page you intend to edit in the new pad.',
		);
	}

	public function getDescription() {
		return array(
			'API module to create a new pad for a page.',
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=CreateNewPadFromPage&pageTitle=Main_Page',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 0.3.0';
	}
	// @codeCoverageIgnoreEnd
}
