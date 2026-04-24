<?php

namespace Rallo\ContaoTheme\Controller\Backend;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RctIconPickerController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir
    ) {}

    #[Route('/contao/rct-icon-picker', name: 'rct_icon_picker', defaults: ['_scope' => 'backend'])]
    public function __invoke(Request $request): Response
    {
        $field = (string) $request->query->get('field', '');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
            return new Response('Invalid field', 400);
        }

        $publicDir = $this->projectDir . '/public/bundles/rct/icons/tabler';
        $bundleDir = \dirname(__DIR__, 2) . '/Resources/public/icons/tabler';
        $dir = is_dir($publicDir) ? $publicDir : $bundleDir;

        $icons = [];
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.svg') as $file) {
                $slug = pathinfo($file, PATHINFO_FILENAME);
                $svg  = (string) file_get_contents($file);
                $svg  = preg_replace('/^<!--.*?-->\s*/s', '', $svg);
                $icons[$slug] = $svg;
            }
            ksort($icons);
        }

        return new Response($this->renderHtml($icons, $field));
    }

    private function renderHtml(array $icons, string $field): string
    {
        $cells = '';
        foreach ($icons as $slug => $svg) {
            $cells .= sprintf(
                '<button type="button" class="cell" data-slug="%s" title="tabler:%s">%s<span class="slug">%s</span></button>',
                htmlspecialchars($slug),
                htmlspecialchars($slug),
                $svg,
                htmlspecialchars($slug)
            );
        }

        $fieldJson = json_encode($field);

        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Icon auswählen</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: system-ui, -apple-system, sans-serif;
    margin: 0;
    padding: 16px;
    background: #23282d;
    color: #eee;
  }
  h1 {
    font-size: 0.95rem;
    margin: 0 0 12px;
    color: #27c4f4;
    font-weight: 600;
  }
  .hint { font-size: 0.75rem; opacity: 0.7; margin: 0 0 12px; }
  .hint code {
    background: rgba(39,196,244,0.15);
    padding: 1px 5px;
    border-radius: 3px;
    font-family: monospace;
  }
  input[type=search] {
    width: 100%;
    padding: 10px 12px;
    margin-bottom: 14px;
    background: #1a1d21;
    border: 1px solid #3a3f45;
    border-radius: 4px;
    color: #eee;
    font-size: 0.9rem;
  }
  input[type=search]:focus {
    outline: none;
    border-color: #27c4f4;
  }
  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 6px;
  }
  .cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 12px 8px;
    background: #2d3339;
    border: 1px solid #3a3f45;
    border-radius: 4px;
    color: inherit;
    cursor: pointer;
    transition: all 0.12s;
    font: inherit;
  }
  .cell:hover {
    background: #27c4f4;
    color: #0a0a0a;
    border-color: #27c4f4;
    transform: scale(1.04);
  }
  .cell svg {
    width: 28px;
    height: 28px;
    stroke-width: 1.75;
  }
  .cell .slug {
    font-size: 0.68rem;
    font-family: monospace;
    word-break: break-all;
    text-align: center;
    opacity: 0.85;
  }
  .cell.hidden { display: none; }
  .empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    opacity: 0.6;
  }
</style>
</head>
<body>
  <h1>🎨 Tabler-Icon auswählen</h1>
  <p class="hint">Klick fügt <code>tabler:slug</code> ins Feld ein und schließt das Fenster.</p>
  <input type="search" id="search" placeholder="Suchen... (rocket, chart, user, paw, ...)" autofocus>
  <div class="grid" id="grid">{$cells}</div>
<script>
(function() {
  var field = {$fieldJson};
  var search = document.getElementById('search');
  var cells  = document.querySelectorAll('.cell');

  search.addEventListener('input', function(e) {
    var q = e.target.value.trim().toLowerCase();
    cells.forEach(function(c) {
      var match = !q || c.dataset.slug.indexOf(q) !== -1;
      c.classList.toggle('hidden', !match);
    });
  });

  cells.forEach(function(c) {
    c.addEventListener('click', function() {
      var value = 'tabler:' + c.dataset.slug;
      if (window.opener && !window.opener.closed) {
        var input = window.opener.document.getElementById(field);
        if (input) {
          input.value = value;
          input.dispatchEvent(new Event('change', { bubbles: true }));
          input.focus();
        }
      }
      window.close();
    });
  });
})();
</script>
</body>
</html>
HTML;
    }
}
