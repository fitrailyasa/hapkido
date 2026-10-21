#!/bin/sh
#
# Health check for the PHP-FPM container.
#
# The check succeeds when the PHP-FPM configuration is valid and the FastCGI
# master process accepts connections on port 9000.
#
set -e

# The PHP-FPM configuration has to be valid.
php-fpm -t > /dev/null 2>&1

# The FastCGI master process has to accept connections.
php -r 'exit(@fsockopen("127.0.0.1", 9000, $errno, $errstr, 3) ? 0 : 1);'
