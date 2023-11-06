<?php

namespace Wieni\ComposerPatchSet\Resolver;

use Composer\Util\Platform;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Resolver\ResolverBase;
use Wieni\ComposerPatchSet\PatchRepository\Loader;

class Resolver extends ResolverBase
{

    private Loader $loader;

    public function getLoader(): Loader
    {
        return $this->loader ??= new Loader(
            Platform::getCwd(),
            $this->composer->getPackage(),
            $this->composer->getRepositoryManager()->getLocalRepository(),
            $this->composer->getInstallationManager(),
            $this,
            $this->io,
        );
    }

    public function setLoader(Loader $loader): void
    {
        $this->loader = $loader;
    }

    public function resolve(PatchCollection $collection): void
    {
        foreach ($this->getLoader()->findPatchRepositories() as $patchRepository) {
            $this->io->write(sprintf('  - <info>Resolving patches from %s.</info>', $patchRepository->name));
            foreach ($this->getLoader()->loadPatches($patchRepository) as $patch) {
                $this->io->write(sprintf(
                    '    - <info>Adding patch %s</info>',
                    $patch->description,
                ));
                $collection->addPatch($patch);
            }
        }
    }

}