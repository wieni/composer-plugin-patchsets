<?php

namespace Wieni\ComposerPatchSet\Resolver;

use cweagans\Composer\Capability\Resolver\BaseResolverProvider;

class ResolverProvider extends BaseResolverProvider
{

    public function getResolvers(): array
    {
        return [
            new Resolver($this->composer, $this->io, $this->plugin),
        ];
    }

}