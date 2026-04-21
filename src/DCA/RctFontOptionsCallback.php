<?php

namespace Rallo\ContaoTheme\DCA;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RctFontOptionsCallback
{
    public function __construct(private readonly ParameterBagInterface $params) {}

    public function getHeadlineFonts(): array
    {
        $projectDir = (string) $this->params->get('kernel.project_dir');

        $dirs = [
            $projectDir . '/public/bundles/rct/fonts',
            \dirname(__DIR__) . '/Resources/public/fonts',
            $projectDir . '/files/rct-fonts',
        ];

        $families = [];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*.{woff2,woff,ttf,otf}', GLOB_BRACE) as $file) {
                $family = $this->extractFamily(basename($file));
                if ($family) {
                    $families[$family] = $family;
                }
            }
        }

        ksort($families);

        return ['' => '— Standard (globaler Font) —'] + $families;
    }

    private function extractFamily(string $filename): string
    {
        $name = preg_replace('/\.(woff2?|ttf|otf)$/i', '', $filename);
        $name = preg_replace('/-v\d+.*$/', '', $name);
        $name = preg_replace('/[-_](regular|bold|italic|light|medium|semibold|extrabold|black|\d{3})([-_].*)?$/i', '', $name);
        $name = preg_replace('/[-_]latin$/i', '', $name);

        return ucwords(str_replace(['-', '_'], ' ', $name));
    }
}
