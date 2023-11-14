<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlsrv'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_MY', '127.0.0.1'),
            'port' => env('DB_PORT_MY', '3306'),
            'database' => env('DB_DATABASE_MY', 'forge'),
            'username' => env('DB_USERNAME_MY', 'forge'),
            'password' => env('DB_PASSWORD_MY', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            // 'prefix' => '',
            'prefix_indexes' => true,
            // 'strict' => true,
            // 'engine' => null,
            // 'options' => extension_loaded('pdo_mysql') ? array_filter([
            //     PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            // ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_mmis' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_MMIS', 'localhost'),
            'port' => env('DB_PORT_MMIS', '1433'),
            'database' => env('DB_DATABASE_MMIS', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_pos' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_POS', '10.4.15.251'),
            'port' => env('DB_PORT_POS', '1433'),
            'database' => env('DB_DATABASE_POS', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
        'sqlsrv_schedules' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_SCHEDULES', 'localhost'),
            'port' => env('DB_PORT_SCHEDULES', '1433'),
            'database' => env('DB_DATABASE_SCHEDULES', 'forge'),
            'username' => env('DB_USERNAME_SCHED', 'forge'),
            'password' => env('DB_PASSWORD_SCHED', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
        'sqlsrv_patient_data' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_PATIENT_DATA', '10.4.15.251'),
            'port' => env('DB_PORT_PATIENT_DATA', '1433'),
            'database' => env('DB_DATABASE_PATIENT_DATA', ''),
            'username' => env('DB_USERNAME_PATIENT_DATA', ''),
            'password' => env('DB_PASSWORD_PATIENT_DATA', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'sqlsrv_medsys_patient_data' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_MEDYS_PATIENT_DATA', '10.4.15.253'),
            'port' => env('DB_PORT_MEDYS_PATIENT_DATA', '1433'),
            'database' => env('DB_DATABASE_MEDYS_PATIENT_DATA', ''),
            'username' => env('DB_USERNAME_MEDYS_PATIENT_DATA', ''),
            'password' => env('DB_PASSWORD_MEDYS_PATIENT_DATA', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
        'sqlsrv_medsys_hemodialysis' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_MEDYS_HEMODIALYSIS', '10.4.15.253'),
            'port' => env('DB_PORT_MEDYS_HEMODIALYSIS', '1433'),
            'database' => env('DB_DATABASE_MEDYS_HEMODIALYSIS', ''),
            'username' => env('DB_USERNAME_MEDYS_HEMODIALYSIS', ''),
            'password' => env('DB_PASSWORD_MEDYS_HEMODIALYSIS', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
        'sqlsrv_medsys_nurse_station' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_MEDYS_NURSE_STATION', '10.4.15.253'),
            'port' => env('DB_PORT_MEDYS_NURSE_STATION', '1433'),
            'database' => env('DB_DATABASE_MEDYS_NURSE_STATION', ''),
            'username' => env('DB_USERNAME_MEDYS_NURSE_STATION', ''),
            'password' => env('DB_PASSWORD_MEDYS_NURSE_STATION', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
        'sqlsrv_medsys_patientdatacdg' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST_MEDYS_CDG_DB', '10.4.15.253'),
            'port' => env('DB_PORT_MEDYS_CDG_DB', '1433'),
            'database' => env('DB_DATABASE_MEDYS_CDG_DB', ''),
            'username' => env('DB_USERNAME_MEDYS_CDG_DB', ''),
            'password' => env('DB_PASSWORD_MEDYS_CDG_DB', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],









    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
