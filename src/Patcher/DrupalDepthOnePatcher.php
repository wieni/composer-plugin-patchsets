<?php

namespace Wieni\ComposerPatchSet\Patcher;

use cweagans\Composer\Patch;
use cweagans\Composer\Patcher\GitInitPatcher;

class DrupalDepthOnePatcher extends GitInitPatcher
{
    public function apply(Patch $patch, string $path): bool
    {
        if (!$this->supports($patch)) {
            return false;
        }

        $originalDepth = $patch->depth;
        $patch->depth = 1;

        // Use the git init patcher to apply the patch
        $status = parent::apply($patch, $path);

        // Reset the patch depth.
        $patch->depth = $originalDepth;

        return $status;
    }

    private function supports(Patch $patch): bool
    {
        // If the patch depth is already 1, we don't need to do anything.
        if ($patch->depth === 1) {
            return false;
        }

        // We only want to apply this patch if it's for a Drupal package.
        return str_starts_with($patch->package, 'drupal/');
    }
}
