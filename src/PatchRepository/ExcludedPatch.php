<?php

namespace Wieni\ComposerPatchSet\PatchRepository;

use cweagans\Composer\Patch;

class ExcludedPatch
{

    public function __construct(
        public readonly string $package,
        public readonly string $url,
    )
    {
    }

    public function matches(Patch $patch): bool
    {
        return $patch->package === $this->package
            && $this->urlOrDescriptionMatches($patch);
    }

    public function urlOrDescriptionMatches(Patch $patch): bool
    {
        if ($this->url === '*') {
            return true;
        }

        return (
            $patch->url === $this->url
            || $patch->description === $this->url
        );
    }

}