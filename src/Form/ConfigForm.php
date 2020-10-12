<?php declare(strict_types=1);
namespace AmazonS3\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;

class ConfigForm extends Form
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'amazons3_access_key_id',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Access Key Id', // @translate
                    'info' => 'First part of access keys that grants programmatic access to your resources. Example: AKIAIOSFODNN7EXAMPLE', // @translate
                ],
                'attributes' => [
                    'id' => 'amazons3_access_key_id',
                    'required' => true,
                ],
            ])
            ->add([
                'name' => 'amazons3_secret_access_key',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Secret Access Key', // @translate
                    'info' => 'Second part of access keys that grants programmatic access to your resources. Example: wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', // @translate
                ],
                'attributes' => [
                    'id' => 'amazons3_access_key_id',
                    'required' => true,
                ],
            ])
            ->add([
                'name' => 'amazons3_bucket',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Bucket', // @translate
                    'info' => 'Public cloud storage resource available in AWS S3, an object storage offering. Similar to file folders, store objects, which consist of data and its descriptive metadata.', // @translate
                ],
                'attributes' => [
                    'id' => 'amazons3_bucket',
                    'required' => true,
                ],
            ])
            ->add([
                'name' => 'amazons3_region',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Region', // @translate
                    'info' => sprintf(
                        'AWS S3 region. Navigate to %s for More info', // @translate
                        'https://docs.aws.amazon.com/general/latest/gr/rande.html'
                    ),
                ],
                'attributes' => [
                    'id' => 'amazons3_region',
                    'required' => true,
                ],
            ])
            ->add([
                'name' => 'amazons3_expiration',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Expiration (minutes)', // @translate
                    'info' => 'If an expiration time is set and greater than zero, we’re uploading private files and using signed URLs. If not, we’re uploading public files.', // @translate
                ],
                'attributes' => [
                    'id' => 'amazons3_expiration',
                ],
            ])
        ;

        $this->getInputFilter()
            ->add([
                'name' => 'amazons3_access_key_id',
                'required' => true,
            ])
            ->add([
                'name' => 'amazons3_secret_access_key',
                'required' => true,
            ])
            ->add([
                'name' => 'amazons3_bucket',
                'required' => true,
            ])
            ->add([
                'name' => 'amazons3_region',
                'required' => true,
            ])
        ;
    }
}
