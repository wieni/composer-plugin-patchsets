<?php

namespace Wieni\ComposerPatchSet\Composer\Plugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Capability\Resolver\ResolverProvider as ResolverProviderInterface;
use cweagans\Composer\Capability\Patcher\PatcherProvider as PatcherProviderInterface;
use Wieni\ComposerPatchSet\Patcher\PatcherProvider;
use Wieni\ComposerPatchSet\Resolver\ResolverProvider;

class PatchSet implements PluginInterface, Capable
{

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

}