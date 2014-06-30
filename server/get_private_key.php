<?php

$filename = __DIR__ . '/keys/' . $_GET['id'] . '.key';

if (!file_exists($filename)) {
    header("HTTP/1.0 404 Not Found");

    exit(1);
}

echo file_get_contents($filename);