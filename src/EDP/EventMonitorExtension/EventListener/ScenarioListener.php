<?php
namespace EDP\EventMonitorExtension\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EDP\EventMonitorExtension\Writer;

use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;

/**
 * Scenario event listener
 *
 * @author Jacek Wysocki <jacek.wysocki@gmail.com>
 */
class ScenarioListener implements EventSubscriberInterface
{
    protected $debug;

    public function __construct($outputFileType, $outputFileName, $debug)
    {
        $this->debug = $debug;

        $writerClass = '\\EDP\\EventMonitorExtension\\Writer\\' . ucfirst($outputFileType);
        if (class_exists($writerClass)) {
            echo "init writer " . $writerClass . "\n";
            $this->writer = new Writer\Csv($outputFileName);
        } else {
            throw new \Exception('Writer class ' . $writerClass . ' not found');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'beforeSuite' => "test",
            'afterSuite' => "afterSuite",
            'beforeFeature' => "beforeFeature",
            'afterFeature' => "test",
            'beforeScenario' => "beforeScenario",
            'afterScenario' => "test",
            'beforeOutlineExample' => "test",
            'afterOutlineExample' => "test",
            'beforeStep' => "beforeStep",
            'afterStep' => "afterStep"
        );
    }

    public function beforeFeature($event)
    {
        fwrite(STDERR, var_export($event->getFeature()->getTags(), 1) . "\n");
    }

    public function beforeScenario($event)
    {
        fwrite(STDERR, var_export($event->getTags(), 1) . "\n");
    }


    public function beforeStep($event)
    {
        /* $this->debug && fwrite(STDERR, "Before step" . "\n"); */

        $js = <<<JS
        document.statistics = {};


        var r = function(f) {
            (/complete|loaded|interactive/.test(document.readyState))
            ? f()
            : setTimeout(ready, 9, f);
        };

        function getId(e) {
            return  e.target.id ? e.target.id : "TAG_"+e.target.tagName;
        }

        function defaultListener(type) {
            return function(e) {
                var id = getId(e);
                if(!document.statistics[id]) document.statistics[id] = {};
                var d = document.statistics[id];
                if(!d[type]) d[type]=0;
                d[type]++;

                console.log(id, " " + type);
            }
        }

        // attach default listeners
        var events = ["input", "change", "click", "focus", "blur", "keyup"], listeners = [];
        for (var i = 0; i < events.length; i++) {
            listeners.push({type: events[i], callback: defaultListener(events[i])});
        }

        r(function(){
            var elements = document.body.getElementsByTagName("*");

            for(i in elements) {
                if(elements[i].addEventListener) {
                    for(j in listeners) {
                        elements[i].addEventListener(listeners[j].type, listeners[j].callback.bind(elements[i]));
                    }
                }
            }
        });
JS;

        $event->getContext()->getSession()->executeScript($js);
    }

    public function afterStep(StepEvent $event)
    {
        $result = $event->getContext()
              ->getSession()
              ->evaluateScript("return document.statistics");

        if ($result) {
            $title = $event->getLogicalParent()->getTitle();

            if ($event->getStep() instanceof \Behat\Gherkin\Node\ExampleStepNode) {
                $subtitle = $event->getStep()->getCleanText();
            } elseif ($event->getStep() instanceof \Behat\Gherkin\Node\StepNode) {
                $subtitle = $event->getStep()->getText();
            } else {
                $subtitle = 'default';
            }

            $this->collectResult($title, $subtitle, $result);
        }

    }

    public function afterSuite($event)
    {
        if ($this->result) {
            $this->debug && fwrite(STDERR, var_export($this->result, 1) . "\n");
        }
    }

    public function collectResult($title, $subtitle = 'default', $result = [])
    {
        $data = [date('Y-m-d H:i:s'), $title, $subtitle];
        foreach ($result as $id => $events) {
            array_push($data, $id);
            array_push($data, json_encode($events));
        }

        $this->debug && fwrite(STDERR, var_export($data, 1) . "\n");
        $this->writer->write($data);

        $this->result[] = $data;
        return true;
    }

    public function test($param)
    {
        /* $this->debug && fwrite(STDERR, var_export(get_class($param), 1) . "\n"); */
    }
}
