<?php

namespace Rallo\ContaoTheme\Twig;

use Doctrine\DBAL\Connection;
use Rallo\ContaoTheme\RctBundle;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class RctTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly Connection $db) {}

    public function getGlobals(): array
    {
        $canvasEnabled = '1';
        $dotsEnabled   = '1';
        $auroraSpeed   = '1.0';

        try {
            $row = $this->db->fetchAssociative('SELECT * FROM tl_rct_config LIMIT 1');
            if ($row) {
                $canvasEnabled = $row['rct_canvas_enabled'] ?? '1';
                $dotsEnabled   = $row['rct_dots_enabled']   ?? '1';
                $auroraSpeed   = $row['rct_aurora_speed']   ?? '1.0';
            }
        } catch (\Throwable) {}

        return [
            'rct_version'        => RctBundle::VERSION,
            'rct_canvas_enabled' => $canvasEnabled,
            'rct_dots_enabled'   => $dotsEnabled,
            'rct_aurora_speed'   => $auroraSpeed !== '' ? $auroraSpeed : '1.0',
        ];
    }
}
