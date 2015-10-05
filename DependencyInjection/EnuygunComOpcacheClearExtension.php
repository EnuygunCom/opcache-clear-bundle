<?php

namespace EnuygunCom\OpcacheClearBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EnuygunComOpcacheClearExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->process($configuration->getConfigTree(), $configs);

        $container->setParameter('enuygun_com_opcache_clear.host_ip', $config['host_ip']);
        $container->setParameter('enuygun_com_opcache_clear.host_name', $config['host_name']);
        $container->setParameter('enuygun_com_opcache_clear.web_dir', $config['web_dir']);
        $container->setParameter('enuygun_com_opcache_clear.protocol', $config['protocol']);
        $container->setParameter('enuygun_com_opcache_clear.ip_filter', $config['ip_filter']);
        $container->setParameter('enuygun_com_opcache_clear.app_version', $config['app_version']);
    }
}
