<?php
/**
 * @copyright Jacek Wysocki <jacek.wysocki@gmail.com>
 */

namespace EDP\EventMonitorExtension;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

use Behat\Behat\Extension\ExtensionInterface;

/**
 * A event monitor extension
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
                end()->
                scalarNode('blur')->
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
