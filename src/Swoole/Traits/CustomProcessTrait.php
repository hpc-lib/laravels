<?php

namespace Hhxsv5\LaravelS\Swoole\Traits;

use Hhxsv5\LaravelS\Illuminate\Laravel;
use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;

trait CustomProcessTrait
{
    use ProcessTitleTrait;
    use LogTrait;

    public function addCustomProcesses(\swoole_server $swoole, $processPrefix, array $processes, array $laravelConfig)
    {
        Laravel::autoload($laravelConfig['root_path']);

        /**
         * @var []CustomProcessInterface $processList
         */
        $processList = [];
        foreach ($processes as $process) {
            if (!isset(class_implements($process)[CustomProcessInterface::class])) {
                throw new \Exception(sprintf(
                        '%s must implement the interface %s',
                        $process,
                        CustomProcessInterface::class
                    )
                );
            }

            $processHandler = function () use ($swoole, $processPrefix, $process, $laravelConfig) {
                $name = $process::getName() ?: 'custom';
                $this->setProcessTitle(sprintf('%s laravels: %s process', $processPrefix, $name));
                $this->initLaravel($laravelConfig, $swoole);
                while (true) {
                    $this->callWithCatchException(function () use ($process, $swoole) {
                        return $process::callback($swoole);
                    });
                    sleep(1);
                }
            };
            $customProcess = new \swoole_process(
                $processHandler,
                $process::isRedirectStdinStdout(),
                $process::getPipeType()
            );
            if ($swoole->addProcess($customProcess)) {
                $processList[] = $customProcess;
            }
        }
        return $processList;
    }

}