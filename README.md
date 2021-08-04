PSR-15 Content Validation / PSR-15 内容验证器
===========================

[![GitHub Actions: Run tests](https://github.com/zfegg/content-validation/workflows/qa/badge.svg)](https://github.com/zfegg/content-validation/actions?query=workflow%3A%22qa%22)
[![Coverage Status](https://coveralls.io/repos/github/zfegg/content-validation/badge.svg?branch=master)](https://coveralls.io/github/zfegg/content-validation?branch=master)
[![Latest Stable Version](https://poser.pugx.org/zfegg/content-validation/v/stable.png)](https://packagist.org/packages/zfegg/content-validation)

Content validation for PSR-15 middleware or laminas-mvc listener. 
Based on [`laminias/laminas-inputfilter`](https://github.com/laminas/laminas-inputfilter).


用于 PSR-15 中间件或 laminas-mvc 的内容验证。
内容验证使用 [`laminias/laminas-inputfilter`](https://github.com/laminas/laminas-inputfilter)组件.


* `ContentValidationMiddleware` PSR-15 Middleware / PSR-15 中间件
* `ContentValidationListener`  laminias-mvc listener / laminas-mvc 监听器


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

Add input filter config. / 添加 input filter 配置.

```php
return [
    'input_filter_specs' => [
        'api.users.create' => [
            ['name' => 'username',],
            ['name' => 'age',],
        ]
    ],
];
```

Get input filter in handler. / 在处理程序中获取 input filter.

```php
$app->post(
  '/api/users', 
   [
   \Zfegg\ContentValidation\RouteNameContentValidationMiddleware::class,
    function (\Psr\Http\Message\ServerRequestInterface $request) {
        /** @var \Laminas\InputFilter\InputFilterInterface $filter */
        $filter = $request->getAttribute('input_filter');
        $data = $filter->getValues(); // Get valid data.
    }
], 'api.users.create');
```

Invalid request will response status 422. / 无效请求将响应 422状态码.

```shell
curl "http://host/api/users" -d 'username=foo'

HTTP/1.1 422

{
  "status": 422,
  "detail": "Failed Validation",
  "validation_messages": {
    "age": {
      "isNotEmpty": "Required message."
    }
  }
}
```
