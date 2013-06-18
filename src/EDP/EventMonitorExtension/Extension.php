<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\JiraExtension;

use Symfony\Component\Config\FileLocator,
    Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

use Behat\Behat\Extension\ExtensionInterface;

/**
 * A Jira Feature Loader extension for Behat
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class Extension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/services'));
        $loader->load('core.xml');

        if (isset($config['clicks'])) {
            $container->setParameter('behat.event_monitor.clicks', $config['clicks']);
        }
        if (isset($config['keypresses'])) {
            $container->setParameter('behat.event_monitor.keypresses', $config['keypresses']);
        }
        if (isset($config['focus'])) {
            $container->setParameter('behat.event_monitor.focus', $config['focus']);
        }
        if (isset($config['blur'])) {
            $container->setParameter('behat.event_monitor.blur', $config['blur']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(ArrayNodeDefinition $builder)
    {
        $builder->
            children()->
                scalarNode('clicks')->
                    defaultTrue()->
                end()->
                scalarNode('keypresses')->
                    defaultTrue()->
                end()->
                scalarNode('focus')->
                    defaultFalse()->
                end()->
                scalarNode('blur')->
                    defaulFalse()->
                end()->
            end()->
        end();
    }

    /**
     * {@inheritdoc}
     */
    public function getCompilerPasses()
    {
        return array();
    }
}
