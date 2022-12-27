<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class GitPull extends Command
{
    protected $signature = 'app:git_pull';
    protected $description = 'Pull files from GIT';
    private $alreadyUpToDate;

    private $pullLog = [];
    private $composerLog = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if(!$this->runPull()) {
            $this->error("An error occurred while executing 'git pull'. \nLogs:");
            foreach($this->pullLog as $logLine) {
                $this->info($logLine);
            }
            return;
        }

        if($this->alreadyUpToDate) {
            $this->info("The application is already up-to-date");
            return;
        }

        // if(!$this->runComposer()) {
        //     $this->error("Error while updating composer files. \nLogs:");
        //     foreach($this->composerLog as $logLine) {
        //         $this->info($logLine);
        //     }
        //     return;
        // }

        $this->info("Succesfully updated the application.");

    }

    private function runPull()
    {
        $process = new Process(['git','pull origin master']);
        $this->info("Running 'git pull'");

        $process->run(function($type, $buffer) {
            $this->pullLog[] = $buffer;

            if($buffer == "Already up to date.\n") {
                $this->alreadyUpToDate = TRUE;
            } 
        });

        return $process->isSuccessful();
    }

    // private function runComposer()
    // {
    //     $process = new Process('composer install');
    //     $this->info("Running 'composer install'");

    //     $process->run(function($type, $buffer) {
    //         $this->composerLog[] = $buffer;
    //     });

    //     return $process->isSuccessful();
    // }

}