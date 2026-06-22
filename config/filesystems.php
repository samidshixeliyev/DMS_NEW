<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Attachment Disk
    |--------------------------------------------------------------------------
    |
    | The disk that NEW execution attachments are written to. During the
    | migration to MinIO this is set to 'minio'. Existing rows keep their own
    | 'disk' value (defaulting to 'local'), so old files are still read from
    | local storage while new uploads go to MinIO.
    |
    */

    'attachment_disk' => env('ATTACHMENT_DISK', 'minio'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // MinIO (S3-compatible object storage) used for execution attachments.
        'minio' => [
            'driver' => 's3',
            'key' => env('MINIO_ACCESS_KEY', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('MINIO_SECRET_KEY', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('MINIO_REGION', 'us-east-1'),
            'bucket' => env('MINIO_BUCKET', 'dms'),
            'endpoint' => env('MINIO_ENDPOINT', 'http://localhost:9000'),
            // MinIO requires path-style addressing: https://host:9000/bucket/key
            'use_path_style_endpoint' => true,
            // TLS verification for the underlying AWS/Guzzle client:
            //   MINIO_SSL_CA = /path/to/ca.pem  -> verify against this CA/cert bundle (HTTPS w/ self-signed or internal CA)
            //   MINIO_SSL_CA unset             -> verify against the system CA bundle (true)
            //   MINIO_SSL_CA = false           -> disable verification (NOT recommended)
            'http' => [
                'verify' => env('MINIO_SSL_CA', true),
            ],
            'throw' => true,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
