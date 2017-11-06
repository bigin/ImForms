<?php
$im_forms_file = basename(__FILE__, '.php');
include_once(__DIR__.'/'.$im_forms_file.'/inc/_inc.php');

register_plugin(
	basename($im_forms_file, '.php'),
	\ImForms\Util::i18n_r('plugin_name'),
	'0.1',
	'Juri Ehret',
	'http://ehret-studio.com',
	\ImForms\Util::i18n_r('plugin_description'),
	'pages',
	'im_forms_admin_init'
);


// TODO: Achtung das wird 2 mal aufgerufen?
if(defined('IS_ADMIN_PANEL'))
{
	register_style('imfstyle',
		$SITEURL.'plugins/'.$im_forms_file.'/css/admin/style.css',GSVERSION, 'screen');
	register_script('sortable', $SITEURL.'plugins/'.$im_forms_file.
		'/scripts/nestedSortable/jquery.mjs.nestedSortable.js', GSVERSION);

	queue_style('imfstyle',GSBACK);
	queue_script('sortable',GSBACK);
}

add_action('admin-pre-header', '__aj_imf_init');

add_action('pages-sidebar', 'createSideMenu',
	array($im_forms_file, \ImForms\Util::i18n_r('admin_sidebar_label')));

add_action( 'index-pretemplate', '__im_forms_init');

function im_forms_admin_init() {
	if(!defined('IS_ADMIN_PANEL')) return;
	$forms = imFormsBuilder();
	$forms->trigger->watchAdminActions();
	echo $forms->renderAdmin();
}

function __aj_imf_init() {
	if(!isset($_GET['imf_aj']) || !defined('IS_ADMIN_PANEL')) return;
	$forms = imFormsBuilder();
	exit();
}

global $imforms;
function __im_forms_init() {
	global $imforms;
	if($imforms) return $imforms;
	$imforms = imFormsBuilder();
	$imforms->trigger->watchFrontendActions();
	if($imforms->processor->config->autoFormOutput) {
		$imforms->autoFormOutput();
	}
	return $imforms;
}
