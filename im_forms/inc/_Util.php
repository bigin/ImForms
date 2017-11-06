<?php namespace ImForms;

class Util
{
	public static $key = 'id';

	public static function i18n_r($key)
	{
		global $im_forms_file;
		return \i18n_r($im_forms_file.'/'.$key);
	}

	/**
	 * Build SimpleCatalog configuration
	 *
	 * @return Config object
	 */
	public static function buildConfig()
	{
		global $im_forms_file;
		$config = new Config();
		if(!file_exists(__DIR__.'/config.php')) { include(dirname(__DIR__).'/install/installer.php'); }
		include(__DIR__.'/config.php');
		if(file_exists(__DIR__.'/custom.config.php')) { include(__DIR__.'/custom.config.php'); }
		$config->pluginRoot = dirname(__DIR__);
		$config->pluginId = $im_forms_file;
		return $config;
	}

}