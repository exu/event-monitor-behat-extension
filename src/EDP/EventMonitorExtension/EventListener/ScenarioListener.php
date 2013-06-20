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
    protected $tags = [];
    protected $featureTags = [];
    protected $outline = 0;

    public function __construct($outputFileType, $outputFileName, $debug)
    {
        $this->debug = $debug;
        $writerClass = '\\EDP\\EventMonitorExtension\\Writer\\' . ucfirst($outputFileType);
        if (class_exists($writerClass)) {
            $this->writer = new $writerClass($outputFileName);
        } else {
            throw new \Exception('Writer class ' . $writerClass . ' not found');
        }
    }

    public function valid()
    {
        $valid = in_array("javascript", $this->tags);
        /* $this->debug && fwrite(STDERR, "Tags are " . ($valid ?: "in") . "valid\n"); */
        /* $this->debug && fwrite(STDERR, var_export($this->tags, 1) . "\n"); */
        return $valid;
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
            'beforeScenario' => "test",
            'afterScenario' => "test",
            'beforeOutlineExample' => "beforeOutlineExample",
            'afterOutlineExample' => "test",
            'beforeStep' => "beforeStep",
            'afterStep' => "afterStep"
        );
    }

    public function beforeFeature($event)
    {
        $this->outline = 0;
        $this->tags = $event->getFeature()->getTags();

        foreach ($event->getFeature()->getScenarios() as $scenario) {
            $this->tags = array_unique(array_merge($this->tags, $scenario->getTags()));
        }

        $this->debug && fwrite(STDERR, "TAGS: " . var_export($this->tags, 1) . "\n");
        $this->outline = 0;
    }

    public function beforeOutlineExample($event)
    {
        $this->outline++;
    }

    public function beforeStep($event)
    {

        if (!$this->valid()) {
            return false;
        }

        /* $this->debug && fwrite(STDERR, "Before step" . "\n"); */

        $js = <<<JS

        document.statistics = {};

        function randr() {
            return parseInt(Math.random() * 100989898980000);
        }

        function r(f) {
            (/complete|loaded|interactive/.test(document.readyState))
            ? f()
            : setTimeout(ready, 9, f);
        };

        function getId(e) {
            return  e.target.id ? e.target.id : "TAG_"+e.target.tagName;
        };

        function defaultListener(type) {
            var randomNumber = randr();
            var callback = function(e) {
                var id = getId(e);
                if(!document.statistics[id]) document.statistics[id] = {};
                if(!document.statistics[id][type]) document.statistics[id][type]=0;
                document.statistics[id][type]++;
                console.log(randomNumber + ": " + id, " " + type, document.statistics[id][type]);
                e.stopPropagation();
                return true;
            };

            return callback;
        };

        r(function(){
            console.log("Resetting stats and pinning listeners to controls");
            document.statistics = {};
            document.eventsAttached = {};

            var counter = 0;
            var elements = document.body.getElementsByTagName("*");
            var events = ["input", "change", "click", "focus", "blur", "keyup"];
            var listeners = [];

            for (i in events) {
                listeners.push({type: events[i], callback: defaultListener(events[i])});
            }

            var randomNumber = randr();

            for(i in elements) {
                if(elements[i].addEventListener && elements[i].id && ["INPUT", "SELECT", "BUTTON"].indexOf(elements[i].tagName) >= 0) {
                    for(j in listeners) {
                        var itemKey = elements[i].tagName+"#"+elements[i].id;

                        //bind event only once in session
                        if(!document.eventsAttached[itemKey]) document.eventsAttached[itemKey] = [];
                        if(document.eventsAttached[itemKey].indexOf(listeners[j].type) < 0) {
                            document.eventsAttached[itemKey].push(listeners[j].type);
                            elements[i].removeEventListener(listeners[j].type, listeners[j].callback);
                            elements[i].addEventListener(listeners[j].type, listeners[j].callback);
                            console.log(randomNumber + "event listener " + listeners[j].type + " attached to: " + elements[i].id, elements[i].tagName);
                        }
                    }
                }
            }
        });
JS;

        $event->getContext()->getSession()->executeScript($js);
    }

    public function afterStep(StepEvent $event)
    {
        if (!$this->valid()) {
            return false;
        }

        $result = $event->getContext()
              ->getSession()
              ->evaluateScript("return document.statistics");

        $event->getContext()
              ->getSession()
              ->executeScript("document.statistics = {};");


        $events = $event->getContext()
              ->getSession()
              ->evaluateScript("return document.eventsAttached");

        $this->debug === 2 && fwrite(STDERR, var_export($events, 1) . "\n");

        if ($result) {
            $title = $event->getLogicalParent()->getTitle();

            if ($event->getStep() instanceof \Behat\Gherkin\Node\ExampleStepNode) {
                $subtitle = $event->getStep()->getText();
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
        if ($this->valid() && $this->result) {
            $this->debug === 2 && fwrite(STDERR, var_export($this->result, 1) . "\n");
        }
    }

    protected function collectResult($title, $subtitle = 'default', $result = [])
    {
        /*
         * @todo design data schema
         */
        $data = [date('Y-m-d H:i:s'), $title, $this->outline, $subtitle];
        foreach ($result as $id => $events) {
            array_push($data, $id);
            array_push($data, json_encode($events));
        }

        $this->debug === 2 && fwrite(STDERR, var_export($data, 1) . "\n");
        $this->writer->write($data);

        $this->result[] = $data;
        return true;
    }

    public function test($param)
    {
        /* if (!$this->valid()) { */
        /*     return false; */
        /* } */

        $this->debug === 2 && fwrite(STDERR, var_export(get_class($param), 1) . "\n");
    }
}
