<?php

namespace Rallo\ContaoTheme\Controller\Backend;

use Contao\CoreBundle\Controller\AbstractBackendController;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/contao/rct-config', name: 'rct_config', defaults: ['_scope' => 'backend', '_token_check' => true])]
class RctConfigController extends AbstractBackendController
{
    private const DEFAULTS = [
        'rct_font_body'          => 'Space Grotesk',
        'rct_font_mono'          => 'DM Mono',
        'rct_color_accent'       => '#27c4f4',
        'rct_color_primary'      => '#2951c7',
        'rct_color_primary_light'=> '#27c4f4',
        'rct_grad1'              => '#27c4f4',
        'rct_grad2'              => '#2951c7',
        'rct_grad3'              => '#1d2db2',
        'rct_grad4'              => '#14054a',
        'rct_sidebar_width'      => '260px',
        'rct_header_height'      => '64px',
        'rct_radius'             => '0.125rem',
    ];

    public function __construct(private readonly Connection $db) {}

    public function __invoke(Request $request): Response
    {
        $saved = false;

        if ($request->isMethod('POST')) {
            $this->saveConfig($request);
            $saved = true;
        }

        $config = $this->loadConfig();
        $fonts  = $this->scanFonts($request);

        return $this->render('@Rct/backend/rct_config.html.twig', [
            'headline' => 'RCT Theme-Einstellungen',
            'config'   => $config,
            'fonts'    => $fonts,
            'saved'    => $saved,
            'defaults' => self::DEFAULTS,
        ]);
    }

    private function loadConfig(): array
    {
        $row = $this->db->fetchAssociative("SELECT * FROM tl_rct_config LIMIT 1");
        if (!$row) {
            return self::DEFAULTS;
        }
        return array_merge(self::DEFAULTS, array_filter($row, fn($v) => $v !== null && $v !== ''));
    }

    private function saveConfig(Request $request): void
    {
        $fields = array_keys(self::DEFAULTS);
        $data   = ['tstamp' => time()];

        foreach ($fields as $field) {
            $val = $request->request->get($field, '');
            // Validate hex colors
            if (str_starts_with($field, 'rct_color_') || str_starts_with($field, 'rct_grad')) {
                $val = preg_match('/^#[0-9a-fA-F]{6}$/', $val) ? $val : self::DEFAULTS[$field];
            }
            // Validate dimension fields
            if (in_array($field, ['rct_sidebar_width', 'rct_header_height', 'rct_radius'])) {
                $val = preg_match('/^[\d.]+\s*(px|rem|em|%)$/', trim($val)) ? trim($val) : self::DEFAULTS[$field];
            }
            $data[$field] = $val;
        }

        $exists = $this->db->fetchOne("SELECT id FROM tl_rct_config LIMIT 1");
        if ($exists) {
            $this->db->update('tl_rct_config', $data, ['id' => $exists]);
        } else {
            $this->db->insert('tl_rct_config', $data);
        }
    }

    private function scanFonts(Request $request): array
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $fontsDir   = $projectDir . '/public/bundles/rct/fonts';

        // Fallback to bundle source during development
        if (!is_dir($fontsDir)) {
            $fontsDir = \dirname(__DIR__, 3) . '/Resources/public/fonts';
        }

        $families = [];
        if (is_dir($fontsDir)) {
            foreach (glob($fontsDir . '/*.{woff2,woff,ttf,otf}', GLOB_BRACE) as $file) {
                $family = $this->extractFamilyName(basename($file));
                if ($family) {
                    $families[$family][] = basename($file);
                }
            }
        }

        ksort($families);
        return $families;
    }

    private function extractFamilyName(string $filename): string
    {
        $name = preg_replace('/\.(woff2?|ttf|otf)$/i', '', $filename);
        // Strip version markers like -v22-, -v16-
        $name = preg_replace('/-v\d+.*$/', '', $name);
        // Strip weight/style suffixes
        $name = preg_replace('/[-_](regular|bold|italic|light|medium|semibold|extrabold|black|\d{3})([-_].*)?$/i', '', $name);
        // Strip locale markers like -latin
        $name = preg_replace('/[-_]latin$/i', '', $name);

        return ucwords(str_replace(['-', '_'], ' ', $name));
    }
}
