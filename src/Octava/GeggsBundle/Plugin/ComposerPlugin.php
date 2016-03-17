<?php
namespace Octava\GeggsBundle\Plugin;

use Octava\GeggsBundle\Helper\RepositoryList;

/**
 * Class ComposerPlugin
 * @package Octava\GeggsBundle\Plugin
 */
class ComposerPlugin extends AbstractPlugin
{

    /**
     * @param RepositoryList $repositories
     */
    public function execute(RepositoryList $repositories)
    {
        $composerFilename = $repositories->getProjectModel()->getAbsolutePath().DIRECTORY_SEPARATOR.'composer.json';
        $composerData = json_decode(file_get_contents($composerFilename), true);

        $updateFlag = false;
        foreach ($repositories->getVendorModels() as $model) {
            $packageName = $model->getPackageName();

            if (empty($composerData['require'][$packageName])) {
                continue;
            }

            $sourceVersion = $composerData['require'][$packageName];
            $versionChanged = (false !== strpos($sourceVersion, 'as'));
            $newVersion = 'dev-'.$model->getBranch().' as '.$sourceVersion;
            if (!$versionChanged &&
                ($model->hasChanges()
                    || 'master' != $model->getBranch())
            ) {
                $composerData['require'][$packageName] = $newVersion;

                $this->getLogger()->info(
                    'Change vendor newVersion',
                    [
                        'vendor' => $packageName,
                        'from_version' => $sourceVersion,
                        'to_version' => $newVersion,
                    ]
                );

                $updateFlag = true;
            } elseif ('master' == $repositories->getProjectModel()->getBranch()
                && $versionChanged
            ) {
                $ar = explode('as', $sourceVersion);
                $newVersion = trim($ar[1]);
                $composerData['require'][$packageName] = $newVersion;

                $this->getLogger()->info(
                    'Change vendor newVersion',
                    [
                        'vendor' => $packageName,
                        'from_version' => $sourceVersion,
                        'to_version' => $newVersion,
                    ]
                );
            } else {
                $this->getLogger()->debug('No changes', ['vendor' => $packageName]);
            }
        }

        if (!$this->isDryRun()) {
            $jsonEncodedData = json_encode(
                $composerData,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            file_put_contents($composerFilename, $jsonEncodedData);
        }

        if ($updateFlag) {
            $this->getSymfonyStyle()->success('File composer.json updated');
        }
    }
}
