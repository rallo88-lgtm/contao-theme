<?php

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_alert', category: 'rct')]
class RctAlertController extends AbstractContentElementController
{
    private const TYPE_CONFIG = [
        'info'    => ['label' => 'INFO',  'color' => 'var(--rct-accent, #27c4f4)'],
        'warning' => ['label' => 'WARN',  'color' => '#f59e0b'],
        'success' => ['label' => 'OK',    'color' => 'var(--rct-primary-dim, #84cc16)'],
        'error'   => ['label' => 'ERR',   'color' => '#ef4444'],
    ];

    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $type   = $model->rct_alert_type ?: 'info';
        $config = self::TYPE_CONFIG[$type] ?? self::TYPE_CONFIG['info'];

        $template->alertType   = $type;
        $template->typeLabel   = $config['label'];
        $template->colorCss    = $config['color'];
        $template->alertTitle  = htmlspecialchars((string) $model->rct_alert_title, ENT_QUOTES, 'UTF-8');
        $template->alertText   = $model->rct_alert_text
            ? nl2br(htmlspecialchars((string) $model->rct_alert_text, ENT_QUOTES, 'UTF-8'))
            : '';
        $template->dismissible  = (bool) $model->rct_alert_dismissible;
        $template->alertStyle   = $model->rct_alert_style ?: 'dark';
        $cssId                  = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId       = trim($cssId[0] ?? '', '"\'');
        $template->cssClass     = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
