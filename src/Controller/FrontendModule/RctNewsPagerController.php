<?php

namespace Rallo\ContaoTheme\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Input;
use Contao\ModuleModel;
use Contao\News;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: 'rct_news_pager', category: 'rct', template: 'frontend_module/rct_news_pager')]
class RctNewsPagerController extends AbstractFrontendModuleController
{
    public function __construct(private readonly Connection $db) {}

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        // Aktuelle News-Alias aus URL — versuche items, auto_item, news (verschiedene Contao-Setups)
        $alias = (string) (Input::get('items') ?: Input::get('auto_item') ?: Input::get('news'));
        if ($alias === '') {
            return new Response('');
        }

        // Alle News-Archive-IDs sammeln — findPublishedByParentAndIdOrAlias braucht Array, nicht null
        $allArchives = NewsArchiveModel::findAll();
        $archiveIds  = $allArchives ? $allArchives->fetchEach('id') : [];
        if (empty($archiveIds)) {
            return new Response('');
        }

        $current = NewsModel::findPublishedByParentAndIdOrAlias($alias, $archiveIds);
        if ($current === null) {
            return new Response('');
        }

        // Wenn mehrere Treffer (selten — Alias-Kollision über Archive hinweg): erste nehmen.
        $currentNews = $current->current();
        $pid         = (int) $currentNews->pid;
        $currentTime = (int) $currentNews->time;

        $sortOrder = $model->rct_pager_sort_order === 'desc' ? 'desc' : 'asc';
        $loop      = (bool) $model->rct_pager_loop;

        // Vorgänger/Nachfolger im selben Archive — Richtung hängt vom Sort ab
        if ($sortOrder === 'asc') {
            // Aufsteigend: prev = niedrigere time, next = höhere time
            $prevNews = $this->findSibling($pid, $currentTime, '<', 'DESC');
            $nextNews = $this->findSibling($pid, $currentTime, '>', 'ASC');
        } else {
            // Absteigend (Blog): prev = jüngere time (höher), next = ältere (niedriger)
            $prevNews = $this->findSibling($pid, $currentTime, '>', 'ASC');
            $nextNews = $this->findSibling($pid, $currentTime, '<', 'DESC');
        }

        // Loop-Logik: bei Erreichen des Endes auf den Anfang springen
        if ($loop) {
            if ($prevNews === null) {
                $prevNews = $this->findEdge($pid, $sortOrder === 'asc' ? 'DESC' : 'ASC');
            }
            if ($nextNews === null) {
                $nextNews = $this->findEdge($pid, $sortOrder === 'asc' ? 'ASC' : 'DESC');
            }
        }

        // Position (1-basiert) + Total
        $direction = $sortOrder === 'asc' ? '<=' : '>=';
        $position  = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_news WHERE pid = ? AND published = '1' AND time {$direction} ?",
            [$pid, $currentTime]
        );
        $total = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM tl_news WHERE pid = ? AND published = '1'",
            [$pid]
        );

        // Cover = erste News im Archive in Sortier-Richtung
        $cover = null;
        if ($model->rct_pager_show_cover_link) {
            $cover = $this->findEdge($pid, $sortOrder === 'asc' ? 'ASC' : 'DESC');
            // Wenn Cover = current, nicht anzeigen (redundant)
            if ($cover !== null && (int) $cover->id === (int) $currentNews->id) {
                $cover = null;
            }
        }

        $template->prev          = $prevNews ? $this->buildItem($prevNews) : null;
        $template->next          = $nextNews ? $this->buildItem($nextNews) : null;
        $template->cover         = $cover    ? $this->buildItem($cover)    : null;
        $template->position      = $position;
        $template->total         = $total;
        $template->style         = in_array($model->rct_pager_style, ['arrows', 'arrows-counter', 'arrows-labels'], true)
            ? $model->rct_pager_style
            : 'arrows-counter';
        $template->positionClass = in_array($model->rct_pager_position, ['top', 'bottom', 'both'], true)
            ? $model->rct_pager_position
            : 'bottom';
        $template->keyboard      = (bool) $model->rct_pager_keyboard;
        $template->swipe         = (bool) $model->rct_pager_swipe;
        $template->rctVisibility = (string) $model->rct_visibility;

        return $template->getResponse();
    }

    private function findSibling(int $pid, int $time, string $op, string $orderDir): ?NewsModel
    {
        return NewsModel::findOneBy(
            ['tl_news.pid=?', "tl_news.time {$op} ?", "tl_news.published='1'"],
            [$pid, $time],
            ['order' => "tl_news.time {$orderDir}"]
        );
    }

    private function findEdge(int $pid, string $orderDir): ?NewsModel
    {
        return NewsModel::findOneBy(
            ['tl_news.pid=?', "tl_news.published='1'"],
            [$pid],
            ['order' => "tl_news.time {$orderDir}"]
        );
    }

    private function buildItem(NewsModel $news): array
    {
        return [
            'title' => $news->headline ?: $news->subheadline ?: '',
            'url'   => News::generateNewsUrl($news, false, true),
            'alias' => $news->alias,
        ];
    }
}
