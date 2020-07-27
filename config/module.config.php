<?php
namespace AmazonS3;

return [
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            File\Store\AwsS3::class => Service\File\Store\StoreFactory::class,
            File\ArchiveRepertory\FileWriter::class => Service\File\ArchiveRepertory\FileWriterFactory::class,
            File\ArchiveRepertory\FileManager::class => Service\File\ArchiveRepertory\FileManagerFactory::class,
        ],
    ],
    'amazons3' => [
        'config' => [
            'amazons3_access_key_id' => null,
            'amazons3_secret_access_key' => null,
            'amazons3_region' => 'us-east-2',
            'amazons3_bucket' => null,
            'amazons3_expiration' => 0,
        ],
    ],
];
