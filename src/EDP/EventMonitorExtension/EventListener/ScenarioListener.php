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
        $this->debug = (int) $debug;
        fwrite(STDERR, var_export($this->debug, 1) . "\n");
        $writerClass = '\\EDP\\EventMonitorExtension\\Writer\\' . ucfirst($outputFileType);
        if (class_exists($writerClass)) {
            $this->writer = new $writerClass($outputFileName);
        } else {
            throw new \Exception('Writer class ' . $writerClass . ' not found');
        }

        if ($outputFileType == 'csv') {
            `rm {$outputFileName}`;
            $data = ['date', 'scenario', 'outline no', 'step', 'step no', 'events'];
            $this->writer->write($data);
        }
    }


    public function getTags()
    {
        return $this->tags;
    }

    public function getResult()
    {
        return $this->result;
    }


    public function valid(array $tags=[])
    {
        return in_array("javascript", array_merge($this->featureTags, $tags));
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
        $this->featureTags = $event->getFeature()->getTags();
        $this->debug == 2 && fwrite(STDERR, "TAGS: " . var_export($this->featureTags, 1) . "\n");
    }

    public function beforeOutlineExample($event)
    {
        $this->outline++;
    }

    public function beforeStep($event)
    {
        if (!$this->valid($event->getLogicalParent()->getTags())) {
            return false;
        }

        $this->attachJavascript($event);
        $this->step++;
        $subtitle = addslashes($this->getStepText($event));

        $js = <<<JS
        try {
            var jqExists = $.guid ? "JQ Enabled":  "JQ is dead";
        } catch (e) {
            var jqExists = "JQ is dead";
        }

        {$this->jsSnippetLogFunction()}
        log("Step {$this->step} {$subtitle} ", jqExists, 14);
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
              ->evaluateScript("return window.s");

        if ($result) {
            $title = $event->getLogicalParent()->getTitle();
            $subtitle = $this->getStepText($event);
            $this->collectResult($title, $subtitle, $result);
        }

    }

    public function jsSnippetLogFunction()
    {

        if ($this->debug) {
            $js = <<<JS

            function log(m, n, fontSize) {
                var container = document.getElementById("debug_container");
                if(!fontSize) {
                    fontSize = 9;
                }
                if(!container) {
                    var container = document.createElement('div');
                    container.setAttribute('style', 'width: 100%; position:fixed; bottom:0; height:500px; overflow: auto; background: #111; color: #fff; font-family: monotype');
                    container.setAttribute('id', 'debug_container');
                    document.body.appendChild(container);
                }

                var newdiv = document.createElement('div');
                newdiv.setAttribute('style', 'font-size:'+fontSize+'px');
                newdiv.setAttribute('id', 'step{$this->step}');
                newdiv.innerHTML = m + (n ? " >> " + n: "") ;
                container.appendChild(newdiv);
                container.scrollTop = container.scrollHeight;
            }
JS;
            return $js;

        } else {
            $js = <<<JS

            function log(m, n, fontSize) {
                return console.log(m,n);
            }
JS;
        }

        return $js;
    }

    public function attachJavascript($event)
    {
        $js = <<<JS
        // I'm not able to see functions attached in browser
        // between 2 executeScript method run
        {$this->jsSnippetLogFunction()}

        if (window.la) {
            log('Listeners already attached');
            return true;
        }

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

        function defaultListener(listenerType) {
            var type = listenerType;
            return function(e) {
                var id = getId(e);
                if(!window.s[id]) window.s[id] = {};
                if(!window.s[id][type]) window.s[id][type] = 0;
                window.s[id][type]++;

                if(["input", "keyup"].indexOf(type) < 0) {
                    log(id + " " + type + " " + window.s[id][type]);
                }

                if(["input", "keyup"].indexOf(type) >= 0 && window.s[id][type] <= 1) {
                    log(id + " " + type + " " + window.s[id][type] + " and probably more input events");
                }

                e.stopPropagation();
                return true;
            };
        };

        var elements = document.querySelectorAll("INPUT, SELECT, BUTTON, TEXTAREA");
        var events = ["input",  "change", "click", "focus", "blur", "keyup"];
        var listeners = [];

        for (i in events) {
            listeners.push({type: events[i], callback: defaultListener(events[i])});
        }


        r(function(){
            log("Resetting stats and pinning listeners to controls");
            window.s = {};
            document.eventsAttached = {};

            var randomNumber = randr();

            for(i in elements) {
                if(elements[i].addEventListener && elements[i].id) {
                    for(j in listeners) {
                        elements[i].removeEventListener(listeners[j].type, listeners[j].callback);
                        elements[i].addEventListener(listeners[j].type, listeners[j].callback);
                    }
                }
            }

            log("Adding listeners to : " + elements.length + " listeners");

            window.la = true;
        });
JS;

        $event->getContext()->getSession()->executeScript($js);
    }

    public function afterSuite($event)
    {
        if ($this->valid() && $this->result) {
            $this->debug == 2 && fwrite(STDERR, var_export($this->result, 1) . "\n");
        }
    }

    protected function collectResult($title, $subtitle = 'default', $result = [])
    {
        /*
         * @todo design data schema
         */
        $data = [date('Y-m-d H:i:s'), $title, $this->outline, $subtitle, $this->step, json_encode($result)];
        $this->debug == 2 && fwrite(STDERR, var_export($data, 1) . "\n");
        $this->writer->write($data);
        $this->result[] = $data;
        return true;
    }

    public function test($param)
    {
        /* if (!$this->valid()) { */
        /*     return false; */
        /* } */

        $this->debug == 2 && fwrite(STDERR, var_export(get_class($param), 1) . "\n");
    }

    public function getStepText($event)
    {
        if ($event->getStep() instanceof \Behat\Gherkin\Node\ExampleStepNode) {
            $subtitle = $event->getStep()->getText();
        } elseif ($event->getStep() instanceof \Behat\Gherkin\Node\StepNode) {
            $subtitle = $event->getStep()->getText();
        } else {
            $subtitle = 'default';
        }

        return $subtitle;
    }
}
