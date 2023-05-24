PSR-15 Content Validation
===========================

[简体中文](README-zh.md)


[![GitHub Actions: Run tests](https://github.com/zfegg/content-validation/workflows/qa/badge.svg)](https://github.com/zfegg/content-validation/actions?query=workflow%3A%22qa%22)
[![Coverage Status](https://coveralls.io/repos/github/zfegg/content-validation/badge.svg?branch=master)](https://coveralls.io/github/zfegg/content-validation?branch=master)
[![Latest Stable Version](https://poser.pugx.org/zfegg/content-validation/v/stable.png)](https://packagist.org/packages/zfegg/content-validation)

Content validation for PSR-15 middleware. 
Based on [`opis/json-schema`](https://packagist.org/packages/opis/json-schema).

Installation
-----------------------

Install via composer.

```bash
composer require zfegg/content-validation
```

Usage
--------------

### `Opis\JsonSchema\Validator` factory config.

```php
// config.php
return [
    Opis\JsonSchema\Validator::class => [
        'resolvers' => [
            'protocolDir' => [
                // foo-schema://host/foo.create.json => schema/dir/foo.create.json
                ['foo-schema', 'host',  'schema/dir'],
            ],
            'protocol' => [
            ],
            'prefix' => [
               ['prefix1', 'path/to/dir'],
               ['prefix2', 'path/to/dir'],
            ],
            'file' => [
               ['SchemaFoo', 'path/to/file'],
               ['SchemaBar', 'path/to/file2'],
            ],
            'raw' => [
               ['{"type":"object", ...}', 'schema id 1'],
               ['{"type":"object", ...}', 'schema id 2'],
            ]
        ],
        'filters' => [
            'foo-filter' => ['filter' => 'FilterFilterName', 'types' => ['integer']],
        ],
        'filtersNS' => [
            'foo-ns' => 'FilterResolverName',
        ],
    ]
]
```

### Mezzio

Add `ConfigProvider` in 'config.php'.

```php

$aggregator = new ConfigAggregator(
  [
    // ...
    \Zfegg\ContentValidation\ConfigProvider::class,
  ]
);

return $aggregator->getMergedConfig();
```


```php
$app->post(
  '/api/users', 
   [
   \Zfegg\ContentValidation\ContentValidationMiddleware::class,
    function (\Psr\Http\Message\ServerRequestInterface $request) {
        $data = $request->getParsedBody(); // Get valid data.
    }
], 'api.users.create')
->setOptions(['schema' => 'path-to-json-schema.json'])
//->setOptions([  
//   // or set json-schema object. 
//  'schema' => (object) [
//        'type' => 'object',
//        'properties' => (object) [
//             'age' => (object) [
//                 'type' => 'integer'
//              ]
//        ],
//        'required' => ['age']
//   ]
// ])
;
```

Invalid request will response status 422.

```shell
curl "http://host/api/users" -d 'username=foo'

HTTP/1.1 422

{
  "status": 422,
  "detail": "Failed Validation",
  "validation_messages": {
    "age": [
      "The required properties (age) are missing"
    ]
  }
}
```


### Slim 

```php
$app->post(
  '/api/users', 
  function (\Psr\Http\Message\ServerRequestInterface $request) {
        $data = $request->getParsedBody(); // Get valid data.
  }
)
->add(\Zfegg\ContentValidation\ContentValidationMiddleware::class)
->setArgument('schema', 'path-to-json-schema.json')
;
```


Validators
----------

- [`DbalRecordExistsFilter`](src/Opis/Filter/DbalRecordExistsFilter.php): Use `doctrine/dbal` to check record exists.
  The json-schema `$filters` config:
  ```json5
  {
      "$func": "dbal-exists",
      "$vars": {
        "db": "db",          // Get DBAL object by container.
        "sql": "select ...", // Set custom SQL
        "table": "foo",      // Table name
        "field": "key",      // Field name
        "exists": true       // Check record exists or not exists. 
      }
  }
  ```
- [`DoctrineRecordExistsFilter`](src/Opis/Filter/DoctrineRecordExistsFilter.php): Use `doctrine/orm` to check record exists.
  The json-schema `$filters` config:
  ```json5
  {
      "$func": "orm-exists",
      "$vars": {
        "db": "orm.default",   // Get ORM object by container.
        "dql": "select ...",   // Set custom DQL
        "entity": "Foo",       // Entity name
        "field": "key",        // Field name
        "exists": true         // Check record exists or not exists. 
      }
  }
  ```
- [`RecordExistsFilter`](src/Opis/Filter/RecordExistsFilter.php): Use `PDO` to check record exists.
  The json-schema `$filters` config:
  ```json5
  {
      "$func": "db-exists",
      "$vars": {
        "db": "db",          // Get DBAL object by container.
        "sql": "select ...", // Set custom SQL
        "table": "foo",      // Table name
        "field": "key",      // Field name
        "exists": true       // Check record exists or not exists. 
      }
  }
  ```