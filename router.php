<?php
// Router untuk php -S: meniru rewrite Apache/Nginx untuk Moodle (slash arguments, dll).
$root = $_SERVER['DOCUMENT_ROOT'];
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// file statis -> biarkan built-in server yang melayani
if ($path !== '/' && is_file($root . $path) && substr($path, -4) !== '.php') {
    return false;
}

$script = '';
$info = '';
$parts = explode('/', trim($path, '/'));
for ($i = count($parts); $i > 0; $i--) {
    $cand = '/' . implode('/', array_slice($parts, 0, $i));
    if (substr($cand, -4) === '.php' && is_file($root . $cand)) {
        $script = $cand;
        $info = substr($path, strlen($cand));
        break;
    }
}
if ($script === '') {
    $dir = rtrim($path, '/');
    if (is_file($root . $dir . '/index.php')) {
        $script = $dir . '/index.php';
    } elseif (is_file($root . '/r.php')) {      // router Moodle 5.1+
        $script = '/r.php';
        $info = $path;
    } else {
        http_response_code(404);
        echo 'Not found';
        return true;
    }
}

$_SERVER['SCRIPT_NAME'] = $script;
$_SERVER['SCRIPT_FILENAME'] = $root . $script;
$_SERVER['PHP_SELF'] = $script . $info;
if ($info !== '') { $_SERVER['PATH_INFO'] = $info; } else { unset($_SERVER['PATH_INFO']); }
chdir(dirname($root . $script));
require $root . $script;
return true;
