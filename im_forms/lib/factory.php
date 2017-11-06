<?php namespace ImForms;

class Factory
{
	protected static $config;
	protected static $processor;
	protected static $controller;

	private static function buildProcessor() {
		self::$config = !self::$config ? Util::buildConfig() : '';
		self::$processor = new Processor(self::$config);
	}

	public static function getProcessor() {
		if(self::$processor) return self::$processor;
		self::buildProcessor();
		return self::$processor;
	}

	public static function setController($controller) {
		self::$controller = $controller;
		self::$controller->init();
	}

	public static function getController() { return self::$controller; }
}