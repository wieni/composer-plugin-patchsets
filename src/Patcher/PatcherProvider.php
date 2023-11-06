<?php

namespace Wieni\ComposerPatchSet\Patcher;

use cweagans\Composer\Capability\Patcher\BasePatcherProvider;

class PatcherProvider extends BasePatcherProvider
{

    public function getPatchers(): array
    {
        return [
            new DrupalDepthOnePatcher($this->composer, $this->io, $this->plugin),
        ];
    }

}