<?php if(!defined('IN_GS')){ die('you cannot load this page directly.'); }

class Installer
{
	private static $imFormsCategory;
	private static $imCacheCategory;

	/**
	 * Check correctly installation step by step
	 */
	public static function checkInstallation()
	{
		global $im_forms_file;

		$jsonOutput = '';

		if(!isset($_GET['imf_aj'])) {
			return self::getInstallerTplBlock();
		}
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'start_install') {
			$jsonOutput = json_encode(array('status' => 1, 'msg' => '<strong>Installation in progress ...</strong>',
				'info' => 'Checking if ItemManager is already installed ...', 'type' => 'notify first'));
		}
		// Check ItemManager installed
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_installed') {
			if(!self::isItemManagerInstalled()) {
				$jsonOutput = json_encode(array('status' => 0, 'msg' =>
					'You need the <a href="http://get-simple.info/extend/plugin/itemmanager/936/">ItemManager</a> '.
					'plugin installed in order to run the ImForms.',
					'info' => '', 'type' => 'error'));
			} else {
				$jsonOutput = json_encode(array('status' => 2, 'msg' =>
					'ItemManager is already installed', 'info' => 'Checking ItemManager Allocator variable ...',
					'type' => 'notify'));
			}
		}
		// Check Allocator variable
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_allocator') {
			if(!self::isAllocatorEnabled()) {
				$jsonOutput = json_encode(array('status' => 0, 'msg' =>
					'The variable useAllocater is disabled, change the $config->useAllocater valiable in '.
					'<strong>/plugins/imanager/inc/config.php</strong> file to <strong>true</strong>',
					'info' => '', 'type' => 'error'));
			} else {
				$jsonOutput = json_encode(array('status' => 3, 'msg' =>
					'The variable <strong>useAllocator</strong> is set to <strong>true</strong>',
					'info' => 'Checking ItemManager version ...', 'type' => 'notify'));
			}
		}
		// Check IM version
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_version') {
			if(!self::checkItemManagerVersion()) {
				$jsonOutput = json_encode(array('status' => 0, 'msg' =>
					'ItemManager version is older than <strong>2.4.1</strong>, a newer version of the ItemManager is '.
					'required to get ImForms running',
					'info' => '', 'type' => 'error'));
			} else {
				$jsonOutput = json_encode(array('status' => 4, 'msg' =>
					'Current ItemManager version is at least <strong>2.4.1</strong>',
					'info' => 'Checking if the imforms category already exists ...', 'type' => 'notify'));
			}
		}
		// Check Category exists
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_category') {
			if(!self::imFormsCategoryExists()) {
				$jsonOutput = json_encode(array('status' => 5, 'msg' =>
					'The <strong>imforms</strong> category does not exist yet', 'info' =>
					'Trying to create imforms category ...', 'type' => 'notify'));
			} else {
				$jsonOutput = json_encode(array('status' => 6, 'msg' =>
					'The <strong>imforms</strong> category already exists', 'info' =>
					'Creating ImForms configuration file ...', 'type' => 'notify'));
			}
		}
		// Create category
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_category_create') {
			if(!self::createImFormsCategory()) {
				$jsonOutput = json_encode(array('status' => 0, 'msg' =>
					'The <strong>imforms</strong> category could not be created.', 'info' => '', 'type' => 'error'));
			} else {
				$jsonOutput = json_encode(array('status' => 6, 'msg' =>
					'The <strong>imforms</strong> category was created successfully.',
					'info' => 'Creating ImForms configuration file ...', 'type' => 'notify'));
			}
		}

		// Check Cache category exists
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_cache_category') {
			if(!self::imCacheCategoryExists()) {
				$jsonOutput = json_encode(array('status' => 7, 'msg' =>
					'The <strong>imforms_cache</strong> category does not exist yet',
					'info' => 'Trying to create imforms_cache category ...', 'type' => 'notify'));
			} else {
				$jsonOutput = json_encode(array('status' => 8, 'msg' =>
					'The <strong>imforms_cache</strong> category already exists',
					'info' => 'Creating ImForms configuration file ...', 'type' => 'notify'));
			}
		}
		// Create cache category
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_cache_category_create') {
			if(!self::createCacheCategory()) {
				$jsonOutput = json_encode(array('status' => 0, 'msg' =>
					'The <strong>imforms_cache</strong> category could not be created.', 'info' => '',
					'type' => 'error'));
			} else {
				$jsonOutput = json_encode(array('status' => 8, 'msg' =>
					'The <strong>imforms_cache</strong> category was created successfully.',
					'info' => 'Checking PHP version ...', 'type' => 'notify'));
			}
		}

		// Check PHP version
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_check_php') {
			if(!self::checkPhpVersion()) {
				$jsonOutput = json_encode(array('status' => 0, 'msg' =>
					'You are running PHP <strong>'.PHP_VERSION.'</strong>, ImForms requires at least PHP version 5.6.0',
					'info' => '', 'type' => 'error'));
			} else {
				$jsonOutput = json_encode(array('status' => 9, 'msg' =>
					'You are running PHP <strong>'.PHP_VERSION.'</strong>',
					'info' => 'Creating a demo contact form...', 'type' => 'notify'));
			}
		}
		// Create a contact form demo
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_create_demo') {
			$res = self::createDemoForm();
			if($res === -1) {
				$jsonOutput = json_encode(array('status' => 0, 'msg' =>
					'A demo <strong>contact</strong> form could not be created', 'info' => '', 'type' => 'error'));
			} else if($res === 1) {
				$jsonOutput = json_encode(array('status' => 10, 'msg' =>
					'A demo <strong>contact</strong> form was created successfully.',
					'info' => 'Creating ImForms configuration file ...', 'type' => 'notify'));
			} else if($res === 2) {
				$jsonOutput = json_encode(array('status' => 10, 'msg' =>
					'A demo <strong>contact</strong> form already exists.',
					'info' => 'Creating ImForms configuration file ...', 'type' => 'notify'));
			}
		}
		// Create configuration file
		elseif($_GET['imf_aj'] == 'setup' && isset($_POST['req']) && $_POST['req'] == 'im_config_create') {
			if(!self::createConfig()) {
				$jsonOutput = json_encode(array('status' => 0, 'msg' =>
					'The <strong>config.php</strong> file could not be created', 'info' => '', 'type' => 'error'));
			} else {
				$jsonOutput = json_encode(array('status' => 11, 'msg' =>
					'The <strong>'.dirname(__DIR__).'/inc/config.php</strong> file was created successfully.',
					'info' => '', 'type' => 'notify'));
			}
		}

		sleep(2);
		header('Content-type: application/json; charset=utf-8');
		return $jsonOutput;
	}


	protected static function getInstallerTplBlock()
	{
		global $im_forms_file;
		ob_start(); ?>
		<div id="delay">
			<div id="clamp">
				<a id="stop-delay" href="#">&nbsp;</a>
				<span id="loader"></span>
				<p id="delay-info" class="blink">Starting ImForms installation ...</p>
			</div>
		</div>
		<h2><?php echo \ImForms\Util::i18n_r('plugin_name') ?></h2>
		<div class="msgs-wrapper">
			<ul id="msgs">

			</ul>
		</div>
		<script>
			$(function () {
				$("#stop-delay").click(function (e) {
					e.preventDefault();
					$("#delay").fadeOut();
				});
				$(document).on({
					ajaxStart: function () {
						$("#delay").show();
					},
					ajaxStop: function () {
						$("#delay").fadeOut();
					}
				});
				var status = 0;
				var info = '';
				var msg = '';
				var type = 'error';
				var count = 0;

				var handleData = function(data) {
					console.log(data);
					count++;
					if(count >= 20) return;
					if(data) {
						info = (data.info) ? data.info : '';
						msg = (data.msg) ? data.msg : '';
						type = (data.type) ? data.type : 'error';
						status = (data.status) ? data.status : 0;
					}
					if(msg.length > 1) {
						$(".main #msgs").append('<li class="'+type+'">'+msg+'</li>');
					}
					if(info.length > 1) {
						$(".main #delay-info").text(info);
					}

					switch(parseInt(status)) {
						case 1:
							sendRequest('im_installed');
							break;
						case 2:
							sendRequest('im_allocator');
							break;
						case 3:
							sendRequest('im_version');
							break;
						case 4:
							sendRequest('im_category');
							break;
						case 5:
							sendRequest('im_category_create');
							break;
						case 6:
							sendRequest('im_cache_category');
							break;
						case 7:
							sendRequest('im_cache_category_create');
							break;
						case 8:
							sendRequest('im_check_php');
							break;
						case 9:
							sendRequest('im_create_demo');
							break;
						case 10:
							sendRequest('im_config_create');
							break;
						case 11:
							$("#msgs .first").text('Installation process is finished');
							$(".main #msgs").append('<li class="notify"><strong>The ImForms plugin installation is successfully completed.</strong></li>');
							$('<form method="post" action="load.php?id=<?php echo $im_forms_file;
								?>"><div class="interact-compresser"><input class="submit" type="submit" name="submit" value="Go to the ImForms"></div></form>').insertAfter(".main #msgs");
							break;
						case 0:
							$("#msgs .first").text('Installation process is finished');
							break;
					}
				}
				function sendRequest(par) {
					$.ajax({
						dataType: "json",
						type: 'POST',
						url: "?id=im_forms&imf_aj=setup",
						data: {'req': par},
						cache: false,
						success: handleData,
						error: function (data) {
							console.log("Error when sending data!");
							console.log(data);
							status = 0;
						},
					});
				}

				// Start Installation
				sendRequest('start_install');
			});
		</script>
		<?php return ob_get_clean();
	}

	private static function isItemManagerInstalled()
	{
		if(!function_exists('imanager')) {return false;}
		$imanager = imanager();
		if($imanager::$installed !== true) {return false;}
		return true;
	}


	private static function isAllocatorEnabled()
	{
		if(!function_exists('imanager')) {return false;}
		if(imanager()->config->useAllocater !== true) {return false;}
		return true;
	}

	private static function checkItemManagerVersion()
	{
		if(!function_exists('imanager')) {return false;}
		imanager();
		if(!defined('IM_VERSION')) return;
		elseif(IM_VERSION < 241) {return false;}
		return true;
	}


	private static function imFormsCategoryExists()
	{
		$imanager = imanager();
		if((self::$imFormsCategory = $imanager->getCategoryMapper()->getCategory('name=imforms')) !== false) { return true; }
		return false;
	}

	private static function imCacheCategoryExists()
	{
		$imanager = imanager();
		if((self::$imCacheCategory = $imanager->getCategoryMapper()->getCategory('name=imforms_cache')) !== false) { return true; }
		return false;
	}

	private static function checkPhpVersion()
	{
		if(version_compare(PHP_VERSION, '5.6.0') >= 0) return true;
		return false;
	}


	private static function createImFormsCategory()
	{
		global $im_forms_file;
		self::imFormsCategoryExists();
		$imanager = imanager();
		self::$imFormsCategory = $imanager->getCategoryMapper()->getCategory('name=imforms');
		// Alredy exist
		if(self::$imFormsCategory) { return false; }
		// Use ItemManager's language file
	 	//MsgReporter::$dir = 'imanager';
		if($imanager->createCategoryByName('imforms', true) !== true) return false;
		self::$imFormsCategory = $imanager->getCategoryMapper()->getCategory('name=imforms');
		if(!self::$imFormsCategory) { return false; }

		$fields = array(
			'cat' => self::$imFormsCategory->id,
			'cf_0_key'   => 'data',
			'cf_0_label' => 'Form Data',
			'cf_0_type'  => 'text',
			'cf_0_options' => '',
			'cf_0_value' => ''
		);

		// Create fields
		if($imanager->createFields($fields) !== true) {
			$imanager->deleteCategory(self::$imFormsCategory->id);
			return false;
		}
		// Default product category field data
		$fieldsdata = array(
			array(
				'field' => 1,
				'default' => '',
				'info' => 'Please never change or delete this data!',
				'required' => 0,
				'min_field_input' => 0,
				'max_field_input' => 0,
				'areaclass' => '',
				'labelclass' => '',
				'fieldclass' => 'readonly'
			)
		);
		// Set field data
		if(self::setFiedData(self::$imFormsCategory->id, $fieldsdata) !== true) {
			$imanager->deleteCategory(self::$imFormsCategory->id);
			return false;
		}
		return true;
	}


	private static function createCacheCategory()
	{
		global $im_forms_file;
		$imanager = imanager();

		self::$imCacheCategory = $imanager->getCategoryMapper()->getCategory('name=imforms_cache');
		// Alredy exist
		if(self::$imCacheCategory) { return false; }
		// Use ItemManager's language file
		//MsgReporter::$dir = 'imanager';
		if($imanager->createCategoryByName('imforms_cache', true) !== true) return false;

		self::$imCacheCategory = $imanager->getCategoryMapper()->getCategory('name=imforms_cache');
		if(!self::$imCacheCategory) return false;

		$fields = array(
			'cat' => self::$imCacheCategory->id,

			'cf_0_key'   => 'data',
			'cf_0_label' => 'Cached Form Markup',
			'cf_0_type'  => 'text',
			'cf_0_options' => '',
			'cf_0_value' => '',
		);
		// Create fields
		if($imanager->createFields($fields) !== true) {
			$imanager->deleteCategory(self::$imCacheCategory->id);
			return false;
		}

		$fieldsdata = array(
			array(
				'field' => 1,
				'default' => '',
				'info' => 'Please never change or delete this data!',
				'required' => 0,
				'min_field_input' => 0,
				'max_field_input' => 0,
				'areaclass' => '',
				'labelclass' => '',
				'fieldclass' => 'readonly'
			)
		);

		// Set field data
		if(self::setFiedData(self::$imCacheCategory->id, $fieldsdata) !== true) {
			$imanager->deleteCategory(self::$imCacheCategory->id);
			return false;
		}
		// Switch back to the SimpleCatalog language file
		//MsgReporter::$dir = $thisfileid;
		return true;
	}

	private static function setFiedData($catid, $fieldsdata)
	{
		$cf = new FieldMapper();
		$cf->init($catid);

		foreach($fieldsdata as $input)
		{
			// Field already exists
			$currfield = $cf->getField((int)$input['field']);

			if(!$currfield)
			{
				MsgReporter::setClause('err_field_id', array(), true);
				return false;
			}

			$currfield->default = !empty($input['default']) ? str_replace('"', "'", $input['default']) : '';
			$currfield->info = !empty($input['info']) ? str_replace('"', "'", $input['info']) : '';
			$currfield->required = (isset($input['required']) && $input['required'] > 0) ? 1 : null;
			$currfield->minimum = (isset($input['min_field_input']) && intval($input['min_field_input']) > 0)
				? intval($input['min_field_input']) : null;
			$currfield->maximum = (isset($input['max_field_input']) && intval($input['max_field_input']) > 0)
				? intval($input['max_field_input']) : null;
			$currfield->areaclass = !empty($input['areaclass']) ? str_replace('"', "'", $input['areaclass']) : '';
			$currfield->labelclass = !empty($input['labelclass']) ? str_replace('"', "'", $input['labelclass']) : '';
			$currfield->fieldclass = !empty($input['fieldclass']) ? str_replace('"', "'", $input['fieldclass']) : '';

			// process custom Fieldtype settings
			foreach($input as $key => $value)
			{
				if(strpos($key, 'custom-') !== false)
				{
					$fieldkey = str_replace('custom-', '', $key);
					$currfield->configs->$fieldkey = $value;
				}
			}
			if(!$currfield->save()) return false;
		}
		return true;
	}


	private static function createDemoForm()
	{
		global $im_forms_file;

		$imanager = imanager();
		$mapper = $imanager->getItemMapper();

		self::$imFormsCategory = $imanager->getCategoryMapper()->getCategory('name=imforms');
		if(!self::$imFormsCategory) { return -1; }

		$mapper->init(self::$imFormsCategory->id);
		$item = $mapper->getItem('name=contact');

		if($item === false) {
			$item = new Item(self::$imFormsCategory->id);
			$item->name = 'contact';
			$item->active = 1;
			$data = 'TzoxOToiSW1Gb3Jtc1xJbUZvcm1zRm9ybSI6MTU6e3M6NjoiaXRlbUlkIjtOO3M6NToiY2xhc3MiO3M6MTU6ImltZm9ybXMtY29udGFjdCI7czoyOiJpZCI7czoxMjoiY29udGFjdC1kZW1vIjtzOjU6InN0eWxlIjtOO3M6OToicmVzb3VyY2VzIjtzOjI1ODoiPGxpbmsgaHJlZj1cIltbc2l0ZXVybF1dcGx1Z2lucy9pbV9mb3Jtcy9jc3MvZnJvbnRlbmQvc3R5bGVzLmNzc1wiIHJlbD1cInN0eWxlc2hlZXRcIj4NCjxzY3JpcHQgc3JjPVwiaHR0cHM6Ly9hamF4Lmdvb2dsZWFwaXMuY29tL2FqYXgvbGlicy9qcXVlcnkvMS4xMi40L2pxdWVyeS5taW4uanNcIj48L3NjcmlwdD4NCjxzY3JpcHQgc3JjPVwiaHR0cHM6Ly93d3cuZ29vZ2xlLmNvbS9yZWNhcHRjaGEvYXBpLmpzXCIgYXN5bmMgZGVmZXI+PC9zY3JpcHQ+IjtzOjQ6Im5hbWUiO3M6NzoiY29udGFjdCI7czo3OiJlbmN0eXBlIjtzOjE5OiJtdWx0aXBhcnQvZm9ybS1kYXRhIjtzOjc6ImNoYXJzZXQiO047czoxMDoibm92YWxpZGF0ZSI7TjtzOjY6ImFjdGlvbiI7TjtzOjc6ImNvbnRlbnQiO047czo2OiJtZXRob2QiO3M6NDoicG9zdCI7czo4OiJmb3JtdHlwZSI7czoxNjoiRW1haWxUcmFuc21pdHRlciI7czo4OiJlbGVtZW50cyI7YTozOntpOjE4O086MjU6IkltRm9ybXNcSW1Gb3Jtc0RlbGF5QmxvY2siOjQ6e3M6NzoiY29udGVudCI7czoxNDoiUGxlYXNlIHdhaXQuLi4iO3M6ODoicGFyZW50aWQiO047czo5OiJwYXJlbnRfaWQiO047czo4OiJlbGVtZW50cyI7YTowOnt9fWk6MjtPOjIzOiJJbUZvcm1zXEltRm9ybXNGaWVsZHNldCI6ODp7czo1OiJjbGFzcyI7czo5OiJ0ZXN0Q2xhc3MiO3M6MjoiaWQiO3M6NjoidGVzdElEIjtzOjg6InBhcmVudGlkIjtOO3M6NToic3R5bGUiO047czo2OiJsZWdlbmQiO3M6MzU6IlBsZWFzZSBmaWxsIGluIHRoZSBmb2xsb3dpbmcgZmllbGRzIjtzOjc6ImNvbnRlbnQiO047czo4OiJlbGVtZW50cyI7YTozOntpOjExO086MjI6IkltRm9ybXNcSW1Gb3Jtc1dyYXBwZXIiOjg6e3M6MzoidGFnIjtzOjM6ImRpdiI7czo1OiJjbGFzcyI7czoxMDoiZm9ybS1ncm91cCI7czoyOiJpZCI7TjtzOjg6InBhcmVudGlkIjtOO3M6NToic3R5bGUiO047czo3OiJjb250ZW50IjtOO3M6ODoiZWxlbWVudHMiO2E6ODp7aTo2O086MjA6IkltRm9ybXNcSW1Gb3Jtc0xhYmVsIjo4OntzOjU6ImNsYXNzIjtzOjg6InJlcXVpcmVkIjtzOjI6ImlkIjtOO3M6ODoicGFyZW50aWQiO047czozOiJmb3IiO3M6NDoibmFtZSI7czo1OiJzdHlsZSI7TjtzOjc6ImNvbnRlbnQiO3M6NDoiTmFtZSI7czo4OiJlbGVtZW50cyI7YTowOnt9czo5OiJwYXJlbnRfaWQiO2k6MTE7fWk6MTtPOjIwOiJJbUZvcm1zXEltRm9ybXNJbnB1dCI6MTQ6e3M6NDoidHlwZSI7czo0OiJ0ZXh0IjtzOjU6ImNsYXNzIjtzOjEyOiJmb3JtLWNvbnRyb2wiO3M6MjoiaWQiO3M6NDoibmFtZSI7czo4OiJwYXJlbnRpZCI7TjtzOjU6InN0eWxlIjtOO3M6NDoibmFtZSI7czo0OiJuYW1lIjtzOjU6InZhbHVlIjtOO3M6MTE6InBsYWNlaG9sZGVyIjtOO3M6ODoicmVxdWlyZWQiO2k6MTtzOjQ6InNpemUiO047czo5OiJtYXhsZW5ndGgiO047czo4OiJtdWx0aXBsZSI7TjtzOjk6InBhcmVudF9pZCI7aToxMTtzOjg6ImVsZW1lbnRzIjthOjA6e319aToxNDtPOjIwOiJJbUZvcm1zXEltRm9ybXNMYWJlbCI6ODp7czo1OiJjbGFzcyI7czo4OiJyZXF1aXJlZCI7czoyOiJpZCI7TjtzOjg6InBhcmVudGlkIjtOO3M6MzoiZm9yIjtzOjU6ImVtYWlsIjtzOjU6InN0eWxlIjtOO3M6NzoiY29udGVudCI7czoxMzoiRW1haWwgYWRkcmVzcyI7czo4OiJlbGVtZW50cyI7YTowOnt9czo5OiJwYXJlbnRfaWQiO2k6MTE7fWk6MTM7TzoyMDoiSW1Gb3Jtc1xJbUZvcm1zSW5wdXQiOjE0OntzOjQ6InR5cGUiO3M6NToiZW1haWwiO3M6NToiY2xhc3MiO3M6MTI6ImZvcm0tY29udHJvbCI7czoyOiJpZCI7czo1OiJlbWFpbCI7czo4OiJwYXJlbnRpZCI7TjtzOjU6InN0eWxlIjtOO3M6NDoibmFtZSI7czo1OiJlbWFpbCI7czo1OiJ2YWx1ZSI7TjtzOjExOiJwbGFjZWhvbGRlciI7TjtzOjg6InJlcXVpcmVkIjtpOjE7czo0OiJzaXplIjtOO3M6OToibWF4bGVuZ3RoIjtOO3M6ODoibXVsdGlwbGUiO047czo5OiJwYXJlbnRfaWQiO2k6MTE7czo4OiJlbGVtZW50cyI7YTowOnt9fWk6ODtPOjIwOiJJbUZvcm1zXEltRm9ybXNMYWJlbCI6ODp7czo1OiJjbGFzcyI7czo4OiJyZXF1aXJlZCI7czoyOiJpZCI7TjtzOjg6InBhcmVudGlkIjtOO3M6MzoiZm9yIjtzOjc6Im1lc3NhZ2UiO3M6NToic3R5bGUiO047czo3OiJjb250ZW50IjtzOjEyOiJZb3VyIG1lc3NhZ2UiO3M6ODoiZWxlbWVudHMiO2E6MDp7fXM6OToicGFyZW50X2lkIjtpOjExO31pOjk7TzoyMzoiSW1Gb3Jtc1xJbUZvcm1zVGV4dGFyZWEiOjEyOntzOjU6ImNsYXNzIjtzOjEyOiJmb3JtLWNvbnRyb2wiO3M6MjoiaWQiO3M6NzoibWVzc2FnZSI7czo4OiJwYXJlbnRpZCI7TjtzOjU6InN0eWxlIjtOO3M6NDoicm93cyI7aToxMDtzOjQ6ImNvbHMiO2k6NjA7czo0OiJuYW1lIjtzOjc6Im1lc3NhZ2UiO3M6MTE6InBsYWNlaG9sZGVyIjtOO3M6ODoicmVxdWlyZWQiO2k6MTtzOjc6ImNvbnRlbnQiO047czo5OiJwYXJlbnRfaWQiO2k6MTE7czo4OiJlbGVtZW50cyI7YTowOnt9fWk6MTc7TzoyMDoiSW1Gb3Jtc1xJbUZvcm1zTGFiZWwiOjg6e3M6NToiY2xhc3MiO047czoyOiJpZCI7czoxMDoiYXR0YWNobWVudCI7czo4OiJwYXJlbnRpZCI7TjtzOjM6ImZvciI7czoxMToiYXR0YWNobWVudHMiO3M6NToic3R5bGUiO047czo3OiJjb250ZW50IjtzOjExOiJBdHRhY2htZW50cyI7czo4OiJlbGVtZW50cyI7YTowOnt9czo5OiJwYXJlbnRfaWQiO2k6MTE7fWk6MTY7TzoyMDoiSW1Gb3Jtc1xJbUZvcm1zSW5wdXQiOjE0OntzOjQ6InR5cGUiO3M6NDoiZmlsZSI7czo1OiJjbGFzcyI7TjtzOjI6ImlkIjtzOjExOiJhdHRhY2htZW50cyI7czo4OiJwYXJlbnRpZCI7TjtzOjU6InN0eWxlIjtOO3M6NDoibmFtZSI7czoxMjoiYXR0YWNobWVudFtdIjtzOjU6InZhbHVlIjtOO3M6MTE6InBsYWNlaG9sZGVyIjtOO3M6ODoicmVxdWlyZWQiO047czo0OiJzaXplIjtOO3M6OToibWF4bGVuZ3RoIjtOO3M6ODoibXVsdGlwbGUiO2k6MTtzOjk6InBhcmVudF9pZCI7aToxMTtzOjg6ImVsZW1lbnRzIjthOjA6e319fXM6OToicGFyZW50X2lkIjtpOjI7fWk6MTU7TzoyMjoiSW1Gb3Jtc1xJbUZvcm1zV3JhcHBlciI6ODp7czozOiJ0YWciO3M6MzoiZGl2IjtzOjU6ImNsYXNzIjtzOjEwOiJmb3JtLWdyb3VwIjtzOjI6ImlkIjtOO3M6ODoicGFyZW50aWQiO047czo1OiJzdHlsZSI7TjtzOjc6ImNvbnRlbnQiO047czo4OiJlbGVtZW50cyI7YToyOntpOjEwO086MjQ6IkltRm9ybXNcSW1Gb3Jtc1JlQ2FwdGNoYSI6Nzp7czo4OiJzaXRlX2tleSI7czo0MDoiNkxlSXhBY1RBQUFBQUpjWlZScXlIaDcxVU1JRUdOUV9NWGppWktoSSI7czoxMDoic2VjcmV0X2tleSI7czo0MDoiNkxlSXhBY1RBQUFBQUdHLXZGSTFUblJXeE1aTkZ1b2pKNFdpZkpXZSI7czo1OiJjbGFzcyI7czoxMToiZy1yZWNhcHRjaGEiO3M6ODoicGFyZW50aWQiO047czo4OiJyZXNvdXJjZSI7TjtzOjk6InBhcmVudF9pZCI7aToxNTtzOjg6ImVsZW1lbnRzIjthOjA6e319aToxMjtPOjIwOiJJbUZvcm1zXEltRm9ybXNJbnB1dCI6MTQ6e3M6NDoidHlwZSI7czo0OiJ0ZXh0IjtzOjU6ImNsYXNzIjtzOjk6ImhvbmV5LXBvdCI7czoyOiJpZCI7TjtzOjg6InBhcmVudGlkIjtOO3M6NToic3R5bGUiO047czo0OiJuYW1lIjtzOjU6ImhvbmV5IjtzOjU6InZhbHVlIjtOO3M6MTE6InBsYWNlaG9sZGVyIjtOO3M6ODoicmVxdWlyZWQiO047czo0OiJzaXplIjtOO3M6OToibWF4bGVuZ3RoIjtOO3M6ODoibXVsdGlwbGUiO047czo5OiJwYXJlbnRfaWQiO2k6MTU7czo4OiJlbGVtZW50cyI7YTowOnt9fX1zOjk6InBhcmVudF9pZCI7aToyO31pOjU7TzoyMjoiSW1Gb3Jtc1xJbUZvcm1zV3JhcHBlciI6ODp7czozOiJ0YWciO3M6MzoiZGl2IjtzOjU6ImNsYXNzIjtzOjEwOiJmb3JtLWdyb3VwIjtzOjI6ImlkIjtOO3M6ODoicGFyZW50aWQiO047czo1OiJzdHlsZSI7TjtzOjc6ImNvbnRlbnQiO047czo4OiJlbGVtZW50cyI7YToxOntpOjQ7TzoyMToiSW1Gb3Jtc1xJbUZvcm1zQnV0dG9uIjo5OntzOjQ6InR5cGUiO3M6Njoic3VibWl0IjtzOjU6ImNsYXNzIjtzOjE1OiJidG4gYnRuLXByaW1hcnkiO3M6MjoiaWQiO047czo4OiJwYXJlbnRpZCI7TjtzOjU6InN0eWxlIjtOO3M6NDoibmFtZSI7czo2OiJzdWJtaXQiO3M6NzoiY29udGVudCI7czoxMToiU3VibWl0IGZvcm0iO3M6OToicGFyZW50X2lkIjtpOjU7czo4OiJlbGVtZW50cyI7YTowOnt9fX1zOjk6InBhcmVudF9pZCI7aToyO319czo5OiJwYXJlbnRfaWQiO047fWk6MTk7TzoyNDoiSW1Gb3Jtc1xJbUZvcm1zQWpheEJsb2NrIjo1OntzOjExOiJzdG9wZGVsYXlpZCI7czoxMDoic3RvcC1kZWxheSI7czo2OiJmb3JtaWQiO3M6MTI6ImNvbnRhY3QtZGVtbyI7czo4OiJwYXJlbnRpZCI7TjtzOjk6InBhcmVudF9pZCI7TjtzOjg6ImVsZW1lbnRzIjthOjA6e319fXM6NjoibGFzdElkIjtpOjE5O30=';
			if($item->setFieldValue('data', $data, false)) {
				if($item->save()) {
					if($mapper->alloc(self::$imFormsCategory->id) !== true)
					{
						$mapper->init(self::$imFormsCategory->id);
						if(!empty($mapper->items))
						{
							$mapper->simplifyBunch($mapper->items);
							$mapper->save();
						}
					}
					$mapper->simplify($item);
					$mapper->save();

					return 1;
				}
			}
			return -1;
		}

		return 2;

	}


	private static function createConfig()
	{
		$imanager = imanager();

		self::$imFormsCategory = $imanager->getCategoryMapper()->getCategory('name=imforms');
		self::$imCacheCategory = $imanager->getCategoryMapper()->getCategory('name=imforms_cache');
		if(!self::$imFormsCategory || !self::$imCacheCategory) { return false; }
		$dci  = self::$imFormsCategory->id;
		$idi  = self::$imCacheCategory->id;

		$data =
<<<EOD
<?php if(!defined('IN_GS')){ die('you cannot load this page directly.'); }
/**
 * ImForms automatically populates several entries into \$config and
 * includes your plugin specific settings in this file.
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * NOTE: DO NOT DELETE OR CHANGE VARIABLES IN THIS FILE!
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 * If you would like to change the ImForms settings you should create your own config
 * file 'custom.config.php' in the same directory. To do so, simply copy the config.php
 * complete file and rename it to 'custom.config.php'. ImForms will load its at
 * runtime and read it's content, the entries in config.php will be overwritten by your
 * custom.config.php settings.
 */

\$config->imFormsCategoryId = {$dci};

\$config->imFormsCacheCategoryId = {$idi};

\$config->autoFormOutput = true;

/**
 * Here you can enter your form processors:
 * 'class suffix' => 'displayed' name in dropdown box
 */
\$config->formProcessors = array(
	'EmailTransmitter' => 'EmailTransmitter'
);

// Change to true the SectionCache to speed up the rendering for large forms
\$config->imFormsCache = false;

\$config->cacheTime = 3600;

\$config->timeFormat = 'Y-m-d H:i:s';

/* Mailer Settings */

// Email character encoding
\$config->emailCharSet = 'UTF-8';

// The maximum number of file attachments
\$config->maxFileUploads = 5;

/**
 * Set the language for error messages in the log file (Mailer only)
 * optional default: en – ISO 639-1 2-character language code (e.g. French is "fr", "de" for German)
 */
\$config->mailerLanguage = 'en';

// Debug Mode: 0, 1, 2 or 3. Default: 0 (Off).
\$config->mailerDebug = 0;

// Your email address. This is the email address to which the Mailer will send all the messages.
\$config->emailFrom = 'chuck.norris@gmail.com';

// Choose your sender name as you would like it to appear in messages that you send. Example: Chuck Norris.
\$config->emailFromName = 'Chuck Norris';

/**
 * Sending emails via SMTP?
 * Simple Mail Transfer Protocol (SMTP) is a protocol that allows the sending of messages over a
 * TCP/IP-based network from one server to another.
 */
\$config->useSmtp = false;

	// The host name of the outgoing SMTP (Simple Mail Transfer Protocol) server, such as smtp.example.com.
	\$config->smtpHostname = '';

	// Your user name for this account, such as appleseed. Some email providers want your full email address as your user name.
	\$config->smtpUser = '';

	// The email password you use to sign in to your account.
	\$config->smptPassword = '';

	// Does the outgoing mail server support SSL or TLS encryption?
	\$config->smtpEncryption = 'START_TLS'; //SSL, START_TLS

	// The port number used by the outgoing mail server. Common port numbers for outgoing mail are 25, 465, and 587
	\$config->smtpPort = 25;

EOD;
		$fp = fopen(dirname(__DIR__).'/inc/config.php','w');
		$fwrite = fwrite($fp, $data);
		if($fwrite === false) {
			fclose($fp);
			return false;
		}
		return true;
	}
}
if(!file_exists(dirname(__DIR__).'/inc/config.php')) {
	ob_start();
		//login_cookie_check();
		if(!get_cookie('GS_ADMIN_USERNAME')) {die();}
	ob_end_clean();
	echo Installer::checkInstallation();
}

exit();
