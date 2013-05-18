<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../lib/WatchFiles/Watch.php';

define('TMP', __DIR__ . '/tmp');

if (!is_dir(TMP)) {
    mkdir(TMP);
}

foreach (glob(TMP . '/*') as $file) {
    unlink($file);
}
