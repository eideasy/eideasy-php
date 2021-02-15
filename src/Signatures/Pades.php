<?php

namespace EidEasy\Signatures;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Pades
{
    protected $apiUrl;
    protected $guzzle;

    /**
     * Is using application https://github.com/eideasy/eideasy-external-pades-digital-signatures
     * @param string $apiUrl - PAdES processing server url. Test environment available at https://detached-pdf.eideasy.com
     */
    public function __construct(string $padesApiUrl = "https://detached-pdf.eideasy.com", Client $guzzle = null)
    {
        $this->apiUrl = $padesApiUrl;
        $this->guzzle = $guzzle;
    }

    /**
     * @param string $apiUrl
     */
    public function setApiUrl(string $apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * @param Client|null $guzzle
     */
    public function setGuzzle(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @param string $pdfFile PDF file in base64 format that will need to be signed
     * @param string $signatureTime is unix timestamp in milliseconds returned from the getDigest call
     * @param string $cadesSignature is ETSI.CAdES.detached binary signature in base64 encoding to be embedded into the PDF file
     * @return array Array with signedFile property containing signed PDF in base64 encoding. error and message if call failed.
     */
    public function addSignaturePades(string $pdfFile, string $signatureTime, string $cadesSignature, ?array $padesDssData, SignatureParameters $parameters = null): array
    {
        $data = [
            'fileContent'    => base64_encode($pdfFile),
            'signatureTime'  => $signatureTime,
            'signatureValue' => $cadesSignature,
        ];

        if ($parameters) {
            $data['reason']      = $parameters->getReason();
            $data['contactInfo'] = $parameters->getContactInfo();
            $data['location']    = $parameters->getLocation();
            $data['signerName']  = $parameters->getSignerName();
        }

        if ($padesDssData) {
            $data['padesDssData'] = $padesDssData;
        }

        return $this->sendRequest("/api/detached-pades/complete", $data);
    }

    /**
     * @param string $pdfFile PDF file contents will need to be signed
     * @return array Array property digest that will be signed and signatureTime if success. error and message if call failed.
     */
    public function getPadesDigest(string $pdfFile, SignatureParameters $parameters = null): array
    {
        $data = [
            'fileContent' => base64_encode($pdfFile),
        ];

        if ($parameters) {
            $data['reason']      = $parameters->getReason();
            $data['contactInfo'] = $parameters->getContactInfo();
            $data['location']    = $parameters->getLocation();
            $data['signerName']  = $parameters->getSignerName();
        }
        return $this->sendRequest("/api/detached-pades/prepare", $data);
    }

    protected function sendRequest($path, $body): array
    {
        try {
            $response = $this->guzzle->post($this->apiUrl . $path, [
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json'    => $body,
            ]);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if (!$response) {
                return [
                    'status'  => 'error',
                    'message' => 'No response body: ' . $e->getMessage(),
                ];
            }
            $body     = $response->getBody()->getContents();
            $jsonBody = json_decode($body);
            if (!$jsonBody) {
                return [
                    'status'  => 'error',
                    'message' => 'Response not json: ' . $body,
                ];
            }

            return [
                'status'  => 'error',
                'message' => $body,
            ];
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
