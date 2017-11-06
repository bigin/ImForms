# ImForms

ImForms is a light-weight, user-friendly plugin for GetSimple that allows you to build and manage customizable forms for your website. ImForms is modular in design and has an implemented method for embedding custom form processing modules. An `EmailTransmitter` module for sending emails is built-in as default, but you are free to extend the functionality as required by writing your own modules for processing the form data.

### Requirements
You will need to have ItemManager installed to get ImForms running.
You have to run at least PHP 5.6.0 on your server.
You will need a configured mail server to send and receive emails from your GetSimple website (Note: maybe it won't work on the local server)

### Installation
First install and configure ItemManager if you haven't done it yet. Install now the ImForms plugin by unpacking the archive and uploading its contents to your server in the /plugins/ directory of your GS installation. Activate the ImForms plugin in GS admin area under plugins menu. Let's click the button under Pages » ImForms (in the sidebar) to start the installation procedure. Firstly, installation wizard automatically checks system requirements. In case there are any issues, fix them and refresh the page. During the installation the system creates a demo contact form for you, so that you can get an idea of how to create your own forms.

### Advanced settings
The global configuration settings are managed within the custom.config.php file. By default, however, there is no custom.config.php file in your /plugins/im_forms/inc/ directory, you have to create this file first. The directory /plugins/im_forms/inc/ contains a config.php file. ImForms automatically populates several entries into this file during the installation routine. You will need to make a copy of the config.php file and name it custom.config.php, that one will take priority over default ImForms settings. Now you can modify all variables listed in the custom.config.php file suit your needs. Pay special attention to the email settings and add your real email address there, so that you can receive the emails.

A valid configuration for a Gmail account could look something like this:
```php
$config->emailFrom = 'your.email@gmail.com';
$config->emailFromName = 'Your Website';
$config->useSmtp = true;
    $config->smtpHostname = 'smtp.gmail.com';
    $config->smtpUser = 'your.email@gmail.com';
    $config->smptPassword = 'Your Password';
    $config->smtpEncryption = 'START_TLS';
    $config->smtpPort = 25;
```
