<?php

namespace EnuygunCom\OpcacheClearBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('enuygun_com_opcache_clear');

        $rootNode
            ->isRequired()
            ->children()
            ->scalarNode('host_ip')->isRequired()->end()
            ->scalarNode('host_name')->isRequired()->end()
            ->scalarNode('web_dir')->isRequired()->end()
            ->scalarNode('app_version')->isRequired()->end()
            ->scalarNode('app_key')->defaultValue('x-enuygun-app-version')->end()
            ->enumNode('protocol')->values(array('http', 'https'))->defaultValue('http')->end()
            ->variableNode('ip_filter')->end()
            ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
    /**
     * Generates the configuration tree.
     */
    public function getConfigTree()
    {
        return $this->getConfigTreeBuilder()->buildTree();
    }
}
