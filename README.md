# curl-client

Client HTTP utilisant CUrl

## Installation

```shell
composer require unofficialmc2/http-client
```

## Utilisation

### `HttpClient`

#### Requête synchrone

```php
<?php
$logger = new \Monolog\Logger('test');
$client = new \HttpClient\HttpClient($logger);
$response = $client->curlUnique('https://exemple.net/path', \HttpClient\HttpMethod::GET, []);
$response->isSuccess(); // true
$response->isRedirect(); // false
$response->isCode(404); // false
$response->getHeaders(); // [ ... ]
$response->getHeader('type-content'); // 'application/json'
$response->getData(); // ['message' => 'lorem ipsum']
$response->getData(true); // ['message' => 'lorem ipsum']
$response->getData(false); // {"message": "lorem ipsum"}
```

#### Requête asynchrone

```php
<?php
$logger = new \Monolog\Logger('test');
$client = new \HttpClient\HttpClient($logger);
$refRequest1 = $client->addParamRequest('https://exemple.net/info', \HttpClient\HttpMethod::GET, []);
$refRequest2 = $client->addParamRequest('https://exemple.com/info', \HttpClient\HttpMethod::GET, []);
$client->execAll();
$client->waitResult();
$response = $client->getResult($refRequest1);
$response->isSuccess(); // true
$response = $client->getResult($refRequest2);
$response->isSuccess(); // true
```

### `HttpClient` Stub

#### Requête synchrone

```php
<?php
$client = new \HttpClientStub\HttpClient();
$client->addResult(
    200,
    ['type-content'=>'application/json'],
    ['message' => 'lorem ipsum']
);
$response = $client->curlUnique('https://exemple.net/path', \HttpClient\HttpMethod::GET, []);
$response->isSuccess(); // true
```

On peut enregistrer plusieures résultats pour un client. Les résultats seront retournés tour à tour en boucle.

#### Requête asynchrone

```php
<?php
$client = new \HttpClientStub\HttpClient();
$client->addResult(
    200,
    ['type-content'=>'application/json'],
    json_encode(['message' => 'lorem ipsum'])
);
$client->addResult(false, [], '');
$refRequest1 = $client->addParamRequest('https://exemple.net/info', \HttpClient\HttpMethod::GET, []);
$refRequest2 = $client->addParamRequest('https://exemple.com/info', \HttpClient\HttpMethod::GET, []);
$client->execAll();
$client->waitResult();
$response = $client->getResult($refRequest2);
$response->isSuccess(); // false
$response = $client->getResult($refRequest1);
$response->isSuccess(); // true
```

On peut enregistrer plusieurs résultats pour un client. Les résultats seront affectés tour à tour en boucle aux requêtes ajoutées.
