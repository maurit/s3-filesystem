<?php

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

require_once '../vendor/autoload.php';
require_once '../src/S3Storage.php';

$s3storage = new \Maurit\S3filesystem\S3Storage([
    'region'   => 'nl-ams',
    'endpoint' => 'https://s3.nl-ams.scw.cloud',
    'key'      => 'publickey',
    'secret'   => 'privatekey',
    'bucket'   => 'bucket'
]);

$s3storage->render('toskansko/IMG_20190402_145911.jpg');
