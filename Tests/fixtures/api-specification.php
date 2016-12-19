<?php

return [
    'name' => 'Rest Service',
    'endpoint' => 'http://httpbin.org',
    'operations' => [
        'get' => [
            'httpMethod' => 'GET',
            'requestUri' => '/get',
        ],
        'getStream' => [
            'httpMethod' => 'GET',
            'requestUri' => '/stream-bytes/5',
            'responseProtocol' => 'stream',
        ],
        'getStreamError' => [
            'httpMethod' => 'GET',
            'requestUri' => '/stream-bytes/5',
        ],
        'getJson' => [
            'httpMethod' => 'POST',
            'requestUri' => '/post',
        ],
    ],
];
