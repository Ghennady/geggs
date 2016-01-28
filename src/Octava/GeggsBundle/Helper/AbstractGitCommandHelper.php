<?php
namespace Octava\GeggsBundle\Helper;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class AbstractGitCommandHelper
 * @package Octava\GeggsBundle\Helper
 */
abstract class AbstractGitCommandHelper extends ContainerAwareCommand
{
    /**
     * @param       $dir
     * @param array $arguments
     * @return string
     */
    protected function buildCommand($dir, array $arguments)
    {
        $bin = $config = $this->getContainer()->get('octava_geggs.config')->getGitBin();
        $builder = ProcessBuilder::create($arguments);
        $builder->setPrefix($bin);

        $cmd = $builder->getProcess()
            ->getCommandLine();
        $cmd = 'cd '.$dir.' && '.$cmd;

        return $cmd;
    }

    /**
     * @param $cmd
     * @param $io
     */
    protected function runCommand($cmd, SymfonyStyle $io)
    {
        $process = new Process($cmd);
        $process->setTty(true);
        try {
            $process->mustRun();

            $io->text($process->getOutput());
        } catch (ProcessFailedException $e) {
            $message = $e->getMessage();
            if (false !== strpos($message, 'nothing to commit')) {
                $io->error($e->getMessage());
            }
        }
    }
}