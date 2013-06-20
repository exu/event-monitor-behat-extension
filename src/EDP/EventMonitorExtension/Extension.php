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

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $loader->load('services.xml');

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
        if (isset($config['debug'])) {
            $container->setParameter('behat.event_monitor.debug', (bool) $config['debug']);
        }
        if (isset($config['output_file_type'])) {
            $container->setParameter('behat.event_monitor.output_file_type', $config['output_file_type']);
        }
        if (isset($config['output_file_name'])) {
            $container->setParameter('behat.event_monitor.output_file_name', $config['output_file_name']);
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
                scalarNode('debug')->
                end()->
                scalarNode('output_file_type')->
                    defaultValue("csv")->
                end()->
                scalarNode('output_file_name')->
                    defaultValue("out.csv")->
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
