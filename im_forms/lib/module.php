<?php namespace ImForms;

class Module
{
	protected $processor;
	protected $trigger;

	public function __construct(Processor $processor) {
		$this->processor = $processor;
		$this->trigger = new ActionsTrigger();
		$this->input = $this->trigger->input;
	}
}