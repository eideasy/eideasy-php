<?php

namespace EidEasy\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class EidEasyApi
{
    private $guzzle;
    private $clientId;
    private $secret;
    private $apiUrl;
    private $longPollTimeout = 120;

    public function __construct(
        Client $guzzle = null,
        string $clientId = null,
        string $secret = null,
        string $apiUrl = "https://id.eideasy.com"
    )
    {
        $this->clientId = $clientId;
        $this->secret   = $secret;
        $this->apiUrl   = $apiUrl;
        $this->guzzle   = $guzzle;
    }

    /**
     * @param Client|null $guzzle
     */
    public function setGuzzle(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @param string|null $clientId
     */
    public function setClientId(string $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @param string|null $secret
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param string $apiUrl
     */
    public function setApiUrl(string $apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * @param int $longPollTimeout
     */
    public function setLongPollTimeout(int $longPollTimeout)
    {
        $this->longPollTimeout = $longPollTimeout;
    }

    public function startIdentification(string $method, array $data)
    {
        $params = array_merge([
            'client_id' => $this->clientId,
            'secret'    => $this->secret,
            'timeout'   => $this->longPollTimeout,
        ], $data);

        return $this->sendRequest("/api/identity/$this->clientId/$method/start", $params);
    }

    public function completeIdentification(string $method, array $data)
    {
        $params = array_merge([
            'client_id' => $this->clientId,
            'secret'    => $this->secret,
            'timeout'   => $this->longPollTimeout,
        ], $data);

        return $this->sendRequest("/api/identity/$this->clientId/$method/complete", $params);
    }

    /**
     * @param string $docId
     */
    public function getSignedFile(string $docId): array
    {
        return $this->sendRequest('/api/signatures/download-signed-file', [
            'client_id' => $this->clientId,
            'secret'    => $this->secret,
            'doc_id'    => $docId,
        ]);
    }

    /**
     * @param $files array
     * @param array|null $parameters
     */
    public function prepareFiles(array $files, array $parameters = []): array
    {
        $data = [
            'client_id'      => $this->clientId,
            'secret'         => $this->secret,
            'container_type' => $parameters['container_type'] ?? 'asice',
            'baseline'       => $parameters['baseline'] ?? 'LT',
            'files'          => $files
        ];
        if (isset($parameters['signature_redirect'])) {
            $data['signature_redirect'] = $parameters['signature_redirect'];
        }
        if (isset($parameters['nodownload'])) {
            $data['nodownload'] = true;
        }
        if (isset($parameters['noemails'])) {
            $data['noemails'] = true;
        }
        if (isset($parameters['hide_preview_download'])) {
            $data['hide_preview_download'] = $parameters['hide_preview_download'];
        }
        if (isset($parameters['email_extra'])) {
            $data['email_extra'] = $parameters['email_extra'];
        }
        if (isset($parameters['notification_state'])) {
            $data['notification_state'] = $parameters['notification_state'];
        }

        return $this->sendRequest('/api/signatures/prepare-files-for-signing', $data);
    }

    /**
     * @param string $docId
     */
    public function downloadSignedFile(string $docId): array
    {
        $data = [
            'client_id' => $this->clientId,
            'secret'    => $this->secret,
            'doc_id'    => $docId,
        ];

        return $this->sendRequest('/api/signatures/download-signed-file', $data);
    }

    protected function sendRequest($path, $body = [], $method = 'POST'): array
    {
        $body = array_map('trim', $body);
        try {
            if ($method === 'POST') {
                $response = $this->guzzle->post($this->apiUrl . $path, [
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'json'    => $body,
                ]);
            } elseif ($method === 'GET') {
                $response = $this->guzzle->get($this->apiUrl . $path, [
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'query'   => $body,
                ]);
            } else {
                $response = $this->guzzle->request($method, $this->apiUrl . $path, [
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'json'    => $body,
                ]);
            }
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if (!$response) {
                [
                    'status'  => 'error',
                    'message' => 'No response body: ' . $e->getMessage(),
                ];
            }
            $body     = $response->getBody()->getContents();
            $jsonBody = json_decode($body, true);
            if (!$jsonBody) {
                return [
                    'status'  => 'error',
                    'message' => 'Response not json: ' . $body,
                ];
            }

            if (!array_key_exists('status', $jsonBody)) {
                $jsonBody['status'] = 'error';
            }

            return $jsonBody;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
