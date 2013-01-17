<?php
/**
 * EtherEditor extension
 *
 * @file
 * @ingroup Extensions
 *
 * @author Mark Holmquist <mtraceur@member.fsf.org>
 * @license GPL v2 or later
 * @version 0.3.0
 */


/* Setup */

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'EtherEditor',
	'author' => array( 'Mark Holmquist' ),
	'version' => '0.3.0',
	'url' => 'https://www.mediawiki.org/wiki/Extension:EtherEditor',
	'descriptionmsg' => 'ethereditor-desc',
);
$dir = dirname( __FILE__ );

foreach ( array(
		'EtherEditorHooks' => '/EtherEditorHooks',
		'EtherEditorPad' => '/includes/EtherEditorPad',
		'EtherpadLiteClient' => '/includes/EtherpadLiteClient',
		'GetEtherPadText' => '/api/GetEtherPadText',
		'ForkEtherPad' => '/api/ForkEtherPad',
		'DeleteEtherPad' => '/api/DeleteEtherPad',
		'EtherPadAuth' => '/api/EtherPadAuth',
		'GetCurrentUsers' => '/api/GetCurrentUsers',
		'GetContribs' => '/api/GetContribs',
		'KickFromPad' => '/api/KickFromPad',
		'GetOtherEtherpads' => '/api/GetOtherEtherpads',
		'CreateNewPadFromPage' => '/api/CreateNewPadFromPage',
		'SpecialEtherEditor' => '/includes/special/SpecialEtherEditor',
	) as $module => $path ) {
	$wgAutoloadClasses[$module] = $dir . $path . '.php';
}

$wgSpecialPages['EtherEditor'] = 'SpecialEtherEditor';
$wgSpecialPageGroups['EtherEditor'] = 'pagetools';

$wgHooks['UnitTestsList'][] = 'EtherEditorHooks::registerUnitTests';

$etherEditorTpl = array(
	'localBasePath' => $dir . '/modules',
	'remoteExtPath' => 'EtherEditor/modules',
	'group' => 'ext.etherEditor',
);

$wgResourceModules += array(
	'jquery.etherpad' => $etherEditorTpl + array(
		'scripts' => 'jquery.etherpad.js',
	),

	'ext.etherEditor' => $etherEditorTpl + array(
		'scripts' => array(
			'ext.etherEditor.js',
		),
		'styles' => array(
			'ext.etherEditor.css',
		),
		'messages' => array(
			'seconds',
			'minutes',
			'hours',
			'days',
			'ago',
			'ethereditor-session-created',
			'ethereditor-connected',
			'ethereditor-switch-to-session',
			'ethereditor-recover-session',
			'ethereditor-fork-button',
			'ethereditor-pad-list',
			'ethereditor-user-list',
			'ethereditor-kick-button',
			'ethereditor-delete-button',
			'ethereditor-summary-message',
			'ethereditor-collaborate-button',
		),
		'dependencies' => array(
			'mediawiki.user',
			'mediawiki.Uri',
			'jquery.etherpad',
			'jquery.cookie',
			'mediawiki.jqueryMsg',
		)
	),

	'ext.etherManager' => $etherEditorTpl + array(
		'scripts' => array(
			'ext.etherManager.js',
		),
		'styles' => array(
			'ext.etherManager.css',
		),
		'messages' => array(
			'ethereditor-fork-button',
			'ethereditor-delete-button'
		),
		'dependencies' => array(
			'mediawiki.user',
			'jquery.cookie',
		)
	)
);

$wgExtensionMessagesFiles['EtherEditor'] = $dir . '/EtherEditor.i18n.php';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'EtherEditorHooks::onSchemaUpdate';
$wgHooks['EditPage::showEditForm:initial'][] = 'EtherEditorHooks::editPageShowEditFormInitial';
$wgHooks['ArticleSaveComplete'][] = 'EtherEditorHooks::saveComplete';
$wgHooks['GetPreferences'][] = 'EtherEditorHooks::getPreferences';

$wgAPIModules['GetEtherPadText'] = 'GetEtherPadText';
$wgAPIModules['ForkEtherPad'] = 'ForkEtherPad';
$wgAPIModules['DeleteEtherPad'] = 'DeleteEtherPad';
$wgAPIModules['EtherPadAuth'] = 'EtherPadAuth';
$wgAPIModules['GetContribs'] = 'GetContribs';
$wgAPIModules['GetCurrentUsers'] = 'GetCurrentUsers';
$wgAPIModules['KickFromPad'] = 'KickFromPad';
$wgAPIModules['GetOtherEtherpads'] = 'GetOtherEtherpads';
$wgAPIModules['CreateNewPadFromPage'] = 'CreateNewPadFromPage';
