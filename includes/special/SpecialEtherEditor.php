<?php
/**
 * Special:EtherEditor
 *
 * Easy to use interface for managing pads.
 *
 * @file
 * @ingroup SpecialPage
 */

class SpecialEtherEditor extends SpecialPage {
	// $request is the request (usually wgRequest)
	// $par is everything in the URL after Special:EtherEditor.
	public function __construct( $request = null, $par = null ) {
		parent::__construct( 'EtherEditor', 'pagetools' );
	}

	/**
	 * Check that the user is logged in
	 *
	 * @since 0.3.0
	 *
	 * @param User $user
	 * @return boolean
	 */
	protected function isUserLogged() {
		return $this->getUser()->isLoggedIn();
	}

	/**
	 * Replaces default execute method
	 * @param $subPage, e.g. the "foo" in Special:EtherEditor/foo. In this case, it's the title of the page to manage.
	 */
	public function execute( $subPage ) {
		$this->setHeaders();
		$this->outputHeader();

		if ( !$this->isUserLogged() ) {
			throw new UserNotLoggedIn( 'ethereditor-cannot-nologin' );
		}

		$req = $this->getRequest();

		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'ethereditor-manager-title' ) );

		// fallback for non-JS
		$out->addHTML(
				'<noscript>'
			.	'	<p class="errorbox">' . $this->msg( 'ethereditor-js-off' )->text() . '</p>'
			.	'</noscript>'
		);

		// global javascript variables
		$this->addJsVars( $subPage );

		// dependencies (css, js)
		$out->addModuleStyles( 'ext.etherManager' );
		$out->addModules( 'ext.etherManager' );

		$out->addHTML( $this->getEtherManager() );
	}

	/**
	 * Display an error message.
	 *
	 * @since 1.2
	 *
	 * @param string $message
	 */
	protected function displayError( $message ) {
		$this->getOutput()->addHTML( Html::element(
			'span',
			array( 'class' => 'errorbox' ),
			$message
		) . '<br /><br /><br />' );
	}

	/**
	 * Adds some global variables for our use, as well as initializes the manager.
	 *
	 * @param subpage, e.g. the "foo" in Special:EtherEditor/foo
	 */
	public function addJsVars( $subPage ) {
		global $wgSitename, $wgEtherpadConfig;

		$config = $wgEtherpadConfig;
		unset( $config['apiKey'] );
		if ( $subPage != null ) {
			$config['pageTitle'] = $subPage;
		}

		$epPads = EtherEditorPad::getAllByPageTitle( $subPage );
		$this->getOutput()->addScript(
			Skin::makeVariablesScript(
				array(
					'EtherEditorConfig' => $config,
					'EtherEditorPads' => $epPads,
					'wgSiteName' => $wgSitename
				)
			)
		);
	}

	/**
	 * Return the basic HTML structure for the entire page
	 * Will be enhanced by the javascript to actually do stuff
	 * @return {String} html
	 */
	protected function getEtherManager() {
		return
			'<div id="ethereditor">'
		.	'	<div id="ethereditor-page-list">'
		.	'		<div class="ethereditor-page">'
		.	'			<div class="ethereditor-page-header">'
		.	'				<h2 class="ethereditor-page-title"></h2>'
		.	'				<button class="ethereditor-create-pad">Create pad for this page</button>'
		.	'			</div>'
		.	'			<table class="ethereditor-page-table">'
		.	'				<tr>'
		.	'					<th>' . $this->msg( 'ethereditor-pad-title' )->text() . '</th>'
		.	'					<th>' . $this->msg( 'ethereditor-base-revision' )->text() . '</th>'
		.	'					<th>' . $this->msg( 'ethereditor-users-connected' )->text() . '</th>'
		.	'					<th>' . $this->msg( 'ethereditor-admin-controls' )->text() . '</th>'
		.	'				</tr>'
		.	'				<tr class="ethereditor-pad">'
		.	'					<td class="pad-name"></td>'
		.	'					<td class="pad-baserev"></td>'
		.	'					<td class="pad-users"></td>'
		.	'					<td class="pad-ctrls"></td>'
		.	'				</tr>'
		.	'			</table>'
		.	'		</div>'
		.	'	</div>'
		.	'</div>';
	}
}
