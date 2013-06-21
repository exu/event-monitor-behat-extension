<?php
namespace EDP\EventMonitorExtension\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EDP\EventMonitorExtension\Writer;

use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;

use Edp\RedirectBundle\Driver\Selenium2Driver;

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
    protected $step = 0;

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
            'beforeScenario' => "beforeScenario",
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

    public function beforeScenario($event)
    {
        $event->getContext()->getSession()->executeScript('document.ttt = 123;');
    }


    public function beforeOutlineExample($event)
    {
        $this->outline++;
        $event->getContext()->getSession()->executeScript('document.ttt = 345;');

    }

    public function beforeStep($event)
    {
        $ttt = $event->getContext()->getSession()->evaluateScript('try { return $.badAss = 1; } catch (e) { return e.message; } ');
        fwrite(STDERR, var_export($ttt, 1) . "\n");

        $this->attachJavascript($event);


        if (!$this->valid()) {
            return false;
        }


        if ($event->getStep() instanceof \Behat\Gherkin\Node\ExampleStepNode) {
            $subtitle = $event->getStep()->getText();
        } elseif ($event->getStep() instanceof \Behat\Gherkin\Node\StepNode) {
            $subtitle = $event->getStep()->getText();
        } else {
            $subtitle = 'default';
        }

        $this->step++;

        $subtitle = addslashes($subtitle);

        $js = <<<JS

        try {
            var jqExists = $.guid ? "JQUERY Lives":  "JQUERY is dead";
        } catch (e) {
            var jqExists = "JQUERY is dead";
        }
  var newdiv = document.createElement('div');
  newdiv.setAttribute('id', 'step{$this->step}');
  newdiv.innerHTML = "Step {$this->step} {$subtitle} " + jqExists;
  document.body.appendChild(newdiv);

JS;

        $event->getContext()->getSession()->wait(1000);
        $ttt = $event->getContext()->getSession()->evaluateScript($js. ';document.ttt = ' . rand(1, 100000) . ' ;');
        fwrite(STDERR, $subtitle . " " . var_export($ttt, 1) . "\n");



        /* $this->debug && fwrite(STDERR, "Before step" . "\n"); */
    }

    public function afterStep(StepEvent $event)
    {
        if (!$this->valid()) {
            return false;
        }

        $ttt = $event->getContext()->getSession()->evaluateScript('d = document.getElementById("step{$this->step}"); if(d) { return d.innerHtml;}');
        fwrite(STDERR, var_export($ttt, 1) . "\n");


        $result = $event->getContext()
              ->getSession()
              ->evaluateScript("return window.s");



        $events = $event->getContext()
              ->getSession()
              ->evaluateScript("return document.eventsAttached");

        /* $event->getContext() */
        /*       ->getSession() */
        /*       ->executeScript("window.s = {}; document.eventsAttached = {}"); */

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



    public function attachJavascript($event)
    {
        $js = <<<JS
        function log(m) {
            var newdiv = document.createElement('pre');
            newdiv.setAttribute('id', 'step{$this->step}');
            newdiv.innerHTML = m;
            document.body.appendChild(newdiv);
        }

        try {
            $.guid;

            window.s = {};

            function clickHandler(){
                log("cccc");
            }



            var events = ["input", "change", "click", "focus", "blur", "keyup"];

            function defaultListener(type) {
                var randomNumber = randr();
                return function(e) {
                    var id = getId(e);
                    if(!window.s[id]) window.s[id] = {};
                    if(!window.s[id][type]) window.s[id][type] = 0;
                    window.s[id][type]++;
                    log(randomNumber + ": " + id, " " + type, window.s[id][type]);
                    e.stopPropagation();
                    return true;
                };
            };

            var listeners = {};
            $(events).each(function(e){
                listeners[e] = defaultListener(e);
            });



            $(":input").each(function(){
                var self = $(this);
                $(self).on("change", listeners["change"]);
            });
        } catch (e) {
            log(e.message, "No JQuery loaded");
            return e.message;
        }


        return;

        window.s = {};

        function randr() {
            return parseInt(Math.random() * 100989898980000);
        }

        function r(f) {
            (/complete|loaded|interactive/.test(document.readyState))
            ? f()
            : setTimeout(ready, 19, f);
        };

        function getId(e) {
            return  e.target.id ? e.target.id : "TAG_"+e.target.tagName;
        };

        function defaultListener(type) {
            var randomNumber = randr();
            return function(e) {
                var id = getId(e);
                if(!window.s[id]) window.s[id] = {};
                if(!window.s[id][type]) window.s[id][type] = 0;
                window.s[id][type]++;
                log(randomNumber + ": " + id, " " + type, window.s[id][type]);
                e.stopPropagation();
                return true;
            };
        };

        var elements = document.querySelectorAll("INPUT, SELECT, BUTTON, TEXTAREA");
        var events = ["input", "change", "click", "focus", "blur", "keyup"];
        var listeners = [];

        for (i in events) {
            listeners.push({type: events[i], callback: defaultListener(events[i])});
        }


        r(function(){
            /* if(document.monitorLoded) { */
            /*     log("Monitor loaded"); */
            /*     return false;; */
            /* } */

            log("Resetting stats and pinning listeners to controls");
            window.s = {};
            document.eventsAttached = {};

            var randomNumber = randr();

            for(i in elements) {
                if(elements[i].addEventListener && elements[i].id) {
                    for(j in listeners) {
                        elements[i].removeEventListener(listeners[j].type, listeners[j].callback);
                        elements[i].addEventListener(listeners[j].type, listeners[j].callback);
                        log("Adding "  + listeners[j].type + " to " + elements[i].id);
                    }

                    elements[i].addEventListener("click", function(e) {log("click", getId(e)); });
                    elements[i].addEventListener("change", function(e) {log("change", getId(e)); });

                    /* elements[i].addEventListener("click", clickListener); */
                }
            }
        });
JS;

        $event->getContext()->getSession()->executeScript($js);
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
