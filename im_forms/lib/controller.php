<?php namespace ImForms;

class Controller extends Module
{
	/**
	 * @var $processor - The processor instance
	 */
	public $processor;

	/**
	 * @var $trigger - The actions trigger
	 */
	public $trigger;

	/**
	 * @var $input - The input class
	 */
	public $input;

	/**
	 * @var input sanitizer
	 */
	public $sanitizer;

	/**
	 * @var array - Array of default form elements with ID's
	 */
	public $defaultFormElements = array(
		0 => 'Input',
		1 => 'Fieldset',
		2 => 'Label',
		3 => 'Wrapper',
		4 => 'Textarea',
		5 => 'ReCaptcha',
		6 => 'Button',
		7 => 'DelayBlock',
		8 => 'AjaxBlock',
	);

	/**
	 * Initializes some instances and includes librarys that we work with
	 *
	 */
	public function init()
	{
		include($this->processor->config->pluginRoot.'/tpl/admin.php');
		!file_exists($this->processor->config->pluginRoot.'/tpl/custom.admin.php')
			or include($this->processor->config->pluginRoot.'/tpl/custom.admin.php');

		$this->trigger->init($this);
		$this->parser = $this->processor->getTemplateParser();
		\MsgReporter::$dir = $this->processor->config->pluginId;
		$this->sanitizer = $this->processor->getSanitizer();
	}

	/**
	 * Method retrieves unrendered form by name
	 *
	 * @param $name
	 *
	 * @return null
	 */
	public function getForm($name)
	{
		$formItem = $this->processor->getSimpleItemByFormName($name);
		if($formItem && $formItem->name == $name && $formItem->active == 1) {
			return $this->processor->unserialize($formItem);
		}
		return null;
	}


	/**
	 * Automatic form output on pages with the placeholder [[imform form-name]] in the
	 * content. This method overrides global GS $content variable, requires 'get_header()'
	 * and 'get_page_content()' in template.
	 *
	 */
	public function autoFormOutput()
	{
		global $content;

		preg_match('%\[\[( *)imforms.*( *)\]\]%', $content, $res);
		if(!isset($res[0])) return;

		$var = $res[0];
		$params = explode(' ', trim(str_replace(array('[[', ']]'), '',  $res[0])), 2);
		if(empty($params[1])) return;

		// Getting our form object
		$form = ($this->processor->currentForm) ? $this->processor->currentForm :
			$this->getForm($this->sanitizer->pageName($params[1]));
		if(!$form) {
			$message = $this->parser->render(Util::i18n_r('form_name_notfound_error'),
				array('form_name' => '<strong>'.$this->sanitizer->pageName($params[1]).'</strong>'));
			$content = preg_replace('%(.*?)['.$var.'](.*?)%', $message, $content);
			return;
		}

		/*add_action('theme-header', function($form) {
				echo $form->renderResources();
			}, array($form)
		);*/
		/**
		 * Todo: This is a temporary solution as long as add_action() does not work with PHP 7 >
		 */
		global $plugins;
		if(is_array($plugins)) {
			array_unshift( $plugins,
				array(
					'hook' => 'theme-header',
					'function' => function($form) {
						echo $form->renderResources();
					},
					'args' => array($form),
					'file' => 'controller.php',
					'line' => null
				)
			);
		}

		$msgs = $this->renderSection('Messages');
		// Cache enabled?
		if($this->processor->config->imFormsCache) {
			$slug = get_page_slug(false);
			$sectionCache = $this->processor->getCache();
			if(!$output = $sectionCache->get($slug.'-'.$form->name, $this->processor->config->cacheTime)) {
				$output = $form->render();
				$sectionCache->save($output);
			}
		} else {
			$output = $form->render();
		}

		// Replace page content placeholders with the form output
		$content = preg_replace('%(.*?)['.$var.'](.*?)%',
			"$msgs\r\n".$output, $content
		);
		return;
	}


	/**
	 * Execute a custom form processor module
	 *
	 */
	public function execute($form_name)
	{
		$formItem = $this->processor->getSimpleItemByFormName(
			$this->sanitizer->pageName($form_name)
		);
		if(!$formItem || $formItem->active != 1) return;

		$form = $this->processor->unserialize($formItem);
		$moduleName = $this->sanitizer->fieldName($form->formtype);
		if(!array_key_exists($form->formtype, $this->processor->config->formProcessors)) return;
		if(!file_exists($this->processor->config->pluginRoot.'/module/'.$moduleName.'/'.$moduleName.'.php')) {
			echo $this->parser->render(Util::i18n_r('module_not_exist'), array('module_name' =>
				'<strong>'.$moduleName.'</strong>'));
			return;
		}

		require($this->processor->config->pluginRoot.'/module/'.$moduleName.'/'.$moduleName.'.php');

		$execerror = false;
		$module = null;
		if(class_exists('ImForms\\'.$moduleName)) {
			$className = 'ImForms\\'.$moduleName;
			$this->processor->currentItemId = $formItem->id;
			$this->processor->currentForm = $form;
			$module = new $className($this->processor);
			$module->__execute();
			$execerror = $module->isError();
		}

		// Is Ajax ?
		if($this->input->post->imforms_ajax || $this->input->get->imforms_ajax) {

			$status = (!$execerror) ? 1 : 0;
			$msgs = $this->renderSection('Messages');

			header('Content-type: application/json; charset=utf-8');
			echo json_encode(array('status' => $status, 'msgs' => $msgs));
			exit();
		}
	}


	/**
	 * This method renders the whole admin section
	 *
	 * @return string
	 */
	public function renderAdmin()
	{
		$output = $this->renderSection('AdminHeader');
		$output .= $this->renderSection('AdminBody');
		$output = $this->renderSection('AdminMessages', array($output));
		return $output;
	}


