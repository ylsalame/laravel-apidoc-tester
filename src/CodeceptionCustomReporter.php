<?php

namespace YLSalame\LaravelApiDocTester;

use \Codeception\Event\TestEvent;
use \Codeception\Events;
use \Codeception\Extension;
use \Codeception\Test\Descriptor;
use \Codeception\Test\Interfaces\ScenarioDriven;

class CodeceptionCustomReporter extends \Codeception\Extension
{
    public function _initialize()
    {
        $this->options['silent'] = false; // turn on printing for this extension
    }

    // we are listening for events
    public static $events = [
        Events::TEST_FAIL_PRINT     => 'printAfterFail',
        Events::TEST_AFTER          => 'printAfterTest',
    ];

    public function printAfterTest(TestEvent $event)
    {
        $curlOutput = $this->getCurlCommand($event->getTest());
        $this->writeln(chr(9).'curl command: '.$curlOutput);
    }

    public function printAfterFail(TestEvent $event)
    {
        $this->writeln("CURL command: ");
        $this->writeln("");

        $curlOutput = $this->getCurlCommand($event->getTest());
        $this->output->writeln($curlOutput);
    }

    protected function getCurlCommand($test)
    {
        /*
         * Example curl output (importable into Postman, cli, shell, etc...)
         *
         *      curl -d \"param1=value1&param2=value2\" -H \"Content-Type: application/x-www-form-urlencoded\" -X POST http://localhost:3000/data
         *
        */

        $curlOutput = chr(9).'curl -X {{ submitType }} {{ dataAndUrl }} {{ contentType }}';

        $contentType = '';
        if ($test instanceof ScenarioDriven) {
            $trace = $test->getScenario()->getSteps();
            foreach ($trace as $step) {
                if ($step->getAction() == 'sendGET' || $step->getAction() == 'sendPOST') {
                    $url = $step->getArguments()[0];
                    $parameters = '';
                    foreach ($step->getArguments()[1] as $parameterName => $parameterValue) {
                        $parameters .= $parameterName.'='.$parameterValue.'&';
                    }
                    $parameters = substr($parameters, 0, -1);

                    if ($step->getAction() == 'sendGET') {
                        $submitType = 'GET';
                        $dataAndUrl = '"'.$url.'?'.$parameters.'"';
                    } else {
                        $submitType = 'POST';
                        $contentType = '-H "Content-Type: application/x-www-form-urlencoded"';
                        $dataAndUrl = '-d "'.$parameters.'" '.$url;
                    }
                }
            }

            $curlOutput = str_replace(
                [
                    '{{ contentType }}',
                    '{{ submitType }}',
                    '{{ dataAndUrl }}'
                ],
                [
                    $contentType,
                    $submitType,
                    $dataAndUrl,
                ],
                $curlOutput
            );
        }
        return $curlOutput;
    }
}
