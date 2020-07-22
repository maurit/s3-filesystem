<?php

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

require_once '../vendor/autoload.php';
require_once '../src/S3Storage.php';
require_once '../src/Exceptions.php';

$s3storage = new \Maurit\S3filesystem\S3Storage([
    'region'         => 's3_config_region', // required
    'endpoint'       => 's3_config_endpoint_url', // required
    'key'            => 's3_public_key', // required
    'secret'         => 's3_private_key', // required
    'bucket'         => 's3_bucket_name', // required
    'version'        => 's3_config', // optional default 2006-03-01
    'cacheDirectory' => 'path_to_save_cache', // optional default cache in current dir
    'cacheDirectory' => 'path_to_save_cache', // optional default cache in current dir
    'cacheLifetime'  => 'cace_lifetime_in_seconds', // optional default 60*60 (1 hour)
]);

$s3storage->render('toskansko/IMG_20190402_131847.jpg');