	/**
	 *
	 * @param $sectionName
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function renderSection($sectionName, $params = array())
	{
		$method = '__render'.$sectionName;
		return !empty($params) ? $this->{$method}($params) : $this->{$method}();
	}

	/**
	 * Renders Frontend errors and notifications
	 */
	private function __renderMessages()
	{
		if(\MsgReporter::msgs()) { return \MsgReporter::buildMsg(); }
		return;
	}

	/**
	 * Renders the admin header section:
	 *
	 * <div id="manager-header">
	 *      <h3 class="floated">...</h3>
	 *      ...
	 * </div>
	 *
	 *  @return string
	 */
	private function __renderAdminHeader()
	{
		if(!$this->input->get->section) {
			return $this->parser->render($this->listViewHeader, array(
					'plugin_name' => Util::i18n_r('plugin_name'),
					'plugin_id' => $this->processor->config->pluginId
				)
			);
		} else if($this->input->get->section == 'form' || $this->input->get->section == 'field') {
			$link1 = '';
			$link2 = '';
			if($this->input->get->section == 'form') {
				$link1 = 'load.php?id='.$this->processor->config->pluginId;
			} else if($this->input->get->section == 'field') {
				$link1 = 'load.php?id='.$this->processor->config->pluginId.
					'&section=form&action=edit&edit='.(int)$this->input->get->form;
				$link2 = $this->parser->render($this->link, array(
						'link' => 'load.php?id='.$this->processor->config->pluginId,
						'class' => 'btn checkChangedbutton',
						'link_text' => Util::i18n_r('to_overview_link_text')
					)
				);
			}


			return $this->parser->render($this->formViewHeader, array(
					'plugin_name' => Util::i18n_r('plugin_name'),
					'plugin_id' => $this->processor->config->pluginId,
					'link_1' => $link1,
					'prev_save_form_link' => Util::i18n_r('prev_save_form_link'),
					'overview_link' => $link2
				)
			);
		}
	}

	/**
	 * Renders body of the admin section
	 *
	 * <div id="itemContent" class="manager-wrapper">...</div>
	 *
	 * @return string - A rendered sections template
	 */
	private function __renderAdminBody()
	{
		// Form list
		if(!$this->input->get->section) {
			return $this->renderSection('AdminFormList');
		}
		// Form edit/create section
		elseif($this->input->get->section == 'form') {
			if($this->input->get->action == 'edit') {
				return $this->renderSection('AdminFormEditor');
			}
		}
		// Field edit/create section
		elseif($this->input->get->section == 'field') {
			if($this->input->get->action == 'edit') {
				return $this->renderSection('AdminFieldEditor');
			}
		}
	}

	/**
	 * This method renders the category list view only
	 *
	 * @return string
	 */
	private function __renderAdminFormList()
	{
		$rows = $this->renderSection('AdminFormListRows');

		return $this->parser->render($this->formListWrapper, array(
				'plugin_id' => $this->processor->config->pluginId,
				'form_name_label' => Util::i18n_r('form_name_label'),
				'form_created_label' => Util::i18n_r('form_created_label'),
				'form_modified_label' => Util::i18n_r('form_modified_label'),
				'form_active_label' => Util::i18n_r('form_active_label'),
				'item_rows' => $rows,
				'create_form_button_label' => Util::i18n_r('create_form_button_label')
			)
		);
	}

	/**
	 * Renders form rows
	 */
	private function __renderAdminFormListRows()
	{
		$rows = '';
		$result = $this->processor->getAllForms();

		if(isset($result))
		{
			foreach($result as $form)
			{
				$rows .= $this->parser->render($this->formRow, array(
						'item_id' => $form->id,
						'plugin_id' => $this->processor->config->pluginId,
						'item_title' => (strlen($form->name) > 30) ?
							substr($form->name, 0, 30).'...' : $form->name,
						'active' => !empty((int)$form->active) ? 'fa-check-square-o' : 'fa-square-o',
						'created' => date((string) $this->processor->config->timeFormat, (int)$form->created),
						'modified' => ($form->updated) ?
							date((string) $this->processor->config->timeFormat, (int)$form->updated) : ''
					)
				);
			}
		}
		return $rows;
	}



