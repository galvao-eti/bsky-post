<?php
declare(strict_types = 1);

/**
 * Posting to BlueSky using PHP and Guzzle
 * @author Er Galvão Abbott <galvao@php.net>
 * @link https://github.com/galvao-eti/bsky-post/branches
 *
 */

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;

/**
 * Generate an application password at settings -> App passwords @ bsky.app
 */

define('BSKY_AUTH', [
    'identifier' => 'your_bsky.app_handler',
    'password' => 'your_app_password',
]);

$client = new Client(['base_uri' => 'https://bsky.social']);
$commonHeaders = ['Content-Type' => 'application/json'];

$authResponse = $client->request(
    'POST',
    '/xrpc/com.atproto.server.createSession',
    [
        'headers' => $commonHeaders,
        'body' => json_encode(BSKY_AUTH)
    ]
);

if ($authResponse->getStatusCode() === 200) {
    $session = json_decode((string)$authResponse->getBody());

    $postHeaders = array_merge($commonHeaders, ['Authorization' => 'Bearer ' . $session->accessJwt]);

    $post = [
        '$type' => 'app.bsky.feed.post',
        'text' => 'Posting to BlueSky with PHP and Guzzle',
        'createdAt' => (new DateTime())->format('Y-m-d\TH:i:s.up'),
    ];

    $postBody = [
        'repo' => $session->did,
        'collection' => 'app.bsky.feed.post',
        'record' => $post,
    ];

    $postResponse = $client->request(
        'POST',
        '/xrpc/com.atproto.repo.createRecord',
        [
            'headers' => $postHeaders,
            'body' => json_encode($postBody)
        ]
    );

    if ($postResponse->getStatusCode() === 200) {
        $postData = json_decode((string)$postResponse->getBody());
        $postSlug = substr($postData->uri, strrpos($postData->uri, '/') + 1);

        echo 'Success!' . PHP_EOL;
        printf('See your post @ https://bsky.app/profile/%s/post/%s' . PHP_EOL, BSKY_AUTH['identifier'], $postSlug);
    } else {
        echo 'Error!' . PHP_EOL;
        printf('Status code: %d' . PHP_EOL . 'Reason: %s' . PHP_EOL, $postResponse->getStatusCode(), $postResponse->getReasonPhrase());
    }
} else {
        echo 'Authentication Error!' . PHP_EOL;
        printf('Status code: %d' . PHP_EOL . 'Reason: %s' . PHP_EOL, $authResponse->getStatusCode(), $authResponse->getReasonPhrase());
}
