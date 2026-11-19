<?php

namespace Rallo\ContaoTheme\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsFrontendModule(type: 'rct_language_switcher', category: 'rct', template: 'frontend_module/rct_language_switcher')]
class RctLanguageSwitcherController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $languages = [];
        foreach (array_filter(array_map('trim', explode("\n", (string) $model->rct_languages))) as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 3) {
                $languages[] = ['code' => strtoupper($parts[0]), 'label' => $parts[1], 'url' => $parts[2]];
            }
        }
        $languages = $this->linkAssociated($languages, $request);

        $template->languages      = $languages ?: [];
        $template->rct_visibility = (string) $model->rct_visibility;

        return $template->getResponse();
    }

    /**
     * Mit terminal42/contao-changelanguage (optional, Ralph 19.11.2026): jede Sprache verlinkt die
     * zugeordnete Seite (Seiten-Feld „Hauptsprache-Seite“) statt der festen URL, und der Kopf bekommt
     * hreflang-Links. Ohne Erweiterung oder ohne Zuordnung bleibt die feste URL aus den Zeilen.
     */
    private function linkAssociated(array $languages, Request $request): array
    {
        $page = $request->attributes->get('pageModel');
        $page = $page instanceof PageModel ? $page : ($GLOBALS['objPage'] ?? null);
        if (!$languages || !$page instanceof PageModel || !class_exists(\Terminal42\ChangeLanguage\PageFinder::class)) {
            return $languages;
        }

        try {
            $generator = System::getContainer()->get('contao.routing.content_url_generator');
            $found = [];
            foreach ((new \Terminal42\ChangeLanguage\PageFinder())->findAssociatedForPage($page) as $p) {
                $p->loadDetails();
                $found[strtolower(substr((string) $p->language, 0, 2))] = $generator->generate($p, [], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        } catch (\Throwable) {
            return $languages;   // im Zweifel feste URLs statt Seitenfehler
        }

        if (\count($found) < 2) {
            return $languages;   // Seite hat (noch) keine Gegenstücke → wie bisher
        }

        foreach ($languages as &$lang) {
            $code = strtolower($lang['code']);
            if (isset($found[$code])) {
                $lang['url'] = $found[$code];
            }
        }
        unset($lang);

        $head = '';
        foreach ($found as $code => $url) {
            $head .= '<link rel="alternate" hreflang="' . $code . '" href="' . htmlspecialchars($url) . '">' . "\n";
        }
        $default = $found[strtolower($languages[0]['code'])] ?? reset($found);   // erste Zeile = x-default
        $head .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($default) . '">';
        $GLOBALS['TL_HEAD']['rct_hreflang'] = $head;

        return $languages;
    }
}
