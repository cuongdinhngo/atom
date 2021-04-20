# Atom - A Simple PHP Framework

Atom is a simple PHP framework that implements MVC pattern.
Please read more [`Request Lifecycle Atom`](https://www.linkedin.com/pulse/atom-simple-php-framework-implements-mvc-pattern-cuong-dinh-ngo)

# Configuration
<pre>
composer require cuongnd88/atom
</pre>
# Usage
## Single Point Entry
#### index.php
<pre>
require __DIR__ . '/../vendor/autoload.php';

use Atom\Http\Server;

try {
    $server = new Server(['env']);
    $server->handle();
} catch (Exception $e) {
    echo $e->getMessage();
}
</pre>

For example of implementation and usage, please take a look at EzyCrazy project https://github.com/cuongnd88/ezycrazy which was developed using Atom framework
