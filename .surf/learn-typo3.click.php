<?php

use TYPO3\Surf\Application\TYPO3\CMS;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;
use TYPO3\Surf\Task\ShellTask;
use TYPO3\Surf\Task\Php\WebOpcacheResetCreateScriptTask;
use TYPO3\Surf\Task\Php\WebOpcacheResetExecuteTask;
use TYPO3\Surf\Task\TYPO3\CMS\CopyConfigurationTask;
use TYPO3\Surf\Task\TYPO3\CMS\CreatePackageStatesTask;
use TYPO3\Surf\Task\TYPO3\CMS\FlushCachesTask;
use TYPO3\Surf\Task\TYPO3\CMS\RunCommandTask;

$node = new Node('ec2-3-67-33-241.eu-central-1.compute.amazonaws.com');
$node
    ->setHostname($node->getName())
    ->setOptions(array_merge($node->getOptions(), [
        'username' => 'sergio',
        'phpBinaryPathAndFilename' => '/usr/bin/php'
    ]));

$application = new CMS('current');
$application
    ->setContext('Development/Develop')
    ->setDeploymentPath('/var/www/html/learn-typo3.click')
    ->setOptions(array_merge($application->getOptions(), [
        'baseUrl' => 'https://learn-typo3.click/',
        'branch' => 'develop',
        'composerCommandPath' => 'composer',
        'keepReleases' => '1',
        'repositoryUrl' => 'git@gitlab.com:instruccionesaldorso/learn-typo3.click.git',
        'symlinkDataFolders' => [],
        'webDirectory' => 'public',
        'rsyncExcludes' => array_merge($application->getOption('rsyncExcludes'), [
            '/.surf',
            '/.gitignore',
            '/.gitlab-ci.yml',
            '/composer.*',
            '/public/fileadmin',
        ]),
        'symlinkDataFolders' => ['fileadmin'],
    ]))
    ->setOption(FlushCachesTask::class . '[arguments]', [])
    ->addSymlinks([
        'Configuration' => '../../shared/Configuration/Configuration',
        '.env' => '../../shared/Configuration/.env'
    ])
    ->addNode($node);

$workflow = new SimpleWorkflow();
$workflow
    ->setEnableRollback(false)
    ->setTaskOptions(WebOpcacheResetExecuteTask::class, [
        'throwErrorOnWebOpCacheResetExecuteTask' => true
    ])
    ->defineTask('SetOwnershipAndPermissions',
        ShellTask::class,
        [
            'command' => 'chown www-data:www-data {releasePath} -R && cd {releasePath} && find . -type d -exec chmod 2775 {} + && find . -type f -exec chmod 0664 {} + && chmod 0774 vendor/helhum/typo3-console/typo3cms && chmod 0774 vendor/typo3/cms-cli/typo3'
        ]
    );

/** @var \TYPO3\Surf\Domain\Model\Deployment $deployment */
$deployment
    ->addApplication($application)
    ->setWorkflow($workflow)
    ->onInitialize(
        function () use ($workflow, $application) {
            $workflow
                ->beforeStage('transfer', WebOpcacheResetCreateScriptTask::class, $application)
                ->afterStage('finalize', 'SetOwnershipAndPermissions', $application)
                ->afterStage('switch', WebOpcacheResetExecuteTask::class, $application)
                ->removeTask(CreatePackageStatesTask::class, $application)
                ->removeTask(CopyConfigurationTask::class, $application);
        }
    );
