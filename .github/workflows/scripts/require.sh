#!/bin/bash
cd $MAGENTO_ROOT
composer require $COMPOSER_NAME:$GITHUB_SHA --no-update --no-interaction