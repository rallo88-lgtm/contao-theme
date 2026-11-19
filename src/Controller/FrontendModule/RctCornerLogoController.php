<?php

namespace Rallo\ContaoTheme\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\ModuleModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RCT Eck-Logo (Ralph 14.11.2026): Logo fest unten links/rechts im Bildschirm, ohne Hintergrund.
 * Ohne eigenes Bild = großes RCT-Logo (färbt sich mit dem Theme). Erst ab einer Mindest-Fensterbreite sichtbar,
 * damit es nie über dem Inhalt liegt.
 * Optional „Auswahl“: statt eines Links öffnet das Logo ein Popover (HTML popover, ohne JS) mit mehreren Zielen.
 */
#[AsFrontendModule(type: 'rct_corner_logo', category: 'rct', template: 'frontend_module/rct_corner_logo')]
class RctCornerLogoController extends AbstractFrontendModuleController
{
    private const RCT_LOGO_RATIO = 418.7 / 802.7;   // Höhe/Breite des großen RCT-Logos (viewBox)

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $image = null;
        $ratio = self::RCT_LOGO_RATIO;
        if ($model->rct_logo_image) {
            $file = FilesModel::findByUuid($model->rct_logo_image);
            if ($file !== null) {
                $image = '/' . $file->path;
                $ratio = $this->ratio($file->path) ?? 0.65;
            }
        }

        $width = max(60, min(600, (int) ($model->rct_corner_width ?: 260)));

        $template->cornerId  = 'rct-corner-' . (int) $model->id;
        $template->corner    = $model->rct_corner_pos === 'right' ? 'right' : 'left';
        $template->width     = $width;
        $template->height    = (int) round($width * $ratio);
        $template->minWidth  = max(0, min(5000, (int) ($model->rct_corner_min ?: 1720)));
        $template->image     = $image;
        $template->url       = $this->url((string) $model->rct_logo_url);
        $template->newTab    = (bool) $model->rct_corner_blank;
        $template->altText   = $this->tags((string) ($model->rct_logo_alt ?: $model->name));
        $template->menuTitle = trim($this->tags((string) $model->rct_corner_menu_title));
        $template->menu      = $this->menu($this->tags((string) $model->rct_corner_menu));

        return $template->getResponse();
    }

    /** Inserttags auflösen (z. B. {{iflng::en}}…{{iflng}} für zweisprachige Seiten mit einem Modul) */
    private function tags(string $text): string
    {
        return str_contains($text, '{{') ? System::getContainer()->get('contao.insert_tag.parser')->replaceInline($text) : $text;
    }

    /** Eine Zeile pro Ziel: Titel | Beschreibung | URL  (Beschreibung optional) */
    private function menu(string $raw): array
    {
        $items = [];
        foreach (preg_split('/\R/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (\count($parts) < 2) {
                continue;
            }
            $url = $this->url((string) array_pop($parts));
            if ($url === '' || $parts[0] === '') {
                continue;
            }
            $host = parse_url($url, PHP_URL_HOST);
            $items[] = [
                'title' => $parts[0],
                'text'  => $parts[1] ?? '',
                'url'   => $url,
                'host'  => \is_string($host) ? preg_replace('/^www\./', '', $host) : '',
            ];
        }

        return $items;
    }

    private function url(string $url): string
    {
        $url = trim($url);
        if ($url !== '' && !preg_match('~^(https?://|/|#|mailto:)~i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    /** Höhe/Breite eines Bildes (Raster über getimagesize, SVG über viewBox/width/height) – für die Popover-Position */
    private function ratio(string $path): ?float
    {
        $abs = System::getContainer()->getParameter('kernel.project_dir') . '/' . $path;
        if (!is_file($abs)) {
            return null;
        }
        if (strtolower(pathinfo($abs, PATHINFO_EXTENSION)) === 'svg') {
            $head = (string) file_get_contents($abs, false, null, 0, 4096);
            if (preg_match('/viewBox\s*=\s*["\']\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)/i', $head, $m) && (float) $m[1] > 0) {
                return (float) $m[2] / (float) $m[1];
            }

            return null;
        }
        $size = @getimagesize($abs);

        return $size && $size[0] > 0 ? $size[1] / $size[0] : null;
    }
}
