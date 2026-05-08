<?php

declare(strict_types=1);

namespace Vanilo\Mollie\Tests\Fakes;

use Mollie\Api\HttpAdapter\MollieHttpAdapterInterface;

class FakeMollieHttpAdapter implements MollieHttpAdapterInterface
{
    public const string FAKE_API_KEY = 'test_it_does_not_matter_but_at_least_30_characters_is_required';

    public array $requests = [];

    public function send($httpMethod, $url, $headers, $httpBody)
    {
        $this->requests[] = [
            'method' => $httpMethod,
            'url' => $url,
            'headers' => $headers,
            'body' => $httpBody,
        ];

        return new \stdClass();
    }

    public function versionString()
    {
        return '1.0';
    }
}
