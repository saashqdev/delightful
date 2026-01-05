<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Tools\PHPUnit;

use Composer\InstalledVersions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class AdapterCommand extends Command
{
    protected static $defaultName = 'run';

    protected Filesystem $filesystem;

    protected SymfonyStyle $io;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        foreach ($this->getAdapterConfig() as $adapterConfig) {
            if (! extension_loaded($adapterConfig['extension'])) {
                continue;
            }

            if ($this->packageNotInstalled($adapterConfig['package'])) {
                if (! $this->reinstall($adapterConfig['package'], $adapterConfig['operations'])) {
                    return self::FAILURE;
                }
            }

            $this->io->block('Executing command: ' . $adapterConfig['testCommand'], null, 'fg=green');
            passthru($adapterConfig['testCommand']);
            break;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<array{extension:string, package:string, operations:array<callable>, testCommand:string}>
     */
    protected function getAdapterConfig(): array
    {
        return [
            [
                'extension' => 'swoole',
                'package' => 'hyperf/engine',
                'operations' => [
                    fn () => $this->deleteComposerFiles(),
                    fn () => $this->composerInstall(),
                ],
                'testCommand' => 'php vendor/hyperf/testing/co-phpunit --prepend tests/bootstrap.php --configuration phpunit.xml --colors=always',
            ],
            [
                'extension' => 'swow',
                'package' => 'hyperf/engine-swow',
                'operations' => [
                    fn () => $this->deleteComposerFiles(),
                    fn ($package) => $this->composerSafeRequire($package),
                ],
                'testCommand' => 'php vendor/bin/phpunit --prepend tests/bootstrap.php --configuration phpunit.xml --colors=always',
            ],
        ];
    }

    private function packageNotInstalled(string $package): bool
    {
        return ! InstalledVersions::isInstalled($package) || ! InstalledVersions::getReference($package);
    }

    private function reinstall(string $package, array $steps): bool
    {
        $question = new ChoiceQuestion(
            'Detected that you have enabled Swoole/Swow extension, but the corresponding ' . $package . ' component is not installed. Do you want to regenerate the composer library?',
            ['N' => 'No', 'Y' => 'Yes'],
            'N'
        );
        $answer = $this->io->askQuestion($question);
        if ($answer == 'N') {
            return false;
        }

        foreach ($steps as $step) {
            if (! $step($package)) {
                return false;
            }
        }

        return true;
    }

    private function deleteComposerFiles(): bool
    {
        try {
            $removeFiles = [PHPUNIT_ADAPTOR_BASE_PATH . '/composer.lock', PHPUNIT_ADAPTOR_BASE_PATH . '/vendor'];
            $this->filesystem->remove($removeFiles);
            $this->io->block('Removed files and directories: ' . implode(',', $removeFiles), null, 'fg=green');
        } catch (IOException $e) {
            $this->io->error('Failed to remove files and directories: ' . implode(',', $removeFiles));
            throw $e;
        }

        return true;
    }

    private function composerSafeRequire($package): bool
    {
        $this->filesystem->copy(PHPUNIT_ADAPTOR_BASE_PATH . '/composer.json', PHPUNIT_ADAPTOR_BASE_PATH . '/composer.json.bak');
        $command = 'cd ' . PHPUNIT_ADAPTOR_BASE_PATH . ' && composer require ' . $package;
        $this->io->block('Executing command: ' . $command, null, 'fg=green');
        passthru($command, $resultCode);

        $this->filesystem->remove(PHPUNIT_ADAPTOR_BASE_PATH . '/composer.json');
        $this->filesystem->rename(PHPUNIT_ADAPTOR_BASE_PATH . '/composer.json.bak', PHPUNIT_ADAPTOR_BASE_PATH . '/composer.json');

        if ($resultCode !== 0) {
            $this->io->error('Command execution failed');
            return false;
        }

        return true;
    }

    private function composerInstall(): bool
    {
        $command = 'cd ' . PHPUNIT_ADAPTOR_BASE_PATH . ' && composer install';
        $this->io->block('Executing command: ' . $command, null, 'fg=green');
        passthru($command, $resultCode);
        if ($resultCode !== 0) {
            $this->io->error('Command execution failed');
            return false;
        }

        return true;
    }
}
