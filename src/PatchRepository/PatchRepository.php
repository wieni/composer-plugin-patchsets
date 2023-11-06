<?php

namespace Wieni\ComposerPatchSet\PatchRepository;

use cweagans\Composer\Patch;

class PatchRepository
{

    public function __construct(
        public readonly string $name,
        /** @var ExcludedPatch[] */
        public readonly array $excludedPatches = [],
    )
    {
    }

    public function isExcluded(Patch $patch): bool
    {
        foreach ($this->excludedPatches as $excludedPatch) {
            if ($excludedPatch->matches($patch)) {
                return true;
            }
        }

        return false;
    }

}