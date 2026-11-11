<?php
// Taruh file ini di ROOT folder moodle/ (sejajar dengan folder lib/ atau public/).
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('PGHOST');
$CFG->dbname    = getenv('PGDATABASE') ?: 'postgres';
$CFG->dbuser    = getenv('PGUSER');
$CFG->dbpass    = getenv('PGPASSWORD');
$CFG->prefix    = 'mdl_';
$CFG->dboptions = [
    'dbpersist' => 0,
    'dbport'    => getenv('PGPORT') ?: 5432,
    'dbsocket'  => '',
    'sslmode'   => 'require',
    'dbhandlesoptions' => true, // <-- TAMBAHAN UNTUK SUPABASE POOLER
];

$CFG->wwwroot  = rtrim(getenv('MOODLE_WWWROOT') ?: 'http://localhost:8080', '/');
$CFG->sslproxy = (strpos($CFG->wwwroot, 'https://') === 0);

// Semua yang writable diarahkan ke /tmp (sementara, hilang saat instance dibuang)
$CFG->dataroot       = '/tmp/moodledata';
$CFG->tempdir        = '/tmp/moodletemp';
$CFG->cachedir       = '/tmp/moodlecache';
$CFG->localcachedir  = '/tmp/moodlelocalcache';
foreach ([$CFG->dataroot, $CFG->tempdir, $CFG->cachedir, $CFG->localcachedir] as $d) {
    if (!is_dir($d)) { @mkdir($d, 0777, true); }
}
$CFG->directorypermissions = 0777;
$CFG->admin = 'admin';

// Tidak ada instal/upgrade plugin lewat web
$CFG->disableupdateautodeploy = true;

// Aktifkan SETELAH instalasi database selesai: MOODLE_DBSESSIONS=1
if (getenv('MOODLE_DBSESSIONS') === '1') {
    $CFG->session_handler_class = '\core\session\database';
    $CFG->session_database_acquire_lock_timeout = 120;
}

$setup = is_file(__DIR__ . '/public/lib/setup.php')
    ? __DIR__ . '/public/lib/setup.php'   // Moodle 5.1+
    : __DIR__ . '/lib/setup.php';
require_once($setup);
