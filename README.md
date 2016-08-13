RestService
===========

[![Source Code](http://img.shields.io/badge/source-indragunawan/rest--service-blue.svg?style=flat-square)](https://github.com/indragunawan/rest-service)
[![GitHub license](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/indragunawan/rest-service/blob/master/LICENSE)
[![Travis](https://img.shields.io/travis/indragunawan/rest-service.svg?style=flat-square)](https://travis-ci.org/indragunawan/rest-service)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/indragunawan/rest-service.svg?style=flat-square)](https://scrutinizer-ci.com/g/indragunawan/rest-service)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/indragunawan/rest-service.svg?style=flat-square)](https://scrutinizer-ci.com/g/indragunawan/rest-service/code-structure)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/be48d599-8e72-4b03-b8b8-a99301c4823a/small.png)](https://insight.sensiolabs.com/projects/be48d599-8e72-4b03-b8b8-a99301c4823a)

Provides an implementation of the Guzzle Command library that uses services specification to describe web services.

Installation
------------

Require the library with composer:

``` bash
$ composer require indragunawan/rest-service
```

Composer will install the library to your project’s `vendor/indragunawan/rest-service` directory.

Usage
-----

```php
<?php

// httpbin-v1.php

return [
    'name' => 'OneSignal API',
    'endpoint' => '{endpoint}',
    'defaults' => [
        'endpoint' => [
            'rule' => 'required | url', // see: http://www.sirius.ro/php/sirius/validation/validation_rules.html
            'defaultValue' => 'http://httpbin.org',
        ],
    ],
    'operations' => [
        'postTest' => [
            'httpMethod' => 'POST', // header, uri, query, body
            'requestUri' => '/post',
            'request' => [
                'type' => 'map', // map, list
                'members' => [
                    'Name' => [
                        'locationName' => 'name',
                        'type' => 'string', // string, integer, float, number, boolean, datetime
                        'rule' => 'required',
                    ],
                    'CreatedAt' => [
                        'type' => 'datetime',
                        'defaultValue' => 'now',
                        'format' => 'd M y',
                    ],
                ],
            ],
            'response' => [
                'members' => [
                    'url' => [
                        'type' => 'string',
                        'format' => 'format_%s',
                    ]
                ]
            ],
        ],
    ],
];
```

```php
use IndraGunawan\RestService\ServiceClient;

    $config = [
        'httpClient' => [
            // use by GuzzleClient
        ],
        'defaults' => [
            // default value for services specification
        ],
    ];
    $cacheDir = __DIR__.'/../cache'; // optional
    $debug = false; // optional, default: false

    $service = new ServiceClient(__DIR__.'/httpbin-v1.php', $config, $cacheDir, $debug);
    $result = $service->getApps([
        'Name' => 'My Name',
    ]);

    echo $result['url']; // format_http://httpbin.org/post
    // var_dump($result->toArray());
```

Todo
----
* Add more tests.
* Add more documentation.
* Parse Response to Model
