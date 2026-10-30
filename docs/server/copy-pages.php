<?php
// Seiten samt Artikeln + Inhaltselementen von einer Contao-Instanz in eine andere kopieren (Ralph 2026-10-30: Impressum/Datenschutz rct → aws).
//   php copy-pages.php <quell-instanz> <ziel-instanz> <ziel-root-id> <ziel-layout-id> <alias,alias,...>
// Neue IDs im Ziel, Spalten = Schnittmenge beider Tabellen, alles in einer Transaktion. Aliase, die im Ziel schon existieren, werden übersprungen.
[$_, $src, $dst, $root, $layout, $aliases] = $argv + [null, null, null, null, null, null];
if (!$aliases) { fwrite(STDERR, "Aufruf: php copy-pages.php <quelle> <ziel> <root-id> <layout-id> <alias,...>\n"); exit(1); }

function db(string $inst): PDO {
    foreach (["/html/$inst/.env.local", "/html/$inst/.env"] as $f) {
        if (is_file($f) && preg_match('/^DATABASE_URL=["\']?([^"\'\n]+)/m', file_get_contents($f), $m)) {
            $u = parse_url($m[1]);
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $u['host'], $u['port'] ?? 3306, ltrim($u['path'], '/'));
            return new PDO($dsn, urldecode($u['user']), urldecode($u['pass'] ?? ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        }
    }
    throw new RuntimeException("Keine DATABASE_URL für $inst");
}
$S = db($src); $D = db($dst);
$cols = fn(PDO $p, string $t) => array_column($p->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
$common = fn(string $t) => array_values(array_diff(array_intersect($cols($S, $t), $cols($D, $t)), ['id']));

function insert(PDO $D, string $t, array $row, array $common): int {
    $row = array_intersect_key($row, array_flip($common));
    $D->prepare(sprintf('INSERT INTO `%s` (`%s`) VALUES (%s)', $t, implode('`,`', array_keys($row)), implode(',', array_fill(0, count($row), '?'))))
      ->execute(array_values($row));
    return (int) $D->lastInsertId();
}

$now = time();
$sort = (int) $D->query("SELECT COALESCE(MAX(sorting),0) FROM tl_page WHERE pid=" . (int) $root)->fetchColumn();
$D->beginTransaction();
try {
    foreach (explode(',', $aliases) as $alias) {
        $q = $D->prepare('SELECT id FROM tl_page WHERE alias=?'); $q->execute([$alias]);
        if ($q->fetchColumn()) { echo "– $alias existiert im Ziel schon, übersprungen\n"; continue; }
        $q = $S->prepare('SELECT * FROM tl_page WHERE alias=?'); $q->execute([$alias]); $page = $q->fetch(PDO::FETCH_ASSOC);
        if (!$page) { echo "– $alias gibt es in der Quelle nicht\n"; continue; }
        $sort += 128;
        $pid = insert($D, 'tl_page', ['pid' => $root, 'layout' => $layout, 'sorting' => $sort, 'tstamp' => $now] + $page, $common('tl_page'));
        $na = $nc = 0;
        foreach ($S->query('SELECT * FROM tl_article WHERE pid=' . (int) $page['id'] . ' ORDER BY sorting')->fetchAll(PDO::FETCH_ASSOC) as $art) {
            $aid = insert($D, 'tl_article', ['pid' => $pid, 'tstamp' => $now] + $art, $common('tl_article')); $na++;
            foreach ($S->query("SELECT * FROM tl_content WHERE ptable='tl_article' AND pid=" . (int) $art['id'] . ' ORDER BY sorting')->fetchAll(PDO::FETCH_ASSOC) as $ce) {
                insert($D, 'tl_content', ['pid' => $aid, 'tstamp' => $now] + $ce, $common('tl_content')); $nc++;
            }
        }
        echo "✔ $alias → Seite $pid ($na Artikel, $nc Elemente)\n";
    }
    $D->commit();
} catch (Throwable $e) { $D->rollBack(); fwrite(STDERR, "✖ abgebrochen, nichts geschrieben: " . $e->getMessage() . "\n"); exit(1); }
