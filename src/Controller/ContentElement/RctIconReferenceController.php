<?php

namespace Rallo\ContaoTheme\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_icon_reference', category: 'rct', template: 'content_element/rct_icon_reference')]
class RctIconReferenceController extends AbstractContentElementController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir
    ) {}

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $publicDir = $this->projectDir . '/public/bundles/rct/icons/tabler';
        $bundleDir = \dirname(__DIR__, 2) . '/Resources/public/icons/tabler';
        $dir = is_dir($publicDir) ? $publicDir : $bundleDir;

        $icons = [];
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.svg') as $file) {
                $slug = pathinfo($file, PATHINFO_FILENAME);
                $icons[] = $slug;
            }
            sort($icons);
        }

        $template->icons  = $icons;
        $cssId            = \Contao\StringUtil::deserialize($model->cssID, true);
        $template->htmlId = trim($cssId[0] ?? '', '"\'');
        $template->cssClass = $cssId[1] ?? '';

        return $template->getResponse();
    }
}
