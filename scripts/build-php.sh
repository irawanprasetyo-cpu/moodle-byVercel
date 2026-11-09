#!/usr/bin/env bash
# Build PHP statis (gd + opcache + pgsql + intl + dll) memakai static-php-cli.
# Jalankan di Linux x86_64 (Fedora kamu cocok). Hasil: bin/php
set -euo pipefail
cd "$(dirname "$0")/.."
EXT="bcmath,ctype,curl,dom,exif,fileinfo,filter,gd,iconv,intl,mbstring,opcache,openssl,pdo,pdo_pgsql,pgsql,phar,posix,redis,session,simplexml,soap,sodium,tokenizer,xml,xmlreader,xmlwriter,zip,zlib"
mkdir -p build bin && cd build
[ -f spc ] || { curl -fsSL -o spc https://dl.static-php.dev/static-php-cli/spc-bin/nightly/spc-linux-x86_64; chmod +x spc; }
./spc doctor --auto-fix
ok=0
for i in $(seq 1 30); do
  if ./spc download --for-extensions="$EXT" --with-php=8.3 --prefer-pre-built --retry=5; then ok=1; break; fi
  echo "Download gagal, coba lagi ($i/30)..."; sleep 20
done
[ "$ok" = 1 ] || { echo "DOWNLOAD GAGAL TOTAL"; exit 1; }
./spc build "$EXT" --build-cli --with-suggested-libs
cp buildroot/bin/php ../bin/php
chmod +x ../bin/php
echo "=== ekstensi terpasang ==="
../bin/php -m | tr '\n' ' '
echo; ls -lh ../bin/php
