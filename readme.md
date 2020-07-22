#### S3 filesystem with caching

#### **Usage**

**installation**
`````
composer require maurit/s3filesystem
`````

**declaration**
`````
$s3storage = new \Maurit\S3filesystem\S3Storage([
    'region'         => 's3_config_region', // required
    'endpoint'       => 's3_config_endpoint_url', // required
    'key'            => 's3_public_key', // required
    'secret'         => 's3_private_key', // required
    'bucket'         => 's3_bucket_name', // required
    'version'        => 's3_config', // optional default 2006-03-01
    'cacheDirectory' => 'path_to_save_cache', // optional default cache in current dir
    'cacheLifetime'  => 'cace_lifetime_in_seconds', // optional default 60*60 (1 hour)
]);
`````

**get file methods**
`````
$s3storage->get('object_key'); // return symfony response object
$s3storage->render('object_key); // return file content (symfony response method send)
$s3storage->getDownload('object_key'); // return file like attachment
$s3storage->getFile('object_key'); // return array file body and header 
`````

**put file methods**
`````
/**
 * @param string $key objec_key
 * @param string $value file content, or file path
 * @param bool $public bool default false, true is public-read, false authenticated-read
 * @param bool $fromFile bool default false, true if value is file path 
 */

$s3storage->put($key, $value, $public, $fromFile); // upload one file to s3
$s3storage->putMany([
    'object_key' => 'file content'
], $public, $fromFile); // upload more files from array to s3
$s3storage->putFromFolder('path_from_in_your_machine', 'path_to_in_s3'); // upload full path from your machine to s3
`````

**removing methods**
`````
$s3storage->forget('object_key'); // remove object from cache
$s3storage->delete('object_key'); // remove file from s3 and remove from cache
`````