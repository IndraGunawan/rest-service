<?php declare(strict_types=1);

/*
 * This file is part of indragunawan/rest-service package.
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
