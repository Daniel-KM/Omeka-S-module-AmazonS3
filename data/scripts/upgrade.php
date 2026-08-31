<?php declare(strict_types=1);

namespace AmazonS3;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $oldVersion
 * @var string $newVersion
 */

/**
 * @var \Omeka\Settings\Settings $settings
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$settings = $services->get('Omeka\Settings');
$translate = $plugins->get('translate');
$messenger = $plugins->get('messenger');

if (!$services->has('Omeka\Cipher')) {
    $message = new \Omeka\Stdlib\Message(
        $translate('The module %1$s should be installed and active: the secret access key is now encrypted at rest.'), // @translate
        'Common'
    );
    $messenger->addError($message);
    throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $translate('Missing requirement. Unable to upgrade.')); // @translate
}

if (version_compare($oldVersion, '3.4.5', '<')) {
    // Encrypt the secret access key stored in clear by previous versions.
    // Cipher::encrypt() skips an already encrypted value, so replaying a
    // partial upgrade never double-encrypts it.
    $secret = (string) $settings->get(File\Store\AwsS3::OPTION_AWS_SECRET_KEY, '');
    if ($secret !== '') {
        $settings->set(
            File\Store\AwsS3::OPTION_AWS_SECRET_KEY,
            $services->get('Omeka\Cipher')->encrypt($secret)
        );
    }

    $message = new \Omeka\Stdlib\Message(
        $translate('The secret access key is now encrypted at rest. Define the key "%1$s" in the file "%2$s" to enable it.'), // @translate
        'security.secret_key', 'config/local.config.php'
    );
    $messenger->addSuccess($message);
}