	private function __renderAdminFormEditor()
	{
		$currentForm = ($this->processor->currentForm) ? $this->processor->currentForm :
			$this->processor->getFormById($this->input->get->edit);

		$formprops = '';

		$formprops = $this->parser->render($this->formPropertyRow, array(
				'id' => 'name',
				'label' => Util::i18n_r('form_name_label'),
				'type' => 'text',
				'name' => 'name',
				'value' => $currentForm->name,
			)
		);

		// Select form type
		$options = '';
		foreach($this->processor->config->formProcessors as $class => $name) {
			$options .= $this->parser->render($this->formPropertyOption, array(
					'value' => $this->sanitizer->text($class),
					'selected' => ($currentForm->formtype == $class) ? ' selected' : '',
					'label' => $this->sanitizer->text($name)
				)
			);
		}

		$formprops .= $this->parser->render($this->formPropertySelectRow, array(
				'id' => 'formtype',
				'label' => Util::i18n_r('form_type_label'),
				'name' => 'formtype',
				'options' => $options
			)
		);

		// Form action
		$formprops .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'formaction',
				'label' => Util::i18n_r('form_action_label'),
				'type' => 'text',
				'name' => 'formaction',
				'value' => $currentForm->action
			)
		);

		// Select form method
		$options = $this->parser->render($this->formPropertyOption, array(
				'value' => 1,
				'selected' => ((strtolower($currentForm->method) == 'post') ? ' selected="selected" ' : ''),
				'label' => 'POST'
			)
		);
		$options .= $this->parser->render($this->formPropertyOption, array(
				'value' => 2,
				'selected' => ((strtolower($currentForm->method) == 'get') ? ' selected="selected" ' : ''),
				'label' => 'GET'
			)
		);
		$formprops .= $this->parser->render($this->formPropertySelectRow, array(
				'id' => 'method',
				'label' => Util::i18n_r('form_method_label'),
				'name' => 'method',
				'options' => $options
			)
		);

		// Select enctype
		$options = $this->parser->render($this->formPropertyOption, array(
				'value' => '',
				'selected' => '',
				'label' => '&nbsp;'
			)
		);
		$options .= $this->parser->render($this->formPropertyOption, array(
				'value' => 'application/x-www-form-urlencoded',
				'selected' => ((strtolower($currentForm->enctype) == 'application/x-www-form-urlencoded') ?
					' selected="selected" ' : ''),
				'label' => 'application/x-www-form-urlencoded'
			)
		);
		$options .= $this->parser->render($this->formPropertyOption, array(
				'value' => 'multipart/form-data',
				'selected' => ((strtolower($currentForm->enctype) == 'multipart/form-data') ?
					' selected="selected" ' : ''),
				'label' => 'multipart/form-data'
			)
		);
		$options .= $this->parser->render($this->formPropertyOption, array(
				'value' => 'text/plain',
				'selected' => ((strtolower($currentForm->enctype) == 'text/plain') ?
					' selected="selected" ' : ''),
				'label' => 'text/plain'
			)
		);
		$formprops .= $this->parser->render($this->formPropertySelectRow, array(
				'id' => 'enctype',
				'label' => Util::i18n_r('form_enctype_label'),
				'name' => 'enctype',
				'options' => $options
			)
		);

		// accept-charset
		$formprops .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'charset',
				'label' => Util::i18n_r('form_accept_charset_label'),
				'type' => 'text',
				'name' => 'charset',
				'value' => empty($currentForm->charset) ? '' : $currentForm->charset
			)
		);

		// novalidate form
		$formprops .= $this->parser->render($this->formPropertyCheckboxRow, array(
				'id' => 'novalidate',
				'label' => Util::i18n_r('form_novalidate_label'),
				'type' => 'checkbox',
				'name' => 'novalidate',
				'value' => 1,
				'checked' => !empty($currentForm->novalidate) ? ' checked="checked"' : ''
			)
		);

		// css class
		$formprops .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'class',
				'label' => Util::i18n_r('form_class_label'),
				'type' => 'text',
				'name' => 'class',
				'value' => !empty($currentForm->class) ? $currentForm->class : ''
			)
		);

		// css id
		$formprops .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'id',
				'label' => Util::i18n_r('form_id_label'),
				'type' => 'text',
				'name' => 'id',
				'value' => !empty($currentForm->id) ? $currentForm->id : ''
			)
		);

		// style
		$formprops .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'style',
				'label' => Util::i18n_r('form_style_label'),
				'type' => 'text',
				'name' => 'style',
				'value' => !empty($currentForm->style) ? $currentForm->style : ''
			)
		);

		// resources
		$formprops .= $this->parser->render($this->formPropertyAreaRow, array(
				'id' => 'resources',
				'label' => Util::i18n_r('form_resources_label'),
				'type' => 'text',
				'name' => 'resources',
				'value' => !empty($currentForm->resources) ?
					htmlspecialchars(stripslashes($currentForm->resources)) : ''
			)
		);

		$ui_sortable_class = '';
		$block = '';
		if(!empty($currentForm->elements)) {
			$block = $this->buildWrapperBlock($currentForm);
			$ui_sortable_class = ' ui-sortable';
		}

		/**
		 * Nested Sortable library info:
		 * https://github.com/mjsarfatti/nestedSortable
		 * https://github.com/ilikenwf/nestedSortable
		 *
		 *
		protected $defaultFormElements = array(
			0 => 'Input',
			1 => 'Fieldset',
			2 => 'Label',
			3 => 'Wrapper',
			4 => 'Textarea',
			5 => 'ReCaptcha',
			6 => 'Button'
		);
		 */
		$asm_options = '';
		$ol_options = '';
		$selected_options = '';
		foreach($this->defaultFormElements as $id => $type) {
			$class = '';
			if($id != 1 && $id != 2 && $id != 3) {
				$class = 'mjs-nestedSortable-no-nesting';
			}
			$asm_options .= $this->parser->render($this->formFieldAsmOption, array(
					'rel' => $class,
					'value' => $id,
					'label' => Util::i18n_r(strtolower($type).'_element_label')
				)
			);
		}

		$formIdUrl = '';
		if($this->input->get->edit) {
			$formIdUrl = '&edit='.(int)$this->input->get->edit;
		}

		return $this->parser->render($this->formEdotorWrapper, array(
				'form_editor_action' => 'load.php?id='.$this->processor->config->pluginId.'&section=form&action=edit'.$formIdUrl,
				'form_attributes_label' => Util::i18n_r('form_attributes_label'),
				'edit_form_attributes_button' => Util::i18n_r('edit_form_attributes_button'),
				'formprops' => $formprops,
				'form_fields_label' => Util::i18n_r('form_fields_label'),
				'select_fields_label' => Util::i18n_r('select_fields_label'),
				'order_fields_info' =>  Util::i18n_r('order_fields_info'),
				'ui_sortable' => $ui_sortable_class,
				'selected_elements' => $block,
				'elements_value' => '',
				'asm_options' => $asm_options,
				'ol_options' => $ol_options,
				'save_form_prompt' => Util::i18n_r('save_form_prompt'),
				'form_confirm_exit' => Util::i18n_r('form_confirm_exit'),
				'count' => ($currentForm->lastId + 1),
				'edit_link_text' => Util::i18n_r('edit_link_text'),
				'plugin_id' => $this->processor->config->pluginId,
				'prev_save_form_link' => Util::i18n_r('prev_save_form_link'),
				'save_form_button' => Util::i18n_r('save_form_button')
			)
		);
	}

	private function buildWrapperBlock($element, $block = '')
	{
		if(!empty($element->elements)) {
			foreach($element->elements as $rec_id => $rec_element) {
				$block .= $this->buildBlock($rec_element, $rec_id);
			}
		}

		return $block;
	}


	private function buildBlock($element, $id, $buffer = '')
	{
		if(!empty($element->elements)) {
			$buffer .= $this->buildWrapperBlock($element, $buffer);
		}

		$block = '';

		$elementName = $this->processor->getElementName($element);
		if(!in_array($elementName, $this->defaultFormElements)) return;

		$key = array_search($elementName, $this->defaultFormElements);

		$class = '';
		if($key != 1 && $key != 2 && $key != 3) {
			$class = ' class="mjs-nestedSortable-no-nesting"';
		}

		if(!empty($buffer)) {
			$buffer = '<ol>'.$buffer.'</ol>';
		}
		$name = (!empty($element->name)) ? '&nbsp;|&nbsp;'.$element->name : '';
		$block .= '<li id="'.$elementName.'_'.$id.'"'.$class.'><div>'.$this->defaultFormElements[$key].$name.
			'<span class="asmListItemStatus"><a href="load.php?id='.$this->processor->config->pluginId.
			'&section=field&action=edit&form='.(int)$this->input->get->edit.'&edit='.$id.'" class="fieldLink">'.
			Util::i18n_r('edit_link_text').'</a> | <a href="#" rel="'.$elementName.'_'.$id.'"'.
			' class="asmListItemRemove"><i class="fa fa-trash"></i></a></span></div>'.$buffer.'</li>';

		return $block;
	}

	/**
	 * Renders field editor view
	 */
	private function __renderAdminFieldEditor()
	{
		$this->processor->currentForm = $this->processor->getFormById((int)$this->input->get->form);
		if(!$this->processor->currentForm) {
			\MsgReporter::setClause('form_notfound_error', array(), true);
			return;
		}

		$this->processor->currentField = $this->processor->findElement(
			(int)$this->input->get->edit, null, $this->processor->currentForm->elements
		);
		if(!$this->processor->currentField) {
			\MsgReporter::setClause('field_notfound_error', array(), true);
			return;
		}

		$fieldType = $this->processor->getElementName($this->processor->currentField);

		$fieldprops = $this->renderSection('Admin'.$fieldType.'Attributes');

		return $this->parser->render($this->fieldEdotorWrapper, array(
				'field_editor_action' => 'load.php?id='.$this->processor->config->pluginId.
					'&section=field&action=edit&form='.(int)$this->input->get->form.
					'&edit='.(int)$this->input->get->edit,
				'field_type' => $fieldType,
				'attributes_label' => Util::i18n_r('attributes_label'),
				'edit_form_attributes_button' => Util::i18n_r('edit_form_attributes_button'),
				'formprops' => $fieldprops,
				'form_fields_label' => Util::i18n_r('form_fields_label'),
				'select_fields_label' => Util::i18n_r('select_fields_label'),
				'order_fields_info' =>  Util::i18n_r('order_fields_info'),
				'elements_value' => '',
				'save_form_prompt' => Util::i18n_r('save_form_prompt'),
				'form_confirm_exit' => Util::i18n_r('form_confirm_exit'),
				'edit_link_text' => Util::i18n_r('edit_link_text'),
				'prev_link' => 'load.php?id='.$this->processor->config->pluginId.
					'&section=form&action=edit&edit='.(int)$this->input->get->form,
				'plugin_id' => $this->processor->config->pluginId,
				'prev_save_form_link' => Util::i18n_r('prev_save_form_link'),
				'save_field_button' => Util::i18n_r('save_field_button').' '.$fieldType
			)
		);
	}


	private function __renderAdminInputAttributes()
	{
		$props = '';

		$props = $this->parser->render($this->formPropertyRow, array(
				'id' => 'name',
				'label' => Util::i18n_r('field_name_label'),
				'type' => 'text',
				'name' => 'name',
				'value' => $this->processor->currentField->name,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'type',
				'label' => Util::i18n_r('field_type_label'),
				'type' => 'text',
				'name' => 'type',
				'value' => $this->processor->currentField->type,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'value',
				'label' => Util::i18n_r('field_value_label'),
				'type' => 'text',
				'name' => 'value',
				'value' => $this->processor->currentField->value,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'placeholder',
				'label' => Util::i18n_r('field_placeholder_label'),
				'type' => 'text',
				'name' => 'placeholder',
				'value' => $this->processor->currentField->placeholder,
			)
		);

		$props .= $this->parser->render($this->formPropertyCheckboxRow, array(
				'id' => 'required',
				'label' => Util::i18n_r('field_required_label'),
				'type' => 'checkbox',
				'name' => 'required',
				'value' => 1,
				'checked' => !empty($this->processor->currentField->required == 1) ? ' checked="checked"' : ''
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'size',
				'label' => Util::i18n_r('field_size_label'),
				'type' => 'number',
				'name' => 'size',
				'value' => ($this->processor->currentField->size) ?
					(int)$this->processor->currentField->size : '',
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'maxlength',
				'label' => Util::i18n_r('field_maxlength_label'),
				'type' => 'number',
				'name' => 'maxlength',
				'value' => ($this->processor->currentField->maxlength) ?
					(int)$this->processor->currentField->maxlength : '',
			)
		);

		$props .= $this->parser->render($this->formPropertyCheckboxRow, array(
				'id' => 'multiple',
				'label' => Util::i18n_r('form_multiple_label'),
				'type' => 'checkbox',
				'name' => 'multiple',
				'value' => 1,
				'checked' => !empty($this->processor->currentField->multiple == 1) ? ' checked="checked"' : ''
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'class',
				'label' => Util::i18n_r('field_class_label'),
				'type' => 'text',
				'name' => 'class',
				'value' => $this->processor->currentField->class,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'id',
				'label' => Util::i18n_r('field_id_label'),
				'type' => 'text',
				'name' => 'id',
				'value' => $this->processor->currentField->id,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'style',
				'label' => Util::i18n_r('field_style_label'),
				'type' => 'text',
				'name' => 'style',
				'value' => $this->processor->currentField->style,
			)
		);

		return $props;
	}

	private function __renderAdminButtonAttributes()
	{
		$props = '';

		$props = $this->parser->render($this->formPropertyRow, array(
				'id' => 'name',
				'label' => Util::i18n_r('field_name_label'),
				'type' => 'text',
				'name' => 'name',
				'value' => $this->processor->currentField->name,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'content',
				'label' => Util::i18n_r('field_content_label'),
				'type' => 'text',
				'name' => 'content',
				'value' => $this->processor->currentField->content,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'type',
				'label' => Util::i18n_r('field_type_label'),
				'type' => 'text',
				'name' => 'type',
				'value' => $this->processor->currentField->type,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'class',
				'label' => Util::i18n_r('field_class_label'),
				'type' => 'text',
				'name' => 'class',
				'value' => $this->processor->currentField->class,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'id',
				'label' => Util::i18n_r('field_id_label'),
				'type' => 'text',
				'name' => 'id',
				'value' => $this->processor->currentField->id,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'style',
				'label' => Util::i18n_r('field_style_label'),
				'type' => 'text',
				'name' => 'style',
				'value' => $this->processor->currentField->style,
			)
		);

		return $props;
	}

	private function __renderAdminFieldsetAttributes()
	{
		$props = '';

		$props = $this->parser->render($this->formPropertyRow, array(
				'id' => 'class',
				'label' => Util::i18n_r('field_class_label'),
				'type' => 'text',
				'name' => 'class',
				'value' => $this->processor->currentField->class,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'id',
				'label' => Util::i18n_r('field_id_label'),
				'type' => 'text',
				'name' => 'id',
				'value' => $this->processor->currentField->id,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'style',
				'label' => Util::i18n_r('field_style_label'),
				'type' => 'text',
				'name' => 'style',
				'value' => $this->processor->currentField->style,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'legend',
				'label' => Util::i18n_r('field_legend_label'),
				'type' => 'text',
				'name' => 'legend',
				'value' => $this->processor->currentField->legend,
			)
		);

		return $props;
	}

	private function __renderAdminWrapperAttributes()
	{
		$props = '';

		$props = $this->parser->render($this->formPropertyRow, array(
				'id' => 'tag',
				'label' => Util::i18n_r('field_tag_label'),
				'type' => 'text',
				'name' => 'tag',
				'value' => $this->processor->currentField->tag,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'content',
				'label' => Util::i18n_r('field_content_label'),
				'type' => 'text',
				'name' => 'content',
				'value' => $this->processor->currentField->content,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'class',
				'label' => Util::i18n_r('field_class_label'),
				'type' => 'text',
				'name' => 'class',
				'value' => $this->processor->currentField->class,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'id',
				'label' => Util::i18n_r('field_id_label'),
				'type' => 'text',
				'name' => 'id',
				'value' => $this->processor->currentField->id,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'style',
				'label' => Util::i18n_r('field_style_label'),
				'type' => 'text',
				'name' => 'style',
				'value' => $this->processor->currentField->style,
			)
		);

		return $props;
	}

	private function __renderAdminLabelAttributes()
	{
		$props = '';

		$props = $this->parser->render($this->formPropertyRow, array(
				'id' => 'content',
				'label' => Util::i18n_r('field_content_label'),
				'type' => 'text',
				'name' => 'content',
				'value' => $this->processor->currentField->content,
			)
		);

		$props .= $this->parser->render($this->formPropertyCheckboxRow, array(
				'id' => 'textappend',
				'label' => Util::i18n_r('field_append_label'),
				'type' => 'checkbox',
				'name' => 'textappend',
				'value' => 1,
				'checked' => !empty($this->processor->currentField->textappend) ? ' checked="checked"' : ''
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'for',
				'label' => Util::i18n_r('field_for_label'),
				'type' => 'text',
				'name' => 'for',
				'value' => $this->processor->currentField->for,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'class',
				'label' => Util::i18n_r('field_class_label'),
				'type' => 'text',
				'name' => 'class',
				'value' => $this->processor->currentField->class,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'id',
				'label' => Util::i18n_r('field_id_label'),
				'type' => 'text',
				'name' => 'id',
				'value' => $this->processor->currentField->id,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'style',
				'label' => Util::i18n_r('field_style_label'),
				'type' => 'text',
				'name' => 'style',
				'value' => $this->processor->currentField->style,
			)
		);

		return $props;
	}

	private function __renderAdminTextareaAttributes()
	{
		$props = '';

		$props = $this->parser->render($this->formPropertyRow, array(
				'id' => 'name',
				'label' => Util::i18n_r('field_name_label'),
				'type' => 'text',
				'name' => 'name',
				'value' => $this->processor->currentField->name,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'rows',
				'label' => Util::i18n_r('field_rows_label'),
				'type' => 'number',
				'name' => 'rows',
				'value' => $this->processor->currentField->rows,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'cols',
				'label' => Util::i18n_r('field_cols_label'),
				'type' => 'number',
				'name' => 'cols',
				'value' => $this->processor->currentField->cols,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'content',
				'label' => Util::i18n_r('field_content_label'),
				'type' => 'text',
				'name' => 'content',
				'value' => $this->processor->currentField->content,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'placeholder',
				'label' => Util::i18n_r('field_placeholder_label'),
				'type' => 'text',
				'name' => 'placeholder',
				'value' => $this->processor->currentField->placeholder,
			)
		);

		$props .= $this->parser->render($this->formPropertyCheckboxRow, array(
				'id' => 'required',
				'label' => Util::i18n_r('field_required_label'),
				'type' => 'checkbox',
				'name' => 'required',
				'value' => 1,
				'checked' => !empty($this->processor->currentField->required == 1) ? ' checked="checked"' : ''
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'class',
				'label' => Util::i18n_r('field_class_label'),
				'type' => 'text',
				'name' => 'class',
				'value' => $this->processor->currentField->class,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'id',
				'label' => Util::i18n_r('field_id_label'),
				'type' => 'text',
				'name' => 'id',
				'value' => $this->processor->currentField->id,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'style',
				'label' => Util::i18n_r('field_style_label'),
				'type' => 'text',
				'name' => 'style',
				'value' => $this->processor->currentField->style,
			)
		);

		return $props;
	}

	/**
	 * Element attributes block: Recaptcha
	 *
	 * @return string
	 */
	private function __renderAdminRecaptchaAttributes()
	{
		$props = '';

		$props = $this->parser->render($this->formPropertyRow, array(
				'id' => 'site_key',
				'label' => Util::i18n_r('field_site_key_label'),
				'type' => 'text',
				'name' => 'site_key',
				'value' => $this->processor->currentField->site_key,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'secret_key',
				'label' => Util::i18n_r('field_secret_key_label'),
				'type' => 'text',
				'name' => 'secret_key',
				'value' => $this->processor->currentField->secret_key,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'class',
				'label' => Util::i18n_r('field_class_label'),
				'type' => 'text',
				'name' => 'class',
				'value' => $this->processor->currentField->class,
			)
		);

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'datasize',
				'label' => Util::i18n_r('field_datasize_label'),
				'type' => 'text',
				'name' => 'datasize',
				'value' => $this->processor->currentField->datasize,
			)
		);

		return $props;
	}

	/**
	 * Element attributes block: DelayBlock
	 *
	 * @return string
	 */
	private function __renderAdminDelayBlockAttributes()
	{
		$props = '';

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'content',
				'label' => Util::i18n_r('field_delaytext_label'),
				'type' => 'text',
				'name' => 'content',
				'value' => $this->processor->currentField->content,
			)
		);

		return $props;
	}

	/**
	 * Element attributes block: AjaxBlock
	 *
	 * @return string
	 */
	private function __renderAdminAjaxBlockAttributes()
	{
		$props = '';

		$props .= $this->parser->render($this->formPropertyRow, array(
				'id' => 'formid',
				'label' => Util::i18n_r('field_formid_label'),
				'type' => 'text',
				'name' => 'formid',
				'value' => $this->processor->currentField->formid,
			)
		);

		return $props;
	}


	/**
	 * Renders errors and notifications
	 */
	private function __renderAdminMessages($arg)
	{
		if(\MsgReporter::msgs())
		{
			\MsgReporter::removeClauseByValue('<li class="im-notify">{im_forms/item_deleted}<a class="close" href="javascript:void(0)"><i class="fa fa-times"></i></a></li>');
			return $this->parser->render($arg[0], array(
				'messages' => $this->parser->render($this->msgsWrapper, array(
							'msgs_list' => \MsgReporter::buildMsg()
						)
					)
				)
			);
		} else
		{
			return $this->parser->render($arg[0], array(
					'messages' => '')
			);
		}
	}

	/**
	 * Performs validity checks just before form data is saved
	 *
	 * @return bool
	 */
	public function checkFormData()
	{
		$confirm_flag = true;
		$this->processor->currentForm = $this->processor->getFormById((int)$this->input->get->edit);

		// Check user input
		if($this->input->post->name) {
			$this->processor->currentForm->name = $this->sanitizer->pageName($this->input->post->name);
		}
		if(!$this->processor->currentForm->name) {
			$this->processor->currentForm->name = 'form'.time();
		}

		// Check if a form with the same name already exists
		$exists = $this->processor->getSimpleItemByFormName($this->processor->currentForm->name);
		if($exists && $exists->id != $this->input->get->edit) {
			\MsgReporter::setClause('form_check_error', array(), true);
			$confirm_flag = false;
		}

		$this->processor->currentForm->formtype = null;
		if($this->input->post->formtype) {
			$this->processor->currentForm->formtype = $this->sanitizer->fieldName($this->input->post->formtype);
		}

		$this->processor->currentForm->action = null;
		if($this->input->post->formaction) {
			$this->processor->currentForm->action = $this->sanitizer->text($this->input->post->formaction);
		}

		if(!$this->input->post->method || $this->input->post->method == 1) {
			$this->processor->currentForm->method = 'post';
		} else {
			$this->processor->currentForm->method = 'get';
		}

		$this->processor->currentForm->enctype = null;
		if($this->input->post->enctype) {
			$this->processor->currentForm->enctype = $this->sanitizer->text($this->input->post->enctype,
				array('maxLength' => 100)
			);
		}

		$this->processor->currentForm->charset = null;
		if($this->input->post->charset) {
			$this->processor->currentForm->charset = $this->sanitizer->text($this->input->post->charset,
				array('maxLength' => 30)
			);
		}

		$this->processor->currentForm->novalidate = null;
		if($this->input->post->novalidate) {
			$this->processor->currentForm->novalidate = 1;
		}

		$this->processor->currentForm->class = null;
		if($this->input->post->class) {
			$this->processor->currentForm->class = $this->sanitizer->text($this->input->post->class,
				array('maxLength' => 50)
			);
		}

		$this->processor->currentForm->id = null;
		if($this->input->post->id) {
			$this->processor->currentForm->id = $this->sanitizer->text($this->input->post->id,
				array('maxLength' => 50)
			);
		}

		$this->processor->currentForm->style = null;
		if($this->input->post->style) {
			$this->processor->currentForm->style = $this->sanitizer->text($this->input->post->style,
				array('maxLength' => 50)
			);
		}

		$this->processor->currentForm->resources = null;
		if($this->input->post->resources) {
			$this->processor->currentForm->resources = addslashes($this->input->post->resources);
		}

		$values = array();
		$prep = explode('&', $this->input->post->elements);
		foreach($prep as $key => $val) { parse_str($val, $values[]); }


		$elements = array();
		$deletion = array();
		$ids = array(0);
		foreach($values as $value)
		{
			foreach($value as $name => $element)
			{
				foreach($element as $id => $parent_id)
				{
					$element_id = (int)$id;
					$elements[$element_id] = $this->processor->build($name, $parent_id);
					$ids[] = $element_id;
					// Copy all attributes, if this field already exists
					$find = $this->processor->findElement(
						$element_id,
						$this->processor->getElementsClassName($name),
						$this->processor->currentForm->elements
					);
					if($find) {
						$clon = clone $find;
						$clon->elements = array();
						$clon->parent_id = $elements[$element_id]->parent_id;
						$elements[$element_id] = $clon;
					}

					if($parent_id != 'null')
					{
						$elements[$parent_id] = $this->processor->assemble($elements[$parent_id], $elements[$element_id], $element_id);
						$deletion[] = $element_id;
					}
				}
			}
		}
		foreach($deletion as $key) {
			if(!empty($elements[$key])) { unset($elements[$key]); }
		}

		// Set current form elements
		$this->processor->currentForm->elements = $elements;
		// Get the last element ID
		$this->processor->currentForm->lastId = max($ids);

		return $confirm_flag;
	}

	/**
	 * Save the entire form object as an item
	 */
	public function saveForm($formid)
	{
		if($this->processor->saveForm($this->processor->currentForm, (int)$formid)) {
			$form = $this->processor->getSimpleItemByFormName($this->processor->currentForm->name);
			if($form) {
				\MsgReporter::setClause('form_successfully_saved', array(
					'form_name' => '<strong>'.$this->processor->currentForm->name.'</strong>'));
				$this->input->get->edit = (!$this->input->get->edit) ? $form->id : (int)$this->input->get->edit;
				// Just expire all markup caches
				$this->processor->getCache()->expire();
				return true;
			}
		}
		// Todo: Errormessage
		return false;
	}

	/**
	 * Remove ImForm item
	 */
	public function deleteForm($itemid)
	{
		$item = $this->processor->getItem($this->processor->config->imFormsCategoryId, (int)$itemid);
		if($item) {
			$itemName = $item->name;
			if($this->processor->deleteItem((int)$itemid)) {
				\MsgReporter::setClause('form_successfully_deleted', array(
					'form_name' => '<strong>'.$itemName.'</strong>'));
				return true;
			}
		}
		// Todo: Errormessage
		return false;
	}

	/**
	 * Publish/Unpublish form
	 */
	public function publishForm($itemid)
	{
		$item = $this->processor->getItem($this->processor->config->imFormsCategoryId, (int)$itemid);
		if($item) {
			if($item->active == 1) {
				if($this->processor->activateItem($item, 0)) {
					\MsgReporter::setClause('form_successfully_unpublished', array(
						'form_name' => '<strong>' . $item->name . '</strong>'
					));
					return true;
				}
			} else {
				if($this->processor->activateItem($item, 1)) {
					\MsgReporter::setClause('form_successfully_published', array(
						'form_name' => '<strong>' . $item->name . '</strong>'
					));
					return true;
				}
			}
		}
		\MsgReporter::setClause('form_error_publishing', array(), true);
		return false;
	}


	/**
	 * Performs validity checks just before form data is saved
	 *
	 * @return bool
	 */
	public function checkFieldData()
	{
		$confirm_flag = true;

		$this->processor->currentForm = $this->processor->getFormById((int)$this->input->get->form);
		if(!$this->processor->currentForm) {
			\MsgReporter::setClause('form_notfound_error', array(), true);
			return;
		}

		$this->processor->currentField = $this->processor->findElement(
			(int)$this->input->get->edit, null, $this->processor->currentForm->elements
		);
		if(!$this->processor->currentField) {
			\MsgReporter::setClause('field_notfound_error', array(), true);
			return;
		}

		$fieldType = $this->processor->getElementName($this->processor->currentField);
		$properties = get_object_vars($this->processor->currentField);

		// Check user input
		if(array_key_exists('name', $properties)) {
			$this->processor->currentField->name = null;
			if($this->input->post->name) {
				$this->processor->currentField->name =
					$this->sanitizer->nameFilter($this->input->post->name, array('\[', '\]'), '-');
			}
		}

		if(array_key_exists('class', $properties)) {
			$this->processor->currentField->class = null;
			if($this->input->post->class) {
				$this->processor->currentField->class = $this->sanitizer->text($this->input->post->class,
					array('maxLength' => 100)
				);
			}
		}

		if(array_key_exists('id', $properties)) {
			$this->processor->currentField->id = null;
			if($this->input->post->id) {
				$this->processor->currentField->id = $this->sanitizer->text($this->input->post->id,
					array('maxLength' => 100)
				);
			}
		}

		if(array_key_exists('style', $properties)) {
			$this->processor->currentField->style = null;
			if($this->input->post->style) {
				$this->processor->currentField->style = $this->sanitizer->text($this->input->post->style,
					array('maxLength' => 100)
				);
			}
		}

		if(array_key_exists('legend', $properties)) {
			$this->processor->currentField->legend = null;
			if($this->input->post->legend) {
				$this->processor->currentField->legend = $this->sanitizer->text($this->input->post->legend);
			}
		}

		if(array_key_exists('type', $properties)) {
			$this->processor->currentField->type = null;
			if($this->input->post->type) {
				$this->processor->currentField->type = $this->sanitizer->text($this->input->post->type,
					array('maxLength' => 100)
				);
			}
		}

		if(array_key_exists('value', $properties)) {
			$this->processor->currentField->value = null;
			if($this->input->post->value) {
				$this->processor->currentField->value = $this->sanitizer->text($this->input->post->value);
			}
		}

		if(array_key_exists('placeholder', $properties)) {
			$this->processor->currentField->placeholder = null;
			if($this->input->post->placeholder) {
				$this->processor->currentField->placeholder = $this->sanitizer->text($this->input->post->placeholder);
			}
		}

		if(array_key_exists('required', $properties)) {
			$this->processor->currentField->required = null;
			if($this->input->post->required) {
				$this->processor->currentField->required = (int)$this->input->post->required;
			}
		}

		if(array_key_exists('size', $properties)) {
			$this->processor->currentField->size = null;
			if($this->input->post->size) {
				$this->processor->currentField->size = (int)$this->input->post->size;
			}
		}

		if(array_key_exists('maxlength', $properties)) {
			$this->processor->currentField->maxlength = null;
			if($this->input->post->maxlength) {
				$this->processor->currentField->maxlength = (int)$this->input->post->maxlength;
			}
		}

		if(array_key_exists('multiple', $properties)) {
			$this->processor->currentField->multiple = null;
			if($this->input->post->multiple) {
				$this->processor->currentField->multiple = (int)$this->input->post->multiple;
			}
		}

		if(array_key_exists('for', $properties)) {
			$this->processor->currentField->for = null;
			if($this->input->post->for) {
				$this->processor->currentField->for = $this->sanitizer->text($this->input->post->for,
					array('maxLength' => 100)
				);
			}
		}

		if(array_key_exists('tag', $properties)) {
			$this->processor->currentField->tag = null;
			if($this->input->post->tag) {
				$this->processor->currentField->tag = $this->sanitizer->text($this->input->post->tag,
					array('maxLength' => 100)
				);
			}
		}

		if(array_key_exists('rows', $properties)) {
			$this->processor->currentField->rows = null;
			if($this->input->post->rows) {
				$this->processor->currentField->rows = (int)$this->input->post->rows;
			}
		}

		if(array_key_exists('cols', $properties)) {
			$this->processor->currentField->cols = null;
			if($this->input->post->cols) {
				$this->processor->currentField->cols = (int)$this->input->post->cols;
			}
		}

		if(array_key_exists('site_key', $properties)) {
			$this->processor->currentField->site_key = null;
			if($this->input->post->site_key) {
				$this->processor->currentField->site_key = $this->sanitizer->text($this->input->post->site_key);
			}
		}

		if(array_key_exists('secret_key', $properties)) {
			$this->processor->currentField->secret_key = null;
			if($this->input->post->secret_key) {
				$this->processor->currentField->secret_key = $this->sanitizer->text($this->input->post->secret_key);
			}
		}

		if(($fieldType == 'Button' || $fieldType == 'Wrapper' || $fieldType == 'Label') &&
			array_key_exists('content', $properties)) {
			$this->processor->currentField->content = null;
			if($this->input->post->content) {
				$this->processor->currentField->content = $this->input->post->content;
			}
		}

		if($fieldType == 'Label' && array_key_exists('textappend', $properties)) {
			$this->processor->currentField->textappend = null;
			if($this->input->post->textappend) {
				$this->processor->currentField->textappend = 1;
			}
		}

		if($fieldType == 'DelayBlock' && array_key_exists('content', $properties)) {
			$this->processor->currentField->content = null;
			if($this->input->post->content) {
				$this->processor->currentField->content = $this->sanitizer->text($this->input->post->content);
			}
		}

		if($fieldType == 'Textarea' && array_key_exists('content', $properties)) {
			$this->processor->currentField->content = null;
			if($this->input->post->content) {
				$this->processor->currentField->content = $this->sanitizer->textarea($this->input->post->content);
			}
		}

		if(array_key_exists('formid', $properties)) {
			$this->processor->currentField->formid = null;
			if($this->input->post->formid) {
				$this->processor->currentField->formid = $this->sanitizer->text($this->input->post->formid);
			}
		}

		if(array_key_exists('datasize', $properties)) {
			$this->processor->currentField->datasize = null;
			if($this->input->post->datasize) {
				$this->processor->currentField->datasize = $this->sanitizer->text($this->input->post->datasize);
			}
		}

		return $confirm_flag;
	}

}