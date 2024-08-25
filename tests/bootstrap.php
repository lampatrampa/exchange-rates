<?php

declare(strict_types = 1);

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require getcwd() . '/vendor/autoload.php';

if (file_exists(getcwd() . '/config/bootstrap.php')) {
    require getcwd() . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(getcwd() . '/.env');
}


$cacheDir = getcwd() . '/var/cache';

$fs = new Filesystem();

if ($fs->exists($cacheDir)) {
    $fs->remove($cacheDir);
}