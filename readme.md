# ReactPHP retrier

A simple package to retry a reactphp promise several times.

### Example

```php
use Exan\Retrier\Retrier;

$retrier = new Retrier();

$result = $retrier->retry(3, fn () => new Promise(function ($resolve, $reject) {
    // This will be executed up to 3 times

    $resolve('Success!');
}));

$result->then(function ($res) {
    echo $res; // 'Success!'
});
```

# Supported PHP versions
- 8.1
- 8.2
- 8.3
