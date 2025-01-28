<?php

/** @noinspection NullPointerExceptionInspection */

namespace Humantocomputer\MondialRelayLabel;

use Exception;
use Illuminate\Support\Facades\Http;
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

    public function generateLabel($shipmentData = [])
    {
        $xmlRequest = $this->buildRequest($shipmentData);

        // Send request to Api
        $response = $this->sendRequest($xmlRequest);
        // Handle response
        return $this->handleResponse($response);
    }

    private function buildRequest($shipmentData = []): string
    {

        //check shipment data
        $this->checkShipmentData($shipmentData);

        $xml = new SimpleXMLElement('<ShipmentCreationRequest/>');
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
                $this->arrayToXml($shipment, $value);
            } else {
                $shipment->addChild($key, $value);
            }
        }

        return $xml->asXML();
    }

    private function sendRequest($xmlRequest): object
    {
        return Http::withHeaders([
            'Content-Type' => 'application/xml',
            'Accept' => 'application/xml',
        ])->post($this->url, $xmlRequest);
    }

    private function handleResponse($response): string
    {
        $xml = simplexml_load_string($response);
        // Vérification des erreurs
        if (isset($xml->Status->Code) && $xml->Status->Code != '0') {
            throw new Exception('Erreur API Mondial Relay : '.$xml->Status->Message);
        }

        return $xml;
        // Récupération de l'étiquette en base64
        if (isset($xml->Shipment->Label)) {
            return base64_decode($xml->Shipment->Label);
        }

        throw new Exception('Étiquette non disponible dans la réponse de l\'API.');
    }

    public function checkShipmentData($shipmentData = [])
    {
        $shipmentDataRules = [
            'OrderNo' => 'string',
            'CustomerNo' => 'string',
            'parcelCount' => 'integer',
            'DeliveryMode__Mode' => 'required|string', // CCC, CDR, CDS, REL, LCC, HOM, HOC, 24R; 24L,  XOH
            'DeliveryMode__Location' => 'string',
            'CollectionMode__Mode' => 'required|string', // CCC, CDR, CDS, REL, LCC, HOM, HOC, 24R; 24L,  XOH
            'CollectionMode__Location' => 'string',
            'Parcels.Parcel.Content' => 'string',
            'Parcels.Parcel.Weight__Value' => 'required|numeric|min:10',
            'Parcels.Parcel.Weight__Unit' => 'string',
            'Parcels.DeliveryInstruction' => 'string',
            'Parcels.Sender.Address.Title' => 'string',
            'Parcels.Sender.Address.Firstname' => 'required|string',
            'Parcels.Sender.Address.Lastname' => 'required|string',
            'Parcels.Sender.Address.Streetname' => 'required|string',
            'Parcels.Sender.Address.HouseNo' => 'string',
            'Parcels.Sender.Address.CountryCode' => 'required|string',
            'Parcels.Sender.Address.PostCode' => 'required|string',
            'Parcels.Sender.Address.City' => 'required|string',
            'Parcels.Sender.Address.AddressAdd1' => 'required|string',
            'Parcels.Sender.Address.AddressAdd2' => 'string',
            'Parcels.Sender.Address.AddressAdd3' => 'string',
            'Parcels.Sender.Address.PhoneNo' => 'string',
            'Parcels.Sender.Address.MobileNo' => 'string',
            'Parcels.Sender.Address.Email' => 'string',
            'Parcels.Recipient.Address.Title' => 'string',
            'Parcels.Recipient.Address.Firstname' => 'required|string',
            'Parcels.Recipient.Address.Lastname' => 'required|string',
            'Parcels.Recipient.Address.Streetname' => 'required|string',
            'Parcels.Recipient.Address.HouseNo' => 'string',
            'Parcels.Recipient.Address.CountryCode' => 'required|string',
            'Parcels.Recipient.Address.PostCode' => 'required|string',
            'Parcels.Recipient.Address.City' => 'required|string',
            'Parcels.Recipient.Address.AddressAdd1' => 'required|string',
            'Parcels.Recipient.Address.AddressAdd2' => 'string',
            'Parcels.Recipient.Address.AddressAdd3' => 'string',
            'Parcels.Recipient.Address.PhoneNo' => 'string',
            'Parcels.Recipient.Address.MobileNo' => 'string',
            'Parcels.Recipient.Address.Email' => 'string',
        ];

        $validator = Validator::make($shipmentData, $shipmentDataRules);

        return $validator->validate();


    }

    private function arrayToXml(?SimpleXMLElement $shipment, array $value)
    {
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                $this->arrayToXml($shipment->addChild($key), $val);
            } else {
                $shipment->addChild($key, $val);
            }
        }

        return $shipment;
    }
}
