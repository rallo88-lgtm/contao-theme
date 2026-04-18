<?php

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: 'rct_gallery', category: 'rct', template: 'content_element/rct_gallery')]
class RctGalleryController extends AbstractContentElementController
{
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $images     = [];
        $categories = [];
        $sortby     = $model->rct_gallery_sortby ?: 'name_asc';

        // Einzelbilder haben Vorrang vor Ordner
        $imageUuids = StringUtil::deserialize($model->rct_gallery_images, true);
        $imageUuids = array_filter($imageUuids); // leere Einträge entfernen

        if (!empty($imageUuids)) {
            $images = $this->loadIndividualImages($imageUuids, $sortby);
        } elseif ($model->rct_gallery_folder) {
            [$images, $categories] = $this->loadImages(
                $model->rct_gallery_folder,
                $sortby,
                (bool) $model->rct_gallery_filter
            );
        }

        $cssId = StringUtil::deserialize($model->cssID, true);

        $template->images      = $images;
        $template->categories  = $categories;
        $template->layout      = $model->rct_gallery_layout   ?: 'masonry';
        $template->cols        = max(2, min(6, (int) ($model->rct_gallery_cols ?: 3)));
        $template->showFilter  = (bool) $model->rct_gallery_filter && !empty($categories);
        $template->lightbox    = (bool) $model->rct_gallery_lightbox;
        $template->galleryId   = 'rct-gallery-' . $model->id;
        $template->htmlId      = trim($cssId[0] ?? '', '"\'');
        $template->cssClass    = $cssId[1] ?? '';

        return $template->getResponse();
    }

    private function loadIndividualImages(array $uuids, string $sortby): array
    {
        $images = [];

        foreach ($uuids as $uuid) {
            $file = FilesModel::findByUuid($uuid);
            if ($file === null || $file->type !== 'file') {
                continue;
            }
            if (!\in_array(strtolower($file->extension), self::ALLOWED_EXT, true)) {
                continue;
            }

            $meta = StringUtil::deserialize($file->meta, true);
            $alt  = $meta['de']['alt'] ?? $meta['en']['alt'] ?? basename($file->name, '.' . $file->extension);

            $images[] = [
                'path'     => '/' . $file->path,
                'name'     => $file->name,
                'alt'      => htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'),
                'category' => '',
            ];
        }

        // Bei Einzel-Auswahl: Reihenfolge wie gewählt (keine Sortierung), außer explizit anders
        match ($sortby) {
            'name_desc' => usort($images, fn($a, $b) => strcmp($b['name'], $a['name'])),
            'name_asc'  => usort($images, fn($a, $b) => strcmp($a['name'], $b['name'])),
            'random'    => shuffle($images),
            default     => null, // Reihenfolge aus Auswahl behalten
        };

        return $images;
    }

    private function loadImages(string $folderUuid, string $sortby, bool $withFilter): array
    {
        $folder = FilesModel::findByUuid($folderUuid);
        if ($folder === null || $folder->type !== 'folder') {
            return [[], []];
        }

        $images     = [];
        $categories = [];

        if ($withFilter) {
            // Unterordner als Kategorien
            $subfolders = FilesModel::findBy(
                ['pid=? AND type=?'],
                [$folder->uuid, 'folder'],
                ['order' => 'name ASC']
            );

            if ($subfolders !== null) {
                foreach ($subfolders as $sub) {
                    $catName = basename($sub->path);
                    $catSlug = $this->slugify($catName);
                    $categories[] = ['name' => $catName, 'slug' => $catSlug];

                    $this->collectFiles($sub->uuid, $catSlug, $images);
                }
            }

            // Bilder direkt im Root-Ordner (unkategorisiert)
            $this->collectFiles($folder->uuid, '', $images);
        } else {
            $this->collectFiles($folder->uuid, '', $images);
        }

        // Sortierung
        match ($sortby) {
            'name_desc' => usort($images, fn($a, $b) => strcmp($b['name'], $a['name'])),
            'random'    => shuffle($images),
            default     => usort($images, fn($a, $b) => strcmp($a['name'], $b['name'])),
        };

        return [$images, $categories];
    }

    private function collectFiles(string $folderUuid, string $category, array &$images): void
    {
        $files = FilesModel::findBy(
            ['pid=? AND type=?'],
            [$folderUuid, 'file']
        );

        if ($files === null) {
            return;
        }

        foreach ($files as $file) {
            if (!\in_array(strtolower($file->extension), self::ALLOWED_EXT, true)) {
                continue;
            }

            // Metadaten (alt-Text aus Contao Datei-Manager falls gesetzt)
            $meta = StringUtil::deserialize($file->meta, true);
            $alt  = $meta['de']['alt'] ?? $meta['en']['alt'] ?? basename($file->name, '.' . $file->extension);

            $images[] = [
                'path'     => '/' . $file->path,
                'name'     => $file->name,
                'alt'      => htmlspecialchars($alt, ENT_QUOTES, 'UTF-8'),
                'category' => $category,
            ];
        }
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
