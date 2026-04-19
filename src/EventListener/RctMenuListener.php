<?php

namespace Rallo\ContaoTheme\EventListener;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsEventListener(ContaoCoreEvents::BACKEND_MENU_BUILD, priority: -255)]
class RctMenuListener
{
    public function __construct(private readonly RequestStack $requestStack) {}

    public function __invoke(MenuEvent $event): void
    {
        $tree = $event->getTree();
        if ('mainMenu' !== $tree->getName()) {
            return;
        }

        $factory  = $event->getFactory();
        $request  = $this->requestStack->getCurrentRequest();
        $current  = $request?->attributes->get('_route');

        $category = $factory->createItem('rct_theme')
            ->setLabel('RCT Theme')
            ->setAttribute('class', 'navigation rct-theme-area');

        $category->addChild('rct_config', [
            'route'  => 'rct_config',
            'label'  => 'Theme-Einstellungen',
            'extras' => ['icon' => 'bundles/contaocore/images/settings.svg', 'isSafe' => true],
        ])
        ->setLinkAttribute('class', 'navigation rct_config')
        ->setCurrent($current === 'rct_config');

        $tree->addChild($category);
    }
}
