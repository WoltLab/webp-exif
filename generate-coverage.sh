#!/bin/bash
set -e

XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html ./coverage

