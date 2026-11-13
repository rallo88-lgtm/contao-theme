// Mini-Server: /bundles/rct/* → Worktree src/Resources/public, sonst dieses Verzeichnis
const http = require('http'), fs = require('fs'), path = require('path');
const PUB = path.join(__dirname, '..', '..', 'src', 'Resources', 'public'), HERE = __dirname, MIME = { '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css', '.avif': 'image/avif', '.svg': 'image/svg+xml', '.woff2': 'font/woff2' };
http.createServer((q, r) => { let u = decodeURIComponent(q.url.split('?')[0]); const f = u.startsWith('/bundles/rct/') ? path.join(PUB, u.slice(13)) : path.join(HERE, u === '/' ? 'index.html' : u);
  fs.readFile(f, (e, d) => { if (e) { r.writeHead(404); return r.end(); } r.writeHead(200, { 'Content-Type': MIME[path.extname(f)] || 'application/octet-stream' }); r.end(d); }); }).listen(+process.argv[2] || 8765);
