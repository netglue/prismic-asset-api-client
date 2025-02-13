<?php

declare(strict_types=1);

use Http\Client\Curl\Client as HttpClient;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Prismic\Asset\AssetClient;
use Prismic\Asset\Client;

require_once __DIR__ . '/../vendor/autoload.php';

function getClient(): Client
{
    return new AssetClient(
        (static function (): string {
            $token = getenv('PRISMIC_TOKEN');
            if (! is_string($token) || $token === '') {
                throw new RuntimeException('Please set a PRISMIC_TOKEN env var');
            }

            return $token;
        })(),
        (static function (): string {
            $repo = getenv('PRISMIC_REPO');
            if (! is_string($repo) || $repo === '') {
                throw new RuntimeException('Please set a PRISMIC_REPO env var');
            }

            return $repo;
        })(),
        new HttpClient(
            new ResponseFactory(),
            new StreamFactory(),
        ),
        new RequestFactory(),
        new UriFactory(),
        new StreamFactory(),
    );
}
