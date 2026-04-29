<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin;

use Setono\SyliusRedirectPlugin\DependencyInjection\SetonoSyliusRedirectExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SetonoSyliusRedirectPlugin extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return $this->extension ??= new SetonoSyliusRedirectExtension();
    }
}
