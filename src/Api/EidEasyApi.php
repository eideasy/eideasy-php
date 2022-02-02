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
    private $longPollTimeout = 120000;

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

    /**
     * @param string $method that is used for identification
     * @param array $data check the API doc to see what parameters are needed for each method
     * @return string[]
     */
    public function startIdentification(string $method, array $data)
    {
        $params = array_merge([
            'client_id' => $this->clientId,
            'secret'    => $this->secret,
            'timeout'   => $this->longPollTimeout,
        ], $data);

        return $this->sendRequest("/api/identity/$this->clientId/$method/start", $params);
    }

    /**
     * @param string $method that is used for identification
     * @param array $data check the API doc to see what parameters are needed for each method
     * @return string[]
     */
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
     * Will add e-Seal to the document
     * @param $docId string returned from prepare-files-for-signing API call
     * @return string[]
     */
    public function createEseal($docId)
    {
        $timestamp = time();
        $uri       = "/api/signatures/e-seal/create";
        $hmacData  = "$this->clientId$this->secret$docId$timestamp$uri";
        $hmac      = hash_hmac('SHA256', $hmacData, $this->secret);
        $params    = [
            'client_id' => $this->clientId,
            'secret'    => $this->secret,
            'doc_id'    => $docId,
            'timestamp' => $timestamp,
            'hmac'      => $hmac,
        ];

        return $this->sendRequest($uri, $params);
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
            'client_id'             => $this->clientId,
            'secret'                => $this->secret,
            'container_type'        => $parameters['container_type'] ?? 'asice',
            'baseline'              => $parameters['baseline'] ?? 'LT',
            'files'                 => $files,
            'show_visual'           => $parameters['show_visual'] ?? true,
            'nodownload'            => $parameters['nodownload'] ?? false,
            'noemails'              => $parameters['noemails'] ?? false,
            'hide_preview_download' => $parameters['hide_preview_download'] ?? false,
        ];

        $data = $this->addPrepareFileSigningParams($data, $parameters);

        return $this->sendRequest('/api/signatures/prepare-files-for-signing', $data);
    }

    /**
     * @param string $file
     * @param array $parameters
     * @return string[]
     */
    public function prepareAsiceForSigning(string $file, array $parameters = []): array
    {
        $data = [
            'client_id'      => $this->clientId,
            'secret'         => $this->secret,
            'container'      => $file,
            'filename'       => $parameters['filename'] ?? 'filename.asice'
        ];

        $data = $this->addPrepareFileSigningParams($data, $parameters);

        return $this->sendRequest('/api/signatures/prepare-add-signature', $data);
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

    /**
     * @param string $docId
     */
    public function downloadAuditTrail(string $docId): array
    {
        return $this->sendRequest('/api/signatures/download-audit-trail', [
            'client_id' => $this->clientId,
            'secret'    => $this->secret,
            'doc_id'    => $docId,
        ]);
    }

    /**
     * @param string $method that is used for identification
     */
    public function getIdCardIntegrationToken(string $method): array
    {
        $data = [
            'client_id' => $this->clientId,
            'method'    => $method,
        ];

        return $this->sendRequest('/api/signatures/integration/id-card/get-token', $data);
    }

    protected function sendRequest($path, $body = [], $method = 'POST'): array
    {
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
                return [
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

    /**
     * @param array $data
     * @param array $parameters
     * @return array
     */
    protected function addPrepareFileSigningParams(array $data, array $parameters): array
    {
        if (isset($parameters['visual_coordinates'])) {
            $data['visual_coordinates'] = $parameters['visual_coordinates'];
        }
        if (isset($parameters['signature_redirect'])) {
            $data['signature_redirect'] = $parameters['signature_redirect'];
        }
        if (isset($parameters['nodownload'])) {
            $data['nodownload'] = true;
        }
        if (isset($parameters['noemails'])) {
            $data['noemails'] = true;
        }
        if (isset($parameters['email_extra'])) {
            $data['email_extra'] = $parameters['email_extra'];
        }
        if (isset($parameters['notification_state'])) {
            $data['notification_state'] = $parameters['notification_state'];
        }
        if (isset($parameters['signer'])) {
            $data['signer'] = $parameters['signer'];
        }
        if (isset($parameters['lang'])) {
            $data['lang'] = $parameters['lang'];
        }

        return $data;
    }
}
