# Moodle di Vercel (PHP statis + php -S)

## 0. Struktur
    bin/php            <- hasil build-php.sh
    moodle/            <- hasil get-moodle.sh (+ config.php & router.php disalin ke sini)
    api/index.js       <- wrapper
    vercel.json

## 1. Build PHP statis (lokal, Linux x86_64)
    bash scripts/build-php.sh
Cek output `php -m`: harus ada gd, opcache, pgsql, pdo_pgsql, intl, sodium, soap, zip, redis.
Kalau `spc doctor` bermasalah di Fedora, pakai versi Docker-nya (spc-alpine-docker).
Cek juga `bin/php -S 127.0.0.1:8000` jalan (SAPI cli-server wajib ada).

## 2. Ambil Moodle
    bash scripts/get-moodle.sh
    cp config.php router.php moodle/
Paket bahasa Indonesia (disimpan di kode, bukan di dataroot yang hilang):
    curl -L -o id.zip https://download.moodle.org/download.php/langpack/5.2/id.zip
    unzip id.zip -d moodle/lang/
Ukuran `moodle/` + `bin/php` harus < 250 MB. Cek: `du -sh moodle bin`

## 3. Database Supabase
Pakai Session pooler (IPv4, port 5432) atau direct connection. JANGAN transaction pooler (6543).
Env lokal untuk instalasi:
    export PGHOST=... PGPORT=5432 PGUSER=postgres.xxxx PGPASSWORD=... PGDATABASE=postgres
    export MOODLE_WWWROOT=https://NAMA-PROJECT.vercel.app

## 4. Instal database dari lokal (pakai binary yang sama)
    ls moodle/admin/cli moodle/public/admin/cli 2>/dev/null   # cari install_database.php
    bin/php moodle/admin/cli/install_database.php --agree-license \
      --adminuser=admin --adminpass='GantiIni123!' --adminemail=kamu@email.com \
      --fullname='LMS Test' --shortname='LMS'

## 5. Deploy (CLI, bukan git, supaya binary tidak kena batas repo)
    npm i -g vercel
    vercel link
    vercel env add PGHOST        # ulangi: PGPORT PGUSER PGPASSWORD PGDATABASE MOODLE_WWWROOT
    vercel deploy --prod

## 6. Setelah login berhasil
    vercel env add MOODLE_DBSESSIONS   # isi: 1  (sesi pindah ke database)
    vercel deploy --prod
Berikutnya: Redis (cache MUC), tool_objectfs + S3 Supabase (file upload), cron.
