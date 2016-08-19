<?php

return [
    'name' => 'Rest Service',
    'endpoint' => 'http://httpbin.org',
    'operations' => [
        'get' => [
            'httpMethod' => 'GET',
            'requestUri' => '/get'
        ],
    ],
];
