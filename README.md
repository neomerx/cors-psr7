[![Project Management](https://img.shields.io/badge/project-management-blue.svg)](https://waffle.io/neomerx/cors-psr7)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/neomerx/cors-psr7/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/neomerx/cors-psr7/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/neomerx/cors-psr7/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/neomerx/cors-psr7/?branch=master)
[![Build Status](https://travis-ci.org/neomerx/cors-psr7.svg?branch=master)](https://travis-ci.org/neomerx/cors-psr7)
[![HHVM](https://img.shields.io/hhvm/neomerx/cors-psr7.svg)](https://travis-ci.org/neomerx/cors-psr7)
[![License](https://img.shields.io/packagist/l/neomerx/cors-psr7.svg)](https://packagist.org/packages/neomerx/cors-psr7)

## Description

This package has framework agnostic [Cross-Origin Resource Sharing](http://www.w3.org/TR/cors/) (CORS) implementation. It is complaint with [PSR-7](http://www.php-fig.org/psr/psr-7/) HTTP message interfaces.

Why this package?

- Implementation is based on latest [CORS specification](http://www.w3.org/TR/cors/).
- Works with [PSR-7 HTTP message interfaces](http://www.php-fig.org/psr/psr-7/).
- Supports debug mode with [PSR-3 Logger Interface](http://www.php-fig.org/psr/psr-3/).
- Flexible, modular and extensible solution.
- High code quality. **100%** test coverage.
- Free software license [Apache 2.0](LICENSE).

## Sample usage

The package is designed to be used as a middleware. Typical usage

```php
use \Neomerx\Cors\Analyzer;
use \Psr\Http\Message\RequestInterface;
use \Neomerx\Cors\Contracts\AnalysisResultInterface;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param RequestInterface $request
     * @param Closure          $next
     *
     * @return mixed
     */
    public function handle(RequestInterface $request, Closure $next)
    {
        $cors = Analyzer::instance($this->getCorsSettings())->analyze($request);
        
        switch ($cors->getRequestType()) {
            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                // return 4XX HTTP error
                return ...;

            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                $corsHeaders = $cors->getResponseHeaders();
                // return 200 HTTP with $corsHeaders
                return ...;

            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                // call next middleware handler
                return $next($request);
            
            default:
                // actual CORS request
                $response    = $next($request);
                $corsHeaders = $cors->getResponseHeaders();
                
                // add CORS headers to Response $response
                ...
                return $response;
        }
    }
}
```

## Install

```
composer require neomerx/cors-psr7
```

## Debug Mode

Debug logging will provide a detailed step-by-step description of how requests are handled. In order to activate it a [PSR-3 compatible Logger](http://www.php-fig.org/psr/psr-3/) should be set to `Analyzer`.

```php
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger   = ...;
    /** @var \Psr\Http\Message\RequestInterface $request */
    $request  = ...;
    /** @var \Neomerx\Cors\Contracts\Strategies\SettingsStrategyInterface $settings */
    $settings = ...;

    $analyzer = Analyzer::instance($settings);
    $analyzer->setLogger($logger)
    $cors     = $analyzer->analyze($request);
```

## Advanced Usage

There are many possible strategies for handling cross and same origin requests which might and might not depend on data from requests.

This package has built-in strategy called `Settings` which implements simple settings identical for all requests (same list of allowed origins, same allowed methods for all requests and etc).

However you can customize such behaviour. For example you can send different sets of allowed methods depending on request. This might be helpful when you have some kind of Access Control System and wish to differentiate response based on request (for example on its origin). You can either implement `AnalysisStrategyInterface` from scratch or override methods in `Settings` class if only a minor changes are needed to `Settings`. The new strategy could be sent to `Analyzer` constructor or `Analyzer::instance` method could be used for injection.

Example

```php
class CustomMethodsSettings extends Settings
{
    public function getRequestAllowedMethods(
        RequestInterface $request,
        $requestMethod
    ) {
        // An external Access Control System could be used to determine
        // which methods are allowed for this request.
        
        return ...;
    }
}

$cors = Analyzer::instance(new CustomMethodsSettings())->analyze($request);
```

## Testing

```
composer test
```

## Questions?

Do not hesitate to contact us on [![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/neomerx/json-api) or post an [issue](https://github.com/neomerx/cors-psr7/issues).

## Contributing

If you have spotted any compliance issues with the [CORS Recommendation](http://www.w3.org/TR/cors/) please post an [issue](https://github.com/neomerx/cors-psr7/issues). Pull requests for documentation and code improvements (PSR-2, tests) are welcome.

Current tasks are managed with [Waffle.io](https://waffle.io/neomerx/cors-psr7).

## Versioning

This package is using [Semantic Versioning](http://semver.org/).

## License

Apache License (Version 2.0). Please see [License File](LICENSE) for more information.
