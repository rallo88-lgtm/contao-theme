<?php

namespace Rallo\ContaoTheme;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class RctBundle extends Bundle
{
    public const VERSION = '1.2.1';

    public function getPath(): string
    {
        return \dirname(__DIR__) . '/src';
    }
}
