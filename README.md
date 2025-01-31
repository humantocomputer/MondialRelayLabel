# MondialRelayLabel

## Installation

Pour installer le package, utilisez Composer :

```bash
composer require humantocomputer/mondial-relay-label
```

## Configuration

Start by registering the package's the service provider
```php
// config/app.php

'providers' => [
    ...
    Humantocomputer\MondialRelayLabel\MondialRelayLabelServiceProvider::class,
],
```



Après l'installation, publiez le fichier de configuration avec la commande suivante :

```bash
php artisan vendor:publish --provider="HumanToComputer\MondialRelayLabel\MondialRelayLabelServiceProvider"
```
Cela créera un fichier de configuration mondial-relay-label.php dans le répertoire config.

Remplissez les informations d'identification de Mondial Relay dans le fichier de configuration.

```php
## Utilisation

Pour générer une étiquette, vous pouvez utiliser la méthode `generate` de la classe `MondialRelayLabel` :

```php
use HumanToComputer\MondialRelayLabel\MondialRelayLabel;

// Créez une instance de MondialRelayLabel
$mondialRelay = new MondialRelayLabel();

$shipmentData = [
            'OrderNo' => 'Order number (optional)',
            'CustomerNo' => 'Customer number (optional)',
            'ParcelCount' => 'Nombre de colis (optional)',
            'DeliveryMode__Mode' => '24R', // CCC, CDR, CDS, REL, LCC, HOM, HOC, 24R; 24L,  XOH
            'DeliveryMode__Location' => '(optional)',
            'CollectionMode__Mode' => 'CCC', // CCC, CDR, CDS, REL, LCC, HOM, HOC, 24R; 24L,  XOH
            'CollectionMode__Location' => '(optional)',
            'Parcels' => [
                'Parcel' => [
                    'Content' => 'Parcel content descirption (optional)',
                    'Weight__Value' => 'required|numeric|min:10',
                    'Weight__Unit' => 'gr', //required gr mandatory
                ],
                'DeliveryInstruction' => '(optional)',
            ],
            'Sender' => [
                'Address' => [
                    'Title' => 'required|string',
                    'Firstname' => 'required|string',
                    'Lastname' => 'required|string',
                    'Streetname' => 'required|string',
                    'HouseNo' => '(optional)',
                    'CountryCode' => 'required|string',
                    'PostCode' => 'required|string',
                    'City' => 'required|string',
                    'AddressAdd1' => '(optional) Name. Do not fill it if Firstname/Lastname are
filled',
                    'AddressAdd2' => '(optional)',
                    'AddressAdd3' => '(optional)',
                    'PhoneNo' => '(optional)',
                    'MobileNo' => '(optional)',
                    'Email' => '(optional)',
                ],
            ],
            'Recipient' => [
                'Address' => [
                    'Title' => 'required|string',
                    'Firstname' => 'required|string',
                    'Lastname' => 'required|string',
                    'Streetname' => 'required|string',
                    'HouseNo' => '(optional)',
                    'CountryCode' => 'required|string',
                    'PostCode' => 'required|string',
                    'City' => 'required|string',
                    'AddressAdd1' => '(optional) Name. Do not fill it if Firstname/Lastname are
filled',
                    'AddressAdd2' => '(optional)',
                    'AddressAdd3' => '(optional)',
                    'PhoneNo' => '(optional)',
                    'MobileNo' => '(optional)',
                    'Email' => '(optional)',
                ],
            ],
        ];
        
// Générez une étiquette
$label = $mondialRelay->generateLabel($shipmentData);

// Affichez ou téléchargez l'étiquette
echo $label;

// Pour télécharger l'étiquette
file_put_contents('label.pdf', $label);
```

