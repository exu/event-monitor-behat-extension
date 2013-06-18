<?php
namespace EDP\EventMonitorExtension\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;

/**
 * Scenario event listener
 *
 * @author Jacek Wysocki <jacek.wysocki@gmail.com>
 */
class AfterScenarioListener implements EventSubscriberInterface
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'afterScenario' => 'afterScenario',
            'beforeScenario' => 'beforeScenario',
        );
    }


    /**
     * Before Scenario hook
     *
     * @param ScenarioEvent $event
     */
    public function beforeScenario(ScenarioEvent $event)
    {
        echo "<PRE>" . var_export("lalalala", 1) . "</PRE>";
    }

    /**
     * After Scenario hook
     *
     * @param ScenarioEvent $event
     */
    public function afterScenario(ScenarioEvent $event)
    {
        $scenario = $event->getScenario();
        $feature = $scenario->getFeature();
        $url = $feature->getFile();

        echo "<PRE>" . var_export("sialalalala", 1) . "</PRE>";
    }
}
