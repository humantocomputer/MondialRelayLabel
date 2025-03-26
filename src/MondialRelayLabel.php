<?php

/** @noinspection NullPointerExceptionInspection */

namespace Humantocomputer\MondialRelayLabel;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use SimpleXMLElement;

class MondialRelayLabel
{
    protected string $url;

    protected string $brand_id_api;

    protected string $login;

    protected string $password;

    public function __construct()
    {
        // check config('mondial-relay-label.url') is set or not
        if (! config('mondial-relay-label.url')) {
            throw new RuntimeException('Mondial Relay API URL is not set');
        }

        // check config('mondial-relay-label.brand_id_api') is set or not
        if (! config('mondial-relay-label.brand_id_api')) {
            throw new RuntimeException('Mondial Relay API Brand ID is not set');
        }

        // check config('mondial-relay-label.login') is set or not
        if (! config('mondial-relay-label.login')) {
            throw new RuntimeException('Mondial Relay API Login is not set');
        }

        // check config('mondial-relay-label.password') is set or not
        if (! config('mondial-relay-label.password')) {
            throw new RuntimeException('Mondial Relay API Password is not set');
        }

        $this->url = config('mondial-relay-label.url');
        $this->brand_id_api = config('mondial-relay-label.brand_id_api');
        $this->login = config('mondial-relay-label.login');
        $this->password = config('mondial-relay-label.password');
    }

    public function generateLabels($shipmentData = [])
    {
        $xmlRequest = $this->buildRequest($shipmentData);

        // Send request to Api
        $response = $this->sendRequest($xmlRequest);

        // Handle response
        $xml = $this->handleResponse($response);

        $urls = $this->extractUrls($xml);

        return $urls;
    }

    private function buildRequest($shipmentData = []): string
    {

        // check shipment data
        $this->checkShipmentData($shipmentData);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><ShipmentCreationRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://www.example.org/Request"/>');

        // Ajout des informations de contexte
        $context = $xml->addChild('Context');
        $context->addChild('Login', $this->login);
        $context->addChild('Password', $this->password);
        $context->addChild('CustomerId', $this->brand_id_api);
        $context->addChild('Culture', 'fr-FR');
        $context->addChild('VersionAPI', '1.0');

        // Ajout des options de sortie
        $outputOptions = $xml->addChild('OutputOptions');
        $outputOptions->addChild('OutputFormat', '10x15'); // A4, A5,
        $outputOptions->addChild('OutputType', 'PdfUrl');

        $ShipmentsList = $xml->addChild('ShipmentsList');
        // Ajout des informations d'expédition
        $shipment = $ShipmentsList->addChild('Shipment');

        foreach ($shipmentData as $key => $value) {

            if (is_array($value)) {
                $node = $shipment->addChild($key);
                $this->arrayToXml($node, $value);
            } else {
                // if key contains __ then the second element is an attribute of the first element and value is the value of the attribute
                if (strpos($key, '__') !== false) {
                    $key = explode('__', $key);
                    // check if $key[0] is already present in the xml
                    if ($shipment->{$key[0]}) {
                        $shipment->{$key[0]}->addAttribute($key[1], htmlspecialchars($value));
                    } else {
                        $shipment->addChild($key[0])->addAttribute($key[1], htmlspecialchars($value));
                    }
                } else {
                    $shipment->addChild($key, htmlspecialchars($value));
                }
            }
        }

        return $xml->asXML();
    }

    public function checkShipmentData($shipmentData = [])
    {
        $shipmentDataRules = [
            'OrderNo' => 'string',
            'CustomerNo' => 'string',
            'ParcelCount' => 'required|integer',
            'DeliveryMode__Mode' => 'required|string', // CCC, CDR, CDS, REL, LCC, HOM, HOC, 24R; 24L,  XOH
            'DeliveryMode__Location' => 'string',
            'CollectionMode__Mode' => 'required|string', // CCC, CDR, CDS, REL, LCC, HOM, HOC, 24R; 24L,  XOH
            'CollectionMode__Location' => 'string',
            'Parcels.Parcel.Content' => 'string',
            'Parcels.Parcel.Weight__Value' => 'required|numeric|min:10',
            'Parcels.Parcel.Weight__Unit' => 'string',
            'Parcels.DeliveryInstruction' => 'string',
            'Sender.Address.Title' => 'string',
            // Sender.Address.Firstname required if Sender.Address.AddressAdd1 is not present
            'Sender.Address.Firstname' => 'required|string',
            'Sender.Address.Lastname' => 'required|string',
            'Sender.Address.Streetname' => 'required|string',
            'Sender.Address.HouseNo' => 'string',
            'Sender.Address.CountryCode' => 'required|string',
            'Sender.Address.PostCode' => 'required|string',
            'Sender.Address.City' => 'required|string',
            'Sender.Address.AddressAdd1' => 'string',
            'Sender.Address.AddressAdd2' => 'string',
            'Sender.Address.AddressAdd3' => 'string',
            'Sender.Address.PhoneNo' => 'string',
            'Sender.Address.MobileNo' => 'string',
            'Sender.Address.Email' => 'string',
            'Recipient.Address.Title' => 'string',
            'Recipient.Address.Firstname' => 'required|string',
            'Recipient.Address.Lastname' => 'required|string',
            'Recipient.Address.Streetname' => 'required|string',
            'Recipient.Address.HouseNo' => 'string',
            'Recipient.Address.CountryCode' => 'required|string',
            'Recipient.Address.PostCode' => 'required|string',
            'Recipient.Address.City' => 'required|string',
            'Recipient.Address.AddressAdd1' => 'string',
            'Recipient.Address.AddressAdd2' => 'string',
            'Recipient.Address.AddressAdd3' => 'string',
            'Recipient.Address.PhoneNo' => 'string',
            'Recipient.Address.MobileNo' => 'string',
            'Recipient.Address.Email' => 'string',
        ];

        $validator = Validator::make($shipmentData, $shipmentDataRules);

        return $validator->validate();

    }

    private function arrayToXml(?SimpleXMLElement $node, array $value)
    {
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                $this->arrayToXml($node->addChild($key), $val);
            } else {
                if (strpos($key, '__') !== false) {
                    $key = explode('__', $key);
                    if ($node->{$key[0]}) {
                        $node->{$key[0]}->addAttribute($key[1], htmlspecialchars($val));
                    } else {
                        $node->addChild($key[0])->addAttribute($key[1], htmlspecialchars($val));
                    }
                } else {
                    $node->addChild($key, htmlspecialchars($val));
                }
            }
        }

        return $node;
    }

    private function sendRequest($xmlRequest): object
    {
        return (new Client)->post($this->url, [
            'headers' => [
                'Content-Type' => 'application/xml',
                'Accept' => 'application/xml',
            ],
            'body' => $xmlRequest,
        ]);
    }

    private function handleResponse(Response $response): SimpleXMLElement
    {
        if ($response->getStatusCode() === 200) {
            $xmlResponse = new SimpleXMLElement($response->getBody()->getContents());

            return $xmlResponse;
        }
        throw new Exception('Error while generating label');
    }

    private function extractUrls(SimpleXMLElement $xml): array
    {
        try {
            $urls = [];
            foreach ($xml->ShipmentsList->Shipment as $shipment) {
                $urls[] = (string) $shipment->LabelList->Label->Output;
            }
        } catch (Exception $e) {
            throw new Exception('Error while extracting urls');
        }

        return $urls;
    }
}
