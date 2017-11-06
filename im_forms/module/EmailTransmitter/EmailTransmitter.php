<?php namespace ImForms;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require(dirname(__FILE__) . '/PHPMailer/vendor/autoload.php');

/**
 * Class EmailTransmitter
 *
 * This is a EmailTransmitter module.
 * This class is automatically initialized when you assign it to a form and the form is send.
 *
 * @package ImForms
 */
class EmailTransmitter extends Module
{
	/**
	 * @var PHPMailer instance
	 */
	protected $mailer;

	/**
	 * @var array - An array of required element id's that are empty
	 */
	protected $emptyRequired = array();

	/**
	 * @var bool - Validation error flag
	 */
	protected $error = false;

	/**
	 * A method to determine the status of the user input check
	 *
	 * @return bool
	 */
	public function isError() { return $this->error; }

	/**
	 * __execute() method is the entry point of that module.
	 * This method will be called automatically if you assign
	 * a module to your form
	 *
	 */
	public function __execute()
	{
		$this->mailer = new Mailer($this->processor->config);
		$this->sanitizer = $this->processor->getSanitizer();
		$this->parser = $this->processor->getTemplateParser();
		// Let's check user input and send the email
		if(true === $this->checkUserInput() && !$this->error) {
			$this->send();
		}
	}

	/**
	 * The function for checking and processing user input.
	 * Called after submitting the form. In the case of an error,
	 * the $this->error variable gets set to true.
	 *
	 * @return bool
	 */
	protected function checkUserInput()
	{
		$elements = $this->processor->currentForm->elements;
		// The form has no elements is damaged, just terminate the function.
		if(!is_array($elements)) {
			\Util::dataLog(Util::i18n_r('form_validation_error'));
			return false;
		}

		// Get the form method
		$method = $this->processor->currentForm->method;
		// Sanitize user input
		$this->sanitizeInput($method);
		$this->validateRequired($elements);

		// Empty required values
		if($this->error) {
			\MsgReporter::setClause('empty_required_values', array(), true);
			$this->error = true;
		}
		// Check honey field value
		if($this->input->whitelist->honey) {
			\MsgReporter::setClause('honey_field_not_empty', array(), true);
			$this->error = true;
		}
		// Check email field (Note: 'email' field name only)
		if($this->input->whitelist->email) {
			$email = $this->sanitizer->email($this->input->whitelist->email);
			if($email) {
				$this->input->whitelist->email = $email;
			} else {
				\MsgReporter::setClause('invalid_email_address', array(), true);
				$this->error = true;
			}
		}

		// Check reCaptcha
		$recaptcha = $this->processor->findElementByFieldName('ReCaptcha', $elements);

		if(!$this->error && $recaptcha)
		{
			if(empty($this->input->whitelist->{'g-recaptcha-response'})) {
				\MsgReporter::setClause('activate_recaptcha', array(), true);
				$this->error = true;
			}
			$response = json_decode($this->verifySite('https://www.google.com/recaptcha/api/siteverify?secret='
				.$recaptcha->secret_key.'&response='.$this->input->whitelist->{'g-recaptcha-response'}.
				'&remoteip='.@$_SERVER['REMOTE_ADDR']), true );

			if($response['success'] == false) {
				\MsgReporter::setClause('activate_recaptcha', array(), true);
				$this->error = true;
			}
		}

		// Prepare attachments (Note: 'attachment' or 'attachment[]' field name provided)
		if(isset($_FILES['attachment']) && !$this->error) {

			$single_file = false;
			$uploadField = $this->processor->findElementByAttribut(
				'name', 'attachment[]', $this->processor->currentForm->elements
			);
			if(!$uploadField) {
				$uploadField = $this->processor->findElementByAttribut(
					'name', 'attachment', $this->processor->currentForm->elements
				);
				$single_file = true;
			}

			if($uploadField && (($single_file && !empty($_FILES['attachment']['name'])) ||
					(!$single_file && !empty($_FILES['attachment']['name'][0]))))
			{
				$this->input->whitelist->attachment = array();

				if(count($_FILES['attachment']['name']) >= 1 && $uploadField->multiple)
				{
					$i = 0;
					foreach($_FILES['attachment']['name'] as $key => $attachment)
					{
						if($this->processor->config->maxFileUploads && $this->processor->config->maxFileUploads <= $i) {

							\MsgReporter::setClause('maximum_attachment_reached', array(
								'file_name' => $this->sanitizer->text($_FILES['attachment']['name'][$key]),
								'max_number' => $this->processor->config->maxFileUploads
							), true
							);
							continue;
						}
						if($_FILES['attachment']['error'][$key] == UPLOAD_ERR_OK) {
							$this->input->whitelist->attachment[$i] = array('tmp_name' =>
								$_FILES['attachment']['tmp_name'][$key],
								'name' => $_FILES['attachment']['name'][$key]
							);
							$i++;
						} else {
							\MsgReporter::setClause('a_file_not_sent', array(
									'file_name' => $this->sanitizer->text($_FILES['attachment']['name'][$key]))
							);
						}
					}
				} else
				{
					if($_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
						$this->input->whitelist->attachment[0] = array('tmp_name' =>
							$_FILES['attachment']['tmp_name'],
							'name' => $_FILES['attachment']['name']
						);
					} else {
						\MsgReporter::setClause('no_file_sent', array(), true);
					}
				}
			}
		}

		// Override field values
		if($this->error) {
			$this->reFillFieldValues($elements);
			$this->processor->currentForm->elements = $elements;
			// Cache enabled?
			/*if($this->processor->config->imFormsCache) {
				$slug = get_page_slug(false);
				$sectionCache = $this->processor->getCache();
				$sectionCache->get($slug.'-'.$this->processor->currentForm->name, 0);
			}*/
			return false;
		}

		return true;
	}

