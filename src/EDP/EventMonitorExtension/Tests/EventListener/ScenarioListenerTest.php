<?php
namespace EDP\EventMonitorExtension\Tests\EventListener;

use EDP\EventMonitorExtension\EventListener\ScenarioListener;
use Mockery as m;

class ScenarioListenerTest extends \PHPUnit_Framework_TestCase
{
    public function writers()
    {
        return [
            ["csv", "/tmp/some-nasty-csv-test-file.csv"]
        ];
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @dataProvider writers
     */
    public function testValidatorWithoutStepCall($writerType, $writerFileName)
    {
        $listener = new ScenarioListener($writerType, $writerFileName, false);
        $this->assertFalse($listener->valid());
    }

    /**
     * @dataProvider writers
     */
    public function testBeforeStep($writerType, $writerFileName)
    {
        $listener = new ScenarioListener("csv", $writerFileName, false);

        $parent = m::mock('parentMock', ['getTags' => ["javascript"]]);
        $session = m::mock('sessionMock');
        $session->shouldReceive('executeScript')->times(2);
        $context = m::mock('contextMock', ['getSession' => $session]);
        $step = m::mock('\Behat\Gherkin\Node\ExampleStepNode', ['getText' => 'mocked text']);

        $event = m::mock('\Behat\Behat\Event\StepEvent');
        $event->shouldReceive('getLogicalParent')->times(1)->andReturn($parent);
        $event->shouldReceive('getContext')->times(2)->withAnyArgs()->andReturn($context);
        $event->shouldReceive('getStep')->withAnyArgs()->andReturn($step);
        $listener->beforeStep($event);
    }

    /**
     * @dataProvider writers
     */
    public function testAttachJavascriptTest($writerType, $writerFileName)
    {
        $listener = new ScenarioListener($writerType, $writerFileName, false);
        $session = m::mock('sessionMock');
        $session->shouldReceive('executeScript')->times(1);
        $context = m::mock('contextMock', ['getSession' => $session]);
        $event = m::mock('\Behat\Behat\Event\StepEvent');
        $event->shouldReceive('getContext')->times(1)->andReturn($context);

        $listener->attachJavascript($event);
    }

    /**
     * @dataProvider writers
     */
    public function getStepTextFromDifferentSteps($writerType, $writerFileName)
    {
        $listener = new ScenarioListener($writerType, $writerFileName, false);

        $text = 'mocked text';
        $event = m::mock('\Behat\Gherkin\Node\ExampleStepNode');
        $step = m::mock('\Behat\Gherkin\Node\ExampleStepNode', ['getText' => $text]);
        $event->shouldReceive('getStep')->times(1)->andReturn($step);

        $this->assertEquals($listener->getStepText($event), $text);

        $event = m::mock('\Behat\Gherkin\Node\StepNode');
        $event->shouldReceive('getStep')->times(1)->andReturn($step);

        $this->assertEquals($listener->getStepText($event), $text);

        $event = m::mock('\Behat\Gherkin\Node\OtherFunnynonExistingStep');
        $event->shouldReceive('getStep')->times(1)->andReturn("default");

        $this->assertEquals($listener->getStepText($event), "default");
    }


    /**
     * @dataProvider writers
     *
     * @covers \EDP\EventMonitorExtension\EventListener\ScenarioListener::__construct
     * @covers \EDP\EventMonitorExtension\EventListener\ScenarioListener::getTags
     * @covers \EDP\EventMonitorExtension\EventListener\ScenarioListener::getResult
     * @covers \EDP\EventMonitorExtension\EventListener\ScenarioListener::valid
     * @covers \EDP\EventMonitorExtension\EventListener\ScenarioListener::getSubscribedEvents
     * @covers \EDP\EventMonitorExtension\EventListener\ScenarioListener::beforeStep
     * @covers \EDP\EventMonitorExtension\EventListener\ScenarioListener::afterStep
     * @covers \EDP\EventMonitorExtension\EventListener\ScenarioListener::collectResult
     * @covers \EDP\EventMonitorExtension\EventListener\ScenarioListener::getStepText
     */
    public function testCollectResult($writerType, $writerFileName)
    {
        $listener = new ScenarioListener($writerType, $writerFileName, false);
        $stepParentTitle = "step parent title";
        $stepTitle = "step title";
        $result = ["id" => ["click" => 10, "input" => "123"]];


        $featureEvent = m::mock('featureEventMock', ['getFeature' => m::mock(['getTags' => ["javascript"]])]);
        $listener->beforeFeature($featureEvent);

        $parent = m::mock('parentMock', ["getTitle" => $stepParentTitle]);

        $session = m::mock('sessionMock');
        $session->shouldReceive('evaluateScript')->times(1)->andReturn($result);

        $context = m::mock('contextMock', ['getSession' => $session]);
        $step = m::mock('\Behat\Gherkin\Node\ExampleStepNode', ['getText' => $stepTitle]);

        $event = m::mock('\Behat\Behat\Event\StepEvent');
        $event->shouldReceive('getLogicalParent')->times(1)->andReturn($parent);
        $event->shouldReceive('getContext')->times(1)->withAnyArgs()->andReturn($context);
        $event->shouldReceive('getStep')->withAnyArgs()->andReturn($step);

        $listener->afterStep($event);
        $listenerResult = $listener->getResult()[0];
        // strip date part
        array_shift($listenerResult);
        $this->assertEquals($listenerResult, [$stepParentTitle, 0, $stepTitle, 0, json_encode($result)]);
    }
}
