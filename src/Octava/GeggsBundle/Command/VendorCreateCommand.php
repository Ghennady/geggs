<?php
namespace Octava\GeggsBundle\Command;

use Octava\GeggsBundle\Generator\VendorGenerator;
use Octava\GeggsBundle\Model\Vendor;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class VendorCreateCommand
 * @package Octava\GeggsBundle\Command
 */
class VendorCreateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('vendor:create')
            ->addArgument('name', InputArgument::REQUIRED, 'Repository name for example: my_namespace/my_lib.git')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, 'The directory where to create the bundle', 'vendor')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Git url for example: https://github.com', '')
            ->setDescription('Create vendor');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface|Output $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');

        $dir = $input->getOption('dir');
        $filesystem = $this->getContainer()->get('filesystem');
        if (!$filesystem->isAbsolutePath($dir)) {
            $dir = getcwd().'/'.$dir;
        }

        $config = $this->getContainer()->get('octava_geggs.config');

        $repositoryUrl = $input->getOption('url');
        $vendor = new Vendor($dir, $name, $repositoryUrl);
        $relativeTargetDirectory = $config->makePathRelative($vendor->getTargetDirectory());
        $io->writeln(
            sprintf(
                '> Generating a sample vendor skeleton into <info>"%s"</info>',
                $relativeTargetDirectory
            )
        );
        $io->writeln(sprintf('> Repository url: %s', $repositoryUrl));

        $generator = new VendorGenerator($filesystem, $config);
        $generator->setSkeletonDirs(__DIR__.'/../Resources/skeleton');
        $generator->generate($vendor);

        $io->success('Everything is OK! Now get to work :).');
        $io->note(
            [
                'Remember you should push new vendor, before update composer.',
                'Use `geggs push` command',
                sprintf('Or push manually `cd "%s" && git push`', $relativeTargetDirectory),
            ]
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->writeln('Welcome to the vendor generator!');

        $repositoryUrl = $input->getOption('url');
        if (!$repositoryUrl) {
            $repositoryUrl = $this->getContainer()->get('octava_geggs.config')->getGeneratorRepositoryUrl();
        }
        if (!$repositoryUrl) {
            $repositoryUrl = $io->ask('Enter vendor repository url', 'git@github.com');
            $input->setOption('url', $repositoryUrl);
        }
    }
}