	/**
	 * Note, this method is only for validations of email text reasonable
	 * Sanitized variables like: $this->input->whitelist->your_field_name is created with it
	 *
	 * @param $method
	 */
	protected function sanitizeInput($method)
	{
		foreach($this->input->{$method} as $key => $value) {
			$this->input->whitelist->{$key} = filter_var($value, FILTER_SANITIZE_MAGIC_QUOTES);
		}
	}

	/**
	 * Verify if the required fields contain any data
	 *
	 * @param $elements
	 */
	protected function validateRequired($elements)
	{
		foreach($elements as $element_id => $element) {
			if(isset($element->required) && $element->required && !$this->input->whitelist->{$element->name}) {
				$this->emptyRequired[] = $element_id;
				$this->error = true;
			}
			// Element is an array
			if(!empty($element->elements)) {
				$this->validateRequired($element->elements);
			}
		}
	}

	/**
	 * re-fills in all inputs
	 *
	 * @param $elements
	 */
	protected function reFillFieldValues($elements)
	{
		foreach($elements as $element_id => $element) {
			// Element is an array
			if(!empty($element->elements)) { $this->reFillFieldValues($element->elements);}
			// Element is an Input
			$elementName = $this->processor->getElementName($element);
			if($elementName == 'Input') {
				$element->value = htmlspecialchars(stripslashes($this->input->whitelist->{$element->name}));
			// Element is a Textarea
			} elseif($elementName == 'Textarea') {
				$element->content = htmlspecialchars(stripslashes($this->input->whitelist->{$element->name}));
			}
		}
	}


	/**
	 * Submit email message
	 *
	 * @return bool
	 */
	protected function send()
	{
		// SMTP isn't enabled, change to use PHP mail() function
		if(!$this->processor->config->useSmtp) {
			$this->mailer->IsMail();
		}
		$this->mailer->addReplyTo($this->input->whitelist->email, $this->input->whitelist->name);
		$this->mailer->addAddress($this->mailer->From, $this->mailer->FromName);
		$this->mailer->Subject = ($this->input->whitelist->subject) ? $this->input->whitelist->subject :
			$this->parser->render(Util::i18n_r('default_subject'), array(
				'form_name' => $this->processor->currentForm->name)
			);

		// Let's create our mail body
		$this->mailer->Body = '';
		$this->buildMailBody($this->processor->currentForm->elements);

		if($this->input->whitelist->attachment) {
			foreach($this->input->whitelist->attachment as $attachment) {
				$this->mailer->AddAttachment($attachment['tmp_name'], $attachment['name']);
			}
		}
		if($this->mailer->send()) {
			\MsgReporter::setClause('email_success_message');
			return true;
		}
		$this->error = true;
		return false;
	}

	/**
	 * The function is used to compose the email body
	 *
	 * @param $elements - Form elements
	 *
	 */
	protected function buildMailBody($elements) {
		foreach($elements as $element) {
			// Element is an array
			if(!empty($element->elements)) { $this->buildMailBody($element->elements); }

			if(isset($element->name) && !empty($this->input->whitelist->{$element->name})) {
				$this->mailer->Body .= $element->name .': '. @stripslashes($this->input->whitelist->{$element->name})."\r\n\r\n";
			}
		}
	}


	/**
	 * Let Google verify the captcha
	 *
	 * @param $url
	 *
	 * @return mixed
	 */
	protected function verifySite($url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_USERAGENT,
			"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16");
		$curlData = curl_exec($curl);
		curl_close($curl);
		return $curlData;
	}
}

/**
 * Class Mailer
 *
 * This is a simple PHPMailer class wrapper.
 * We use it to pre-fill the variables with our configuration parameters
 *
 * @package ImForms
 */
class Mailer extends PHPMailer
{
	protected $config;

	public function __construct($config) {
		parent::__construct();
		$this->config = $config;
		$this->setDefaults();
	}

	protected function setDefaults()
	{
		try
		{
			$this->CharSet = $this->config->emailCharSet;
			if($this->config->useSmtp == 1) $this->isSMTP();
			$this->setLanguage($this->config->mailerLanguage);
			$this->SMTPDebug = $this->config->mailerDebug;
			$this->Host = $this->config->smtpHostname;
			$this->SMTPAuth = true;
			$this->Username = $this->config->smtpUser;
			$this->Password = $this->config->smptPassword;
			$this->SMTPSecure = $this->config->smtpEncryption;
			$this->Port = $this->config->smtpPort;
			$this->From = $this->config->emailFrom;
			$this->FromName = $this->config->emailFromName;
			$this->isHTML(false);

		} catch (phpmailerException $e)
		{
			$error = $e->errorMessage();
			\Util::dataLog($error);
			return false;
		}
	}

	public function send()
	{
		$o = '';
		ob_start();
		if(!parent::send()) {
			\Util::dataLog('Mailer Error: ' . $this->ErrorInfo);
			@ob_end_clean();
			return false;
		}
		@ob_end_clean();
		return true;
	}
}
