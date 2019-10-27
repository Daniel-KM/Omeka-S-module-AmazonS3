<?php
/*
 * Amazon S3
 *
 * Files store that integrates with Amason S3.
 *
 * Copyright Daniel Berthereau 2019
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */
namespace AmazonS3;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use AmazonS3\File\Store\AwsS3;
use Generic\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        /*
         * Override default File store alias to use Amazon S3 one instead.
         * Note: does not work from module config (loaded alphabetically).
         */
        /** @var \Zend\ServiceManager\ServiceManager $serviceLocator */
        $serviceLocator = $this->getServiceLocator();
        $serviceLocator->setAlias('Omeka\File\Store', File\Store\AwsS3::class);

        // Override ArchiveRepertory File classes to work with Amazon S3.
        if ($this->archiveRepertoryIsActive()) {
            $serviceLocator->setAlias('ArchiveRepertory\FileWriter', File\ArchiveRepertory\FileWriter::class);
            $serviceLocator->setAlias('ArchiveRepertory\FileManager', File\ArchiveRepertory\FileManager::class);
        }

        // Add autoloader for AWS SDK classes.
        require_once __DIR__ . '/vendor/autoload.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        if (!file_exists(__DIR__.'/vendor/autoload.php')) {
            $t = $serviceLocator->get('MvcTranslator');
            throw new ModuleCannotInstallException(
                $t->translate('The AWS SDK library should be installed.') // @translate
                    . ' ' . $t->translate('See module’s installation documentation.') // @translate
            );
        }
        parent::install($serviceLocator);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $result = parent::handleConfigForm($controller);
        if (!$result) {
            return false;
        }

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        /** @var File\Store\AwsS3 $store */
        $store = $services->get(File\Store\AwsS3::class);

        // Get all buckets and check client connection.
        $buckets = $store->getBuckets();
        if ($buckets === false) {
            $controller->messenger()->addErrors(['Wrong credentials. Unable to connect to Amazon S3 service.']); // @translate
            return false;
        }

        //check if specified bucket exists
        if (is_array($buckets) && !in_array($settings->get(AwsS3::OPTION_BUCKET), $buckets)) {
            $controller->messenger()->addErrors([sprintf(
                'Wrong bucket. Please specify an existing one, like: %s', // @translate
                implode(', ', array_slice($buckets, 0, 3))
            )]);
            return false;
        }

        $region = $store->determineBucketRegion();
        if ($region !== false && $region != $settings->get(AwsS3::OPTION_REGION)) {
            $controller->messenger()->addErrors([sprintf(
                'Wrong region. Please use region of a bucket: %s', // @translate
                $region
            )]);
            return false;
        }
    }

    protected function archiveRepertoryIsActive()
    {
        static $archiveRepertoryIsActive;
        if (is_null($archiveRepertoryIsActive)) {
            $archiveRepertoryIsActive = $this->isModuleActive('ArchiveRepertory');
        }
        return $archiveRepertoryIsActive;
    }
}
