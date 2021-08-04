PSR-15 Content Validation / PSR-15 内容验证器
===========================

[![GitHub Actions: Run tests](https://github.com/zfegg/content-validation/workflows/qa/badge.svg)](https://github.com/zfegg/content-validation/actions?query=workflow%3A%22qa%22)
[![Coverage Status](https://coveralls.io/repos/github/zfegg/content-validation/badge.svg?branch=master)](https://coveralls.io/github/zfegg/content-validation?branch=master)
[![Latest Stable Version](https://poser.pugx.org/zfegg/content-validation/v/stable.png)](https://packagist.org/packages/zfegg/content-validation)

Content validation for PSR-15 middleware. 
Based on [`opis/json-schema`](https://packagist.org/packages/opis/json-schema).


基于 PSR-15 内容验证中间件。
内容验证使用 [`opis/json-schema`](https://packagist.org/packages/opis/json-schema).

Installation / 安装使用
-----------------------

Install via composer.

```bash
composer require zfegg/content-validation
```

Usage / 使用
--------------

### Mezzio

Add `ConfigProvider` in 'config.php'. / 在 `config.php` 中添加 `ConfigProvider`.

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

Invalid request will response status 422. / 无效请求将响应 422状态码.

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