<?php
namespace Octava\GeggsBundle\Plugin;

use Octava\GeggsBundle\Helper\CommentHelper;
use Octava\GeggsBundle\Helper\RepositoryList;

/**
 * Class CommitProjectPlugin
 * @package Octava\GeggsBundle\Plugin
 */
class CommitProjectPlugin extends AbstractPlugin
{
    /**
     * @param RepositoryList $repositories
     * @return string
     */
    public function execute(RepositoryList $repositories)
    {
        $this->getLogger()->debug('Run plugin', [get_called_class()]);

        $comment = $this->getInput()->getOption('message');
        if (empty($comment)) {
            $comment = trim(
                $this->getSymfonyStyle()->ask(
                    'Enter comment, please',
                    null,
                    function ($answer) {
                        $answer = trim($answer);
                        if (empty($answer)) {
                            throw new \RuntimeException('Empty comment');
                        }

                        return $answer;
                    }
                )
            );
        }


        $model = $repositories->getProjectModel();
        if ($model->hasChanges()) {
            $model->getProvider()->run('add', ['.'], $this->isDryRun());
            $params = [];
            if ($this->getInput()->hasOption('no-verify') && $this->getInput()->getOption('no-verify')) {
                $params[] = '--no-verify';
            }
            $params[] = '-m';
            $params[] = CommentHelper::buildComment($comment, $model->getBranch());
            $model->getProvider()->run('commit', $params, $this->isDryRun(), true);
        } else {
            $this->getLogger()->debug('Changes not found', ['commit']);
        }

        $this->getLogger()->debug('End plugin', [get_called_class()]);
    }
}
