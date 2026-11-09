// Vercel Function: menjalankan `php -S` (binary statis) dan mem-proxy request ke sana.
// php -S dipakai (bukan CGI per request) supaya OPcache tetap hangat selama instance hidup.
const { spawn } = require('child_process');
const fs = require('fs');
const http = require('http');
const net = require('net');
const path = require('path');

const PORT = 8081;
const BASE = process.cwd();
const MOODLE = path.join(BASE, 'moodle');
const WEBROOT = fs.existsSync(path.join(MOODLE, 'public')) ? path.join(MOODLE, 'public') : MOODLE;
const PHP_SRC = path.join(BASE, 'bin', 'php');
const PHP = '/tmp/php';

let ready = null;

function waitPort(port, tries = 100) {
  return new Promise((resolve, reject) => {
    const attempt = (n) => {
      const s = net.connect(port, '127.0.0.1');
      s.on('connect', () => { s.destroy(); resolve(); });
      s.on('error', () => {
        s.destroy();
        if (n <= 0) return reject(new Error('php server tidak naik'));
        setTimeout(() => attempt(n - 1), 100);
      });
    };
    attempt(tries);
  });
}

function boot() {
  if (ready) return ready;
  ready = (async () => {
    if (!fs.existsSync(PHP)) {           // salin ke /tmp supaya pasti executable
      fs.copyFileSync(PHP_SRC, PHP);
      fs.chmodSync(PHP, 0o755);
    }
    const args = [
      '-d', 'memory_limit=512M',
      '-d', 'max_input_vars=5000',
      '-d', 'upload_max_filesize=4M',
      '-d', 'post_max_size=4500K',
      '-d', 'display_errors=0',
      '-d', 'log_errors=1',
      '-d', 'error_log=/dev/stderr',
      '-d', 'opcache.enable=1',
      '-d', 'opcache.memory_consumption=192',
      '-d', 'opcache.max_accelerated_files=20000',
      '-d', 'opcache.validate_timestamps=0',
      '-d', 'date.timezone=Asia/Jakarta',
      '-S', `127.0.0.1:${PORT}`,
      '-t', WEBROOT,
      path.join(MOODLE, 'router.php'),
    ];
    const child = spawn(PHP, args, {
      cwd: MOODLE,
      env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4' },
      stdio: ['ignore', 'inherit', 'inherit'],
    });
    child.on('exit', () => { ready = null; });
    await waitPort(PORT);
  })();
  return ready;
}

module.exports = async (req, res) => {
  try {
    await boot();
  } catch (e) {
    res.statusCode = 500;
    return res.end('Gagal menjalankan PHP: ' + e.message);
  }
  const host = req.headers['x-forwarded-host'] || req.headers.host;
  const headers = {
    ...req.headers,
    host,
    'x-forwarded-proto': 'https',
    'x-forwarded-host': host,
  };
  const proxyReq = http.request(
    { host: '127.0.0.1', port: PORT, path: req.url, method: req.method, headers },
    (proxyRes) => {
      const out = { ...proxyRes.headers };
      delete out['transfer-encoding'];
      delete out['connection'];
      res.writeHead(proxyRes.statusCode, out);
      proxyRes.pipe(res);
    }
  );
  proxyReq.on('error', (e) => {
    if (!res.headersSent) res.statusCode = 502;
    res.end('Bad gateway: ' + e.message);
  });
  req.pipe(proxyReq);
};
