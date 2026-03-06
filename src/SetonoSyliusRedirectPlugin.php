<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SetonoSyliusRedirectPlugin extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
