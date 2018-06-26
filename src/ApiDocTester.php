<?php

namespace YLSalame\LaravelApiDocTester;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use YLSalame\LaravelSeedGenerator\Exceptions\NotificationException;
use YLSalame\LaravelSeedGenerator\Exceptions\FatalException;

class ApiDocTester extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidoctester
                            {--app_dir= : directory where API classes/methods are kept}
                            {--output_dir= : directory where APIDoc will store its files temporarily}
                            {--keep_files= : defines if APIDoc files will be deleted at the end of execution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use APIDoc blocks to test your APIs';

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
            $this->fetchOptions();

            $this->generateApiDoc();

$process = new Process('ls -lsa');
$process->start();
$iterator = $process->getIterator($process::ITER_SKIP_ERR | $process::ITER_KEEP_OUTPUT);
foreach ($iterator as $data) {
    echo $data."\n";
}
            
            $this->generateUnitTestFiles();
            $this->executeTests();

die();

            $this->showInterfaceBlock(1);
            $this->showInterfaceBlock(2);

            $this->doEcho();
            $this->doEcho('APIDoc Tester routine finished');
            $this->doEcho();
        } catch (FatalException $e) {
            $this->doEcho('Fatal Exception happened - '.$e->getMessage());
            die();
        }
    }

    private function showInterfaceBlock(int $interfaceBlock): void
    {
        switch ($interfaceBlock) {
            case 1:
                $this->doEcho();
                $this->doEcho(str_repeat('=', 60));
                $this->doEcho('APIDoc Tester');
                $this->doEcho(str_repeat('=', 60));

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
            case 2:
                $this->doEcho('Tables to be scanned and have their seeder classes generated'.chr(10));

                foreach ($this->tables as $table) {
                    $this->doEcho(chr(9).$table);
                }

                if (!empty($this->skippedTables)) {
                    $this->doEcho();
                    $this->doEcho('Tables skipped'.chr(10));
                    foreach ($this->skippedTables as $table) {
                        $this->doEcho(chr(9).$table);
                    }
                }

                $this->doEcho();
                $this->doEcho(chr(9).count($this->tables).' seeder files will be generated.');
                $this->doEcho(chr(9).'Database seeder file will also be generated');
                $this->doEcho();

                if ($this->optionDontOverwrite) {
                    $this->doEcho('Existing files WILL NOT be overwritten!');
                } else {
                    $this->doEcho('Existing files WILL be overwritten!');
                }

                if (!$this->confirm(chr(9).'Confirm?')) {
                    throw new FatalException('SeedGenerator aborted');
                } else {
                    if (!$this->optionDontOverwrite) {
                        if (!$this->confirm(chr(9).'Existing files WILL BE OVERWRITTEN! Re-Confirm?')) {
                            throw new FatalException('SeedGenerator aborted');
                        }
                    }
                }
                break;
        }
    }
    
    private function fetchOptions()
    {
        $this->optionApiDir = $this->option('api_dir');
        $this->optionOutputDir = $this->option('output_dir');
        $this->optionKeepFiles = $this->option('keep_files');
    }

    private function raiseError(String $msg)
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

    private function doEcho(String $msg = '')
    {
        $this->line(chr(9).$msg);
    }

    private function createUnitTestFiles()
    {
        foreach ($this->getJsonData() as $request) {

            if($this->validateJSONObject($request)) {

                $numOfTests = isset($request['parameter']['examples'])? sizeof($request['parameter']['examples']): 1;

                for($i = 0; $i < $numOfTests; $i++) {
                    // create a test per param
                    $testFile = $request['name'] . 'Cept.php';
                    if( $numOfTests > 1) {
                        $testFile = $request['name'] . ($i+1) . 'Cept.php';
                    }
                    else {
                        $testFile = $request['name'] . 'Cept.php';
                    }

                    $testGroup = $request['group'];
                    $dirPath = $this->pathToAPIfolder . $testGroup;
                    $this->ensureDir($dirPath);

                    $handle = fopen($dirPath . DIRECTORY_SEPARATOR . $testFile, 'w');

                    try {
                        $data = $this->generateTestScript($request, $i);
                    } catch (\Exception $e) {
                        print "\n\033[41mERROR! LOOK HERE DEVELOPER!: Failed to generate test script '" . $test_file . "'. Error: " . $e->getMessage() . " \033[0m\n\n";
                        fclose($handle);
                        unlink($this->pathToAPIfolder . $test_file); // delete file
                        continue;
                    }

                    fwrite($handle, $data);

                    $this->count++;
                    fclose($handle);
                }
            }
        }
    }
}
