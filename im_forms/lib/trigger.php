<?php namespace ImForms;

class ActionsTrigger
{
	private $controller;

	public $input;

	public function __construct() { $this->input = new Input(); }

	public function init($controller) { $this->controller = $controller;}

	public function watchAdminActions()
	{
		if($this->input->get->section == 'form' && $this->input->get->action == 'edit') {
			if($this->input->post->action == 'saveform') {
				if(true === $this->runAdminAction('check-form')) {
					$this->runAdminAction('save-form');
				}
			}
		// Deleting form from the form list view
		} else if(!$this->input->get->section && $this->input->get->delete) {
			$this->runAdminAction('delete-form');
		// Publish/Unpublish form
		} else if(!$this->input->get->section && $this->input->get->activate) {
			$this->runAdminAction('publish-form');
		// Check and save field data
		} else if($this->input->get->section == 'field' && $this->input->get->action == 'edit') {
			if($this->input->post->action == 'savefield')
			{
				if(true === $this->runAdminAction('check-field')) {
					$this->runAdminAction('save-field');
				}
			}
		}

	}

	protected function runAdminAction($action)
	{
		switch($action)
		{
			case 'check-form':
				return $this->controller->checkFormData();
				break;
			case 'save-form':
				return $this->controller->saveForm($this->input->get->edit);
				break;
			case 'delete-form':
				return $this->controller->deleteForm($this->input->get->delete);
				break;
			case 'publish-form':
				return $this->controller->publishForm($this->input->get->activate);
				break;
			case 'check-field':
				return $this->controller->checkFieldData();
				break;
			case 'save-field':
				return $this->controller->saveForm($this->input->get->form);
		}
	}

	public function watchFrontendActions()
	{
		if($this->input->post->imforms || $this->input->get->imforms) {
			$this->runFrontendAction('execute-module');
		}
	}

	protected function runFrontendAction($action)
	{
		switch($action)
		{
			case 'execute-module':
				return $this->controller->execute($this->input->post->imforms);
				break;
		}
	}
}

class Input
{
	public $post;
	public $get;
	public $whitelist;

	public function __construct()
	{
		$this->post = new Post();
		$this->get = new Get();
		$this->whitelist = new Whitelist();
		foreach($_POST as $key => $value) { $this->post->{$key} = $value; }
		foreach($_GET as $key => $value) { $this->get->{$key} = $value; }
	}
}

class Post
{
	/**
	 * Provides direct reference access to set values in the $data array
	 *
	 * @param string $key
	 * @param mixed $value
	 * return $this
	 *
	 */
	public function __set($key, $value) { $this->{$key} = $value;}
	public function __get($name) { return isset($this->{$name}) ? $this->{$name} : null;}
}

class Get
{
	/**
	 * Provides direct reference access to set values in the $data array
	 *
	 * @param string $key
	 * @param mixed $value
	 * return $this
	 *
	 */
	public function __set($key, $value) { $this->{$key} = $value; }
	public function __get($name) { return isset($this->{$name}) ? $this->{$name} : null; }
}

class Whitelist
{
	/**
	 * Provides direct reference access to set values in the $data array
	 *
	 * @param string $key
	 * @param mixed $value
	 * return $this
	 *
	 */
	public function __set($key, $value) { $this->{$key} = $value; }
	public function __get($name) { return isset($this->{$name}) ? $this->{$name} : null; }
}