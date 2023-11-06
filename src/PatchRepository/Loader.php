<?php

namespace Wieni\ComposerPatchSet\PatchRepository;

use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use cweagans\Composer\Patch;
use cweagans\Composer\Resolver\ResolverBase;

class Loader
{

    public function __construct(
        private readonly string $projectRootDirectory,
        private readonly RootPackageInterface $rootPackage,
        private readonly InstalledRepositoryInterface $installedRepository,
        private readonly InstallationManager $installationManager,
        private readonly ResolverBase $resolver,
        private readonly IOInterface $io,
    )
    {
    }

    public function loadPatches(PatchRepository $patchRepository): array
    {
        $package = $this->installedRepository->findPackage($patchRepository->name, '*');
        if (!$package) {
            throw new \Exception(sprintf('Package %s not found', $patchRepository->name));
        }

        $patches = [];
        foreach ($this->getPatchesFromPatchRepository($package) as $patch) {
            if ($patchRepository->isExcluded($patch)) {
                $this->io->write(sprintf(
                    '    - <info>Excluding patch %s: %s</info>',
                    $patch->package,
                    $patch->description,
                ));
                continue;
            }

            $patches[] = $patch;
        }

        return $patches;
    }

    public function findPatchRepositories(): array
    {
        $patchRepositories = [];
        foreach ($this->rootPackage->getExtra()['patchRepositories'] ?? [] as $patchRepositoryJson) {
            $patchRepositories[] = $this->createPatchRepositoryFromJson($patchRepositoryJson);
        }

        return $patchRepositories;
    }

    private function createPatchRepositoryFromJson(string|array $patchRepositoryJson): PatchRepository
    {
        if (is_string($patchRepositoryJson)) {
            $patchRepositoryJson = [
                'name' => $patchRepositoryJson,
                'excludedPatches' => [],
            ];
        }

        return new PatchRepository(
            $patchRepositoryJson['name'],
            $this->createExcludedPatchesFromJson($patchRepositoryJson),
        );
    }

    private function createExcludedPatchesFromJson(array $patchRepositoryJson): array
    {
        $excludedPatches = [];
        foreach ($patchRepositoryJson['excludedPatches'] ?? [] as $package => $urls) {
            foreach ($urls as $url) {
                $excludedPatches[] = new ExcludedPatch($package, $url);
            }
        }

        return $excludedPatches;
    }

    /** @return Patch[] */
    private function getPatchesFromPatchRepository(PackageInterface $package): array
    {
        $packagePatches = $this->resolver->findPatchesInJson($package->getExtra()['patches'] ?? []);
        
        $allPatches = [];
        foreach ($packagePatches as $patches) {
            foreach ($patches as $patch) {
                $allPatches[] = $patch;
            }
        }

        // In case Patch url is a file path, we need to resolve it to a path
        // relative to the root composer.json.
        foreach ($allPatches as $patch) {
            if (!str_starts_with($patch->url, 'http')) {
                // /home/user/project/vendor/your-org/drupal-patches/patches/core/case_insensitive_langcode.patch
                $newUrl = $this->installationManager->getInstaller($package->getType())->getInstallPath($package) . '/' . trim($patch->url, '/');
                // /vendor/your-org/drupal-patches/patches/core/case_insensitive_langcode.patch
                $newUrl = str_replace(trim($this->projectRootDirectory, '/') . '/', '', $newUrl);
                // vendor/your-org/drupal-patches/patches/core/case_insensitive_langcode.patch
                $newUrl = ltrim($newUrl, '/');

                $this->io->write(sprintf(
                    '    - <info>Renaming patch path %s to %s</info>',
                    $patch->url,
                    $newUrl,
                ));
                $patch->url = $newUrl;
            }
        }

        // sort the patches by package name.
        usort($allPatches, function (Patch $a, Patch $b) {
            return strcmp($a->package, $b->package);
        });
        return $allPatches;
    }

}