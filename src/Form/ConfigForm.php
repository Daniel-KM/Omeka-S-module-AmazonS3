<?php
namespace AmazonS3\Form;

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorAwareTrait;

class ConfigForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    protected $local_storage = '';

    public function setLocalStorage($local_storage)
    {
        $this->local_storage = $local_storage;
    }

    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    public function init()
    {
        $this->add([
            'name' => 'amazons3_access_key_id',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Access Key Id', // @translate
                'info' => $this->translate('First part of access keys that grants programmatic access to your resources. Example: AKIAIOSFODNN7EXAMPLE'), // @translate
            ],
        ]);

        $this->add([
            'name' => 'amazons3_secret_access_key',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Secret Access Key', // @translate
                'info' => $this->translate("Second part of access keys that grants programmatic access to your resources. Example: wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"), // @translate
            ],
        ]);

        $this->add([
            'name' => 'amazons3_bucket',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Bucket', // @translate
                'info' => $this->translate('Public cloud storage resource available in AWS S3, an object storage offering. Similar to file folders, store objects, which consist of data and its descriptive metadata.'), // @translate
            ],
        ]);

        $this->add([
            'name' => 'amazons3_region',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Region', // @translate
                'info' => $this->translate(sprintf('AWS S3 region. Navigate to %s for More info', 'https://docs.aws.amazon.com/general/latest/gr/rande.html')), // @translate
            ],
        ]);

        $this->add([
            'name' => 'amazons3_expiration',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Expiration (minutes)', // @translate
                'info' => $this->translate("If an expiration time is set and grater than zero, we're uploading private files and using signed URLs. If not, we're uploading public files."), // @translate
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'amazons3_access_key_id',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'amazons3_secret_access_key',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'amazons3_bucket',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'amazons3_endpoint',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'amazons3_expiration',
            'required' => false,
            'filters' => [
                ['name' => 'Int'],
            ],
        ]);
    }

    protected function getSetting($name)
    {
        return $this->settings->get($name);
    }

    protected function translate($args)
    {
        $translator = $this->getTranslator();
        return $translator->translate($args);
    }
}
