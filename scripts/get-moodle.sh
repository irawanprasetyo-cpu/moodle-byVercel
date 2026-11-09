#!/usr/bin/env bash
# Ambil Moodle dan pangkas supaya muat di batas 250 MB function Vercel.
set -euo pipefail
cd "$(dirname "$0")/.."
BRANCH="${MOODLE_BRANCH:-MOODLE_502_STABLE}"   # cek nama branch di github.com/moodle/moodle
rm -rf moodle
git clone --depth 1 -b "$BRANCH" https://github.com/moodle/moodle.git moodle
cd moodle
rm -rf .git .github .grunt .upgradenotes
# hapus folder test/behat (aman, hanya untuk developer)
find . -type d \( -name tests -o -name behat -o -name fixtures \) -prune -exec rm -rf {} + 2>/dev/null || true
# bahasa: simpan en saja (paket 'id' ditaruh manual, lihat README)
find lang -mindepth 1 -maxdepth 1 ! -name en -exec rm -rf {} + 2>/dev/null || true
du -sh .
