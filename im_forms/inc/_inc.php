<?php if(!defined('IN_GS')){ die('you cannot load this page directly.'); }
if(defined('IN_GS') && !empty($_GET['id']) && $_GET['id'] == $im_forms_file) { define('IS_ADMIN_PANEL', true); }
define('IMF_PLUGIN_ID', $im_forms_file);

include(__DIR__.'/_Util.php');

function imFormsBuilder()
{
	global $language, $LANG;

	$dir = dirname(__DIR__);
	include($dir.'/lib/config.php');
	include($dir.'/lib/elements.php');
	include($dir.'/lib/processor.php');
	include($dir.'/lib/module.php');
	include($dir.'/lib/controller.php');
	include($dir.'/lib/factory.php');
	include($dir.'/lib/trigger.php');

	$processor = \ImForms\Factory::getProcessor();
	// Merge language stuff.
	// Unfortunately, i18n only supports 2 character-based file names
	if(function_exists('i18n_init')) {
		i18n_init();
		if($language) {
			$LANG = isset($processor->config->langs[$language]) ? $processor->config->langs[$language] : 'en_US';
		}
	}
	i18n_merge(IMF_PLUGIN_ID) || i18n_merge(IMF_PLUGIN_ID, 'en_US');
	$controller = new \ImForms\Controller($processor);
	\ImForms\Factory::setController($controller);
	if(function_exists('exec_action')) exec_action('imforms');
	$controller = \ImForms\Factory::getController();
	return $controller;
}