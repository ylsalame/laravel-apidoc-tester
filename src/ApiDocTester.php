<?php

namespace YLSalame\LaravelApiDocTester;

use Storage;
use File;
use Symfony\Component\Process\Process;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use YLSalame\LaravelApidocTester\Exceptions\NotificationException;
use YLSalame\LaravelApidocTester\Exceptions\FatalException;

class ApiDocTester extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidoctester
                            {--api_dir= : directory where API classes/methods are kept}
                            {--api_url= : URL to be used when executing tests}
                            {--output_dir= : directory where APIDoc will store its files temporarily}
                            {--keep_files= : defines if APIDoc files will be deleted at the end of the test execution}
                            {--default : answer default (yes) to all questions}
                            {--debug : option to run all processes/commands with debug flags and display full their debug output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use APIDoc blocks to test your APIs';

    protected $apiUrl;

    protected $optionApiDir;
    protected $optionOutputDir;
    protected $optionKeepFiles;
    protected $optionDefault;
    protected $optionDebug;

    protected $baseDirectory;
    protected $stubsDirectory;
    protected $apiDocDirectory;
    protected $codeceptionBin = 'php vendor/bin/codecept';
    protected $codeceptionDirectory;
    protected $codeceptionTestDataDirectory;
    protected $codeceptionTestDataFilesDirectory;

    protected $stubCodeceptionTest;
    protected $stubCodeceptionYml;
    protected $stubCodeceptionSuiteYml;
    protected $stubApidocJson;

    protected $testCaseCount = 1;
    protected $testCaseGroupCount = 1;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            //$this->checkExecutables();
            $this->fetchOptions();
            $this->showInterfaceBlock(1);
            $this->setupPaths();
            $this->loadStubbedFiles();
            $this->createFileStructure();
            $this->generateApiDoc();
            $this->generateCodeceptionConfiguration();
            $this->generateCodeceptionTestFiles();
            $this->executeTests();
            $this->cleanUp();
            $this->doEcho();
            $this->doEcho('APIDoc Tester finished');
            $this->doEcho();
        } catch (FatalException $e) {
            $this->doEcho('Fatal Exception happened - '.$e->getMessage(), 'error');
            die();
        }
    }

    /**
        Get the options for this command

        @return void
     */    
    protected function fetchOptions(): void
    {
        $this->optionApiDir = $this->option('api_dir');
        $this->optionOutputDir = $this->option('output_dir');
        $this->optionKeepFiles = $this->option('keep_files');
        $this->optionDefault = $this->option('default');
        $this->optionDebug = $this->option('debug');

        $this->apiUrl = $this->option('api_url');
    
        if (empty($this->apiUrl)) {
            $this->apiUrl = env('API_URL');
        }

    }

    /**
        Determines the paths for all the directorys involved

        @return void
     */
    protected function setupPaths(): void
    {
        $this->doEcho('Setting up paths');
        $this->stubsDirectory = __DIR__.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR;
        $this->randomDirectoryName = date('Ydm_His_').uniqid();
        $this->baseDirectory = 'testing'.DIRECTORY_SEPARATOR.'API'.DIRECTORY_SEPARATOR.$this->randomDirectoryName.DIRECTORY_SEPARATOR;
        $this->apiDocDirectory = $this->baseDirectory.'apidoc'.DIRECTORY_SEPARATOR;
        $this->codeceptionDirectory = $this->baseDirectory.'codeception'.DIRECTORY_SEPARATOR;
        $this->codeceptionTestDataDirectory = $this->codeceptionDirectory.'test_data'.DIRECTORY_SEPARATOR;
        $this->codeceptionTestDataFilesDirectory = $this->codeceptionTestDataDirectory.'apiDocTester'.DIRECTORY_SEPARATOR;

        $this->doEcho('    stubsDirectory:                    '.$this->stubsDirectory, 'info');
        $this->doEcho('    randomDirectoryName:               '.$this->randomDirectoryName, 'info');
        $this->doEcho('    baseDirectory:                     '.$this->baseDirectory, 'info');
        $this->doEcho('    apiDocDirectory:                   '.$this->apiDocDirectory, 'info');
        $this->doEcho('    codeceptionDirectory:              '.$this->codeceptionDirectory, 'info');
        $this->doEcho('    codeceptionTestDataDirectory:      '.$this->codeceptionTestDataDirectory, 'info');
        $this->doEcho('    codeceptionTestDataFilesDirectory: '.$this->codeceptionTestDataFilesDirectory, 'info');
    }

    /**
        Loads stubbed files

        @return void
     */
    protected function loadStubbedFiles(): void
    {
        $this->doEcho('Loading stubbed files');
        $this->stubCodeceptionTest = File::get($this->stubsDirectory.'codeceptionTest.php');
        $this->stubCodeceptionYml = File::get($this->stubsDirectory.'codeception.yml');
        $this->stubCodeceptionSuiteYml = File::get($this->stubsDirectory.'codeceptionSuite.yml');
        $this->stubApidocJson = File::get($this->stubsDirectory.'apidoc.json');

        $this->doEcho('    stubCodeceptionTest     file loaded from: '.$this->stubsDirectory.'codeceptionTest.php', 'info');
        $this->doEcho('    stubCodeceptionYml      file loaded from: '.$this->stubsDirectory.'codeception.yml', 'info');
        $this->doEcho('    stubCodeceptionSuiteYml file loaded from: '.$this->stubsDirectory.'codeceptionSuite.yml', 'info');
        $this->doEcho('    stubApidocJson          file loaded from: '.$this->stubsDirectory.'apidoc.json', 'info');
    }

    /**
        Creates the directories necessary for the test files

        @return void
     */
    protected function createFileStructure(): void
    {
        $this->doEcho('Organizing directory structure and configuration files');

        Storage::deleteDirectory('testing'.DIRECTORY_SEPARATOR.'API');
        Storage::makeDirectory('testing'.DIRECTORY_SEPARATOR.'API');
        Storage::makeDirectory($this->baseDirectory);
        Storage::makeDirectory($this->apiDocDirectory);
        Storage::makeDirectory($this->codeceptionDirectory);
        Storage::makeDirectory($this->codeceptionTestDataDirectory);
        Storage::makeDirectory($this->codeceptionTestDataFilesDirectory);

        $this->doEcho('    deleting directory: '.storage_path('app').DIRECTORY_SEPARATOR.'testing'.DIRECTORY_SEPARATOR.'API', 'info');
        $this->doEcho('    creating directory: '.storage_path('app').DIRECTORY_SEPARATOR.'testing'.DIRECTORY_SEPARATOR.'API', 'info');
        $this->doEcho('    creating directory: '.storage_path('app').DIRECTORY_SEPARATOR.$this->baseDirectory, 'info');
        $this->doEcho('    creating directory: '.storage_path('app').DIRECTORY_SEPARATOR.$this->apiDocDirectory, 'info');
        $this->doEcho('    creating directory: '.storage_path('app').DIRECTORY_SEPARATOR.$this->codeceptionDirectory, 'info');
        $this->doEcho('    creating directory: '.storage_path('app').DIRECTORY_SEPARATOR.$this->codeceptionTestDataDirectory, 'info');
        $this->doEcho('    creating directory: '.storage_path('app').DIRECTORY_SEPARATOR.$this->codeceptionTestDataFilesDirectory, 'info');

        $this->doEcho(' creating stubbed file: '.base_path().DIRECTORY_SEPARATOR.$this->codeceptionDirectory.'codeception.yml', 'info');
        Storage::put($this->codeceptionDirectory.'codeception.yml', $this->stubCodeceptionYml);

        if (!file_exists(base_path().DIRECTORY_SEPARATOR.'apidoc.json')) {
            $this->doEcho(' creating stubbed file: '.base_path().DIRECTORY_SEPARATOR.'apidoc.json', 'info');
            file_put_contents(base_path().DIRECTORY_SEPARATOR.'apidoc.json', $this->stubApidocJson);
        } else {
            $this->raiseError('The file '.base_path().DIRECTORY_SEPARATOR.'apidoc.json already exists but needs to be generated automatically by this command. Please backup/delete/move it before executing this command again');
        }
    }

    /**
        Deletes the test directory after execution

        @return void
     */
    protected function cleanUp():void
    {
        unlink(base_path().DIRECTORY_SEPARATOR.'apidoc.json');

        //remove the directory with the tests unless the option is set to not delete
        if (!$this->optionKeepFiles) {
            Storage::deleteDirectory($this->baseDirectory);
        }
    }

    protected function checkExecutables()
    {
        //TO-DO verify apidoc
        //TO-DO verify codeception
    }

    protected function showInterfaceBlock(int $interfaceBlock): void
    {
        switch ($interfaceBlock) {
        case 1:
            $this->doEcho();
            $this->doEcho(str_repeat('=', 60));
            $this->doEcho('APIDoc Tester');
            $this->doEcho(str_repeat('=', 60));
            $this->doEcho();

            if ($this->optionDefault) {
                return;
            }

            if (!$this->confirm(chr(9).'Run the Tester?')) {
                throw new FatalException('ApiDocTester aborted');
            } else {
                if ($this->optionKeepFiles) {
                    if (!$this->confirm(chr(9).'APIDoc files will NOT be deleted at the end of execution! Confirm?')) {
                        throw new FatalException('ApiDocTester aborted');
                    }
                }
            }
            break;
        }
    }

    /**
        Displays an error accordingly

        @param String $msg Message to be displayed as an error

        @return void
     */
    protected function raiseError(String $msg): void
    {
        $this->doEcho();
        $this->error(chr(9).str_repeat('*', 60));
        $this->error(chr(9).str_repeat(' ', 60));
        $this->error(chr(9).$msg);
        $this->error(chr(9).str_repeat(' ', 60));
        $this->error(chr(9).str_repeat('*', 60));
        $this->doEcho();
        die();
    }

    /**
        Outputs to the terminal

        @param String $msg  Message to be displayed
        @param String $type The type of message displayed. Available values are line, info, comment, question and error

        @return void
     */
    protected function doEcho(String $msg = '', $type = 'line'): void
    {
        $this->{$type}('    '.$msg);
    }

    /**
        Triggers the APIDoc generation

        @return void
     */
    protected function generateApiDoc(): void
    {
        $this->doEcho('Generating APIDoc files...');

        //create the apidoc files
        $command = 'apidoc -i '.base_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http -o '.storage_path('app').DIRECTORY_SEPARATOR.$this->apiDocDirectory;

        if ($this->optionDebug) {
            $command .= ' --debug';
        }
        $this->executeProcess($command);
    }

    /**
        Triggers Codeception generation by creating the Suite and then building it

        @return void
     */
    protected function generateCodeceptionConfiguration(): void
    {
        $this->doEcho('Generating Codeception configuration...');

        //create the suite
        $this->executeProcess($this->codeceptionBin.' generate:suite apiDocTester -c '.storage_path('app').DIRECTORY_SEPARATOR.$this->codeceptionDirectory.'/codeception.yml');

        //copy the stub to use the APITester actor
        $stubCodeceptionSuiteYml = str_replace('{{ apiUrl }}', $this->apiUrl, $this->stubCodeceptionSuiteYml);
        Storage::put($this->codeceptionTestDataDirectory.'apiDocTester.suite.yml', $stubCodeceptionSuiteYml);

        //build the suite
        $this->executeProcess($this->codeceptionBin.' build -c '.storage_path('app').DIRECTORY_SEPARATOR.$this->codeceptionDirectory.'/codeception.yml');
    }

    /**
        Triggers a command process and return the result

        @param String  $command      Command to be execute in the shell
        @param Boolean $forcedOutput Forces the output to be shown regardless of other configuration options

        @return array
     */
    protected function executeProcess(String $command, Bool $forcedOutput = false): array
    {
        $this->doEcho('    Executing command: '.$command, 'comment');

        $output = [];

        $this->forcedOutput = $forcedOutput;

        $process = new Process($command);
        $process->run(
            function ($type, $buffer) {
                if (Process::ERR === $type) {
                    echo 'ERR > '.$buffer;
                } else {
                    if ($this->forcedOutput) {
                        echo $buffer;
                    } else {
//                        $output[] = $buffer;
                        $this->doEcho('        '.$buffer);
                    }
                }
            }
        );
        return $output;
    }

    /**
        Triggers the tests to be run with the json generated by APIDocs

        @return void
     */
    protected function executeTests(): void
    {
        $this->doEcho('Executing Codeception tests with command: ');
        $command = $this->codeceptionBin.' run apiDocTester -c storage/app/'.$this->codeceptionDirectory.'codeception.yml';
        if ($this->optionDebug) {
            $command .= ' --debug';
        }
        $output = $this->executeProcess($command, true);
    }

    protected function generateCodeceptionTestFiles()
    {
        $this->doEcho('Generating Codeception Test Files from api_data.json data');

        $apiDocs = json_decode((Storage::get($this->apiDocDirectory.DIRECTORY_SEPARATOR.'api_data.json')), true);

        foreach ($apiDocs as $apiDoc) {
            //no success tests defined for this apiDoc then skip it
            if (empty($apiDoc['parameter']['examples'])) {
                continue;
            }

            if (!empty($apiDoc['parameter']['fields']['Parameter'])) {
                $requestParameters = $apiDoc['parameter']['fields']['Parameter'];
            }

            $errorCases = [];
            if (!empty($apiDoc['error']['examples'])) {
                $errorCases = $apiDoc['error']['examples'];
            }
            
            if (!empty($apiDoc['error']['fields'])) {
                $errorReturnFields = $apiDoc['error']['fields'];
            }

            if (!empty($apiDoc['success']['fields'])) {
                $successReturnFields = $apiDoc['success']['fields'];
            }

            $successCases = $apiDoc['parameter']['examples'];

            $this->testCaseGroupCount = 1;

            //parse the success cases
            foreach ($successCases as $case) {
                $this->processTestCase($apiDoc, $case, $successReturnFields, 'OK');
            }

            //parse the error cases
            foreach ($errorCases as $case) {
                $this->processTestCase($apiDoc, $case, [], 'BAD_REQUEST');
            }
        }

        $this->doEcho('Finished generating Test Files from ApiDoc data');
    }

    protected function processTestCase($apiDocData, $inputData, $outputData, $responseCode)
    {
        $testCase = new ApiDocTesterTestCase();
        $testCase->inputData = $inputData;
        $testCase->outputData = $outputData;
        $testCase->responseCode = $responseCode;
        $testCase->group = $apiDocData['group'];
        $testCase->name = $apiDocData['name'];
        $testCase->url = $this->apiUrl.$apiDocData['url'];
        $testCase->type = strtoupper($apiDocData['type']);
        $testCase->title = $apiDocData['title'].' - '.$inputData['title'];
        $testCase->stubContent = $this->stubCodeceptionTest;
        $testCase->targetDirectory = $this->codeceptionTestDataFilesDirectory;
        $testCase->groupCount = $this->testCaseGroupCount;
        $testCase->caseCount = $this->testCaseCount;
        $testCase->generate();

        $this->testCaseGroupCount++;
        $this->testCaseCount++;
        return true;
    }
}
