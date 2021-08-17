#!/bin/bash
cd $MAGENTO_ROOT
composer require $COMPOSER_NAME:dev-dev#$GITHUB_SHA --no-update --no-interaction