<?php

namespace Rallo\ContaoTheme;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class RctBundle extends Bundle
{
    public const VERSION = '1.6.7';

    public function getPath(): string
    {
        return \dirname(__DIR__) . '/src';
    }
}
