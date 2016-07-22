<?php

namespace Maven\Bundle\EbayConnectorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class MavenEbayConnectorExtension
 *
 * @package Maven\Bundle\EbayConnectorBundle\DependencyInjection
 */
class MavenEbayConnectorExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('entities.yml');
        $loader->load('processors.yml');
        $loader->load('writes.yml');
        $loader->load('form_types.yml');
    }
}
