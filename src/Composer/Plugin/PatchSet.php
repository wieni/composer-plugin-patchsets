<?php

namespace Wieni\ComposerPatchSet\Composer\Plugin;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Capability\Resolver\ResolverProvider as ResolverProviderInterface;
use cweagans\Composer\Capability\Patcher\PatcherProvider as PatcherProviderInterface;
use cweagans\Composer\Plugin\Patches;
use Wieni\ComposerPatchSet\Patcher\PatcherProvider;
use Wieni\ComposerPatchSet\Resolver\ResolverProvider;

class PatchSet implements PluginInterface, EventSubscriberInterface, Capable
{

    public static function getSubscribedEvents(): array
    {
        return [
            // Make sure patch lock file is removed before the patches are
            // applied (which happens with priority 10).
            PackageEvents::POST_PACKAGE_INSTALL => ['unlockPatches', 20],
            PackageEvents::POST_PACKAGE_UPDATE => ['unlockPatches', 20],
        ];
    }

    public function getCapabilities(): array
    {
        return [
            ResolverProviderInterface::class => ResolverProvider::class,
            PatcherProviderInterface::class => PatcherProvider::class,
        ];
    }

    public function activate(Composer $composer, IOInterface $io)
    {
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public function unlockPatches(PackageEvent $event)
    {
        $operation = $event->getOperation();
        if (!($operation instanceof UpdateOperation)) {
            return;
        }

        $composer = $event->getComposer();
        $rootPackage = $composer->getPackage();
        $targetPackage = $operation->getTargetPackage();
        $isPatchRepositoryUpdate = FALSE;
        foreach ($rootPackage->getExtra()['patchRepositories'] ?? [] as $patchRepositoryJson) {
            $patchRepositoryName = is_string($patchRepositoryJson)
                ? $patchRepositoryJson
                : $patchRepositoryJson['name'];

            if ($targetPackage->getName() === $patchRepositoryName) {
                $isPatchRepositoryUpdate = TRUE;
                break;
            }
        }

        if (!$isPatchRepositoryUpdate) {
            return;
        }

        $targetPatches = $targetPackage->getExtra()['patches'] ?? [];
        $initialPatches = $operation->getInitialPackage()->getExtra()['patches'] ?? [];
        if ($initialPatches === $targetPatches) {
            return;
        }

        $lockFilePath = Patches::getPatchesLockFilePath();
        if (!is_file($lockFilePath)) {
            // If the patch files have not been locked, the patched packages have
            // not yet been installed, and, thus, do not need to be re-installed.
            return;
        }

        $io = $event->getIO();
        $io->write(sprintf(
            '    - <info>Removing patch lock file due to updated patch repository %s</info>',
            $patchRepositoryName,
        ));
        unlink($lockFilePath);

        $packagesToInstall = [];
        foreach ($targetPatches as $targetPatchedPackage => $targetPatches) {
            // Re-install packages that are newly patched or have a different
            // set of patches...
            if (!isset($initialPatches[$targetPatchedPackage]) || ($initialPatches[$targetPatchedPackage] !== $targetPatches)) {
                $packagesToInstall[] = $targetPatchedPackage;
            }
            unset($initialPatches[$targetPatchedPackage]);
        }
        // ...or that are no longer patched but previously were.
        $packagesToInstall = array_merge($packagesToInstall, array_keys($initialPatches));

        // In case any of the packages were updated or installed as part of this
        // batch, do not re-install them.
        foreach ($event->getOperations() as $previousOperation) {
            if ($previousOperation instanceof UpdateOperation) {
                $packagesToInstall = array_diff($packagesToInstall, [$previousOperation->getTargetPackage()->getName()]);
            }
            elseif ($previousOperation instanceof InstallOperation) {
                $packagesToInstall = array_diff($packagesToInstall, [$previousOperation->getPackage()->getName()]);
            }
        }

        $newOperations = [];
        foreach ($packagesToInstall as $packageToInstall) {
            $io->write(sprintf(
                '    - <info>Installing %s with updated patches</info>',
                $packageToInstall,
            ));
            $newOperations[] = new InstallOperation($event->getLocalRepo()->findPackage($packageToInstall, '*'));
        }

        $composer->getInstallationManager()->execute($event->getLocalRepo(), $newOperations);
    }

}
