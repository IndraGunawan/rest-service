<?php

namespace IndraGunawan\RestService;

use GuzzleHttp\Psr7\Response as BaseResponse;

class StreamResult extends BaseResponse
{
    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
        parent::__construct($status, $headers, $body, $version, $reason);
    }
}
