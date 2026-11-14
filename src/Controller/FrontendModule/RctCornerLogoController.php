<?php

namespace Rallo\ContaoTheme\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RCT Eck-Logo (Ralph 14.11.2026): Logo fest unten links/rechts im Bildschirm, ohne Hintergrund.
 * Ohne eigenes Bild = großes RCT-Logo (färbt sich mit dem Theme). Erst ab einer Mindest-Fensterbreite sichtbar,
 * damit es nie über dem Inhalt liegt.
 */
#[AsFrontendModule(type: 'rct_corner_logo', category: 'rct', template: 'frontend_module/rct_corner_logo')]
class RctCornerLogoController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $image = null;
        if ($model->rct_logo_image) {
            $file = FilesModel::findByUuid($model->rct_logo_image);
            if ($file !== null) {
                $image = '/' . $file->path;
            }
        }

        $url = trim((string) $model->rct_logo_url);
        if ($url !== '' && !preg_match('~^(https?://|/|#|mailto:)~i', $url)) {
            $url = 'https://' . $url;
        }

        $template->cornerId  = 'rct-corner-' . (int) $model->id;
        $template->corner    = $model->rct_corner_pos === 'right' ? 'right' : 'left';
        $template->width     = max(60, min(600, (int) ($model->rct_corner_width ?: 260)));
        $template->minWidth  = max(0, min(5000, (int) ($model->rct_corner_min ?: 1720)));
        $template->image     = $image;
        $template->url       = $url;
        $template->newTab    = (bool) $model->rct_corner_blank;
        $template->altText   = (string) ($model->rct_logo_alt ?: $model->name);

        return $template->getResponse();
    }
}
