<?php if(!defined('IN_GS')){ die('you cannot load this page directly.'); }
if(defined('IN_GS') && !empty($_GET['id']) && $_GET['id'] == $im_forms_file) { define('IS_ADMIN_PANEL', true); }

include(__DIR__.'/_Util.php');

// language
i18n_merge($im_forms_file) || i18n_merge($im_forms_file, 'en_US');

function imFormsBuilder()
{
	$dir = dirname(__DIR__);
	include($dir.'/lib/config.php');
	include($dir.'/lib/elements.php');
	include($dir.'/lib/processor.php');
	include($dir.'/lib/module.php');
	include($dir.'/lib/controller.php');
	include($dir.'/lib/factory.php');
	include($dir.'/lib/trigger.php');

	$processor = \ImForms\Factory::getProcessor();
	$controller = new \ImForms\Controller($processor);
	\ImForms\Factory::setController($controller);
	if(function_exists('exec_action')) exec_action('imforms');
	$controller = \ImForms\Factory::getController();
	return $controller;
}