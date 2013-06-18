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
class ScenarioListener implements EventSubscriberInterface
{
    public function __construct()
    {
        fwrite(STDERR, "Listener init\n");
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'beforeSuite' => "test",
            'afterSuite' => "afterSuite",
            'beforeFeature' => "test",
            'afterFeature' => "test",
            'beforeScenario' => "test",
            'afterScenario' => "test",
            'beforeOutlineExample' => "test",
            'afterOutlineExample' => "test",
            'beforeStep' => "beforeStep",
            'afterStep' => "afterStep"
        );
    }

    public function beforeStep($event)
    {
        /* fwrite(STDERR, "Before step" . "\n"); */

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
                var d = document.statistics[type];
                if(!d[id]) d[id]=0;
                d[id]++;

                console.log(id, " " + type);
            }
        }

        // attach default listeners
        var events = ["input", "change", "click", "focus", "blur", "keyup"], listeners = [];
        for (var i = 0; i < events.length; i++) {
            listeners.push({type: events[i], callback: defaultListener(events[i])});
            if(!document.statistics[events[i]]) {
                document.statistics[events[i]] = {};
            }
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

    public function afterStep($event)
    {
        $result = $event->getContext()
              ->getSession()
              ->evaluateScript("return document.statistics");

        if ($result) {
            $this->collectResult($event->getSnippet(), $result);
        }

    }

    public function afterSuite($event)
    {
        fwrite(STDERR, var_export($this->result, 1) . "\n");
    }

    public function collectResult($event, $result)
    {
        if (!isset($event)) {
            $this->result[$event] = $result;
        } else {
            foreach ($result as $event => $ids) {
                foreach ($ids as $id => $count) {
                    if (isset($this->result[$event], $this->result[$event][$id])) {
                        $this->result[$event][$id] += $count;
                    } else {
                        $this->result[$event][$id] = $count;
                    }
                }
            }
        }
    }



    public function test($param)
    {
        /* fwrite(STDERR, var_export(get_class($param), 1) . "\n"); */
    }
}
