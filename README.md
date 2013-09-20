ZF2 Deploy Module, v1.0
=======

[![Build Status](https://travis-ci.org/neilime/zf2-deploy-module.png?branch=master)](https://travis-ci.org/neilime/zf2-deploy-module)
[![Latest Stable Version](https://poser.pugx.org/neilime/zf2-deploy-module/v/stable.png)](https://packagist.org/packages/neilime/zf2-deploy-module)
[![Total Downloads](https://poser.pugx.org/neilime/zf2-deploy-module/downloads.png)](https://packagist.org/packages/neilime/zf2-deploy-module)
![Code coverage](https://raw.github.com/zf2-boiler-app/app-test/master/ressources/100%25-code-coverage.png "100% code coverage")

NOTE : If you want to contribute don't hesitate, I'll review any PR.

Introduction
------------

ZF2 Deploy Module is a Zend Framework 2 provides tools for deploying ZF2 module into a ZendSkeletonApplication to display / tests module's views. 
It is useful for ZF2 modules developers, in order to render modules views (for humans, selenium...)

Contributing
------------

If you wish to contribute to ZF2 Deploy Module, please read both the [CONTRIBUTING.md](CONTRIBUTING.md) file.

Requirements
------------

* [Zend Framework 2](https://github.com/zendframework/zf2) (2.*)

## Installation

### Main Setup

#### By cloning project

1. Clone this project into your `./vendor/` directory.

#### With composer

1. Add this project in your composer.json:

    ```json
    "require": {
        "neilime/zf2-deploy-module": "dev-master"
    }
    ```

2. Now tell composer to download __ZF2 Deploy Module by running the command:

    ```bash
    $ php composer.phar update
    ```

#### Post installation

1. Enabling it in your `application.config.php`file.

    ```php
    <?php
    return array(
        'modules' => array(
            // ...
            'Neilime\ZF2DeployModule',
        ),
        // ...
    );
    ```

## Configuration

 * string `zend_skeleton_application_path` : Define the ZendSkeletonApplication file path, allows locale or remote directory, allows archive (Bzip2, LZF, Phar, Rar, Zip, Zlib) depending on PHP installed libraries
 
## How to use _ZF2 Deploy Module_

_ZF2 Deploy Module_ provides console tools.

### Features

    Deploying module into the given directory (ex: apache "www" directory)

### Usage

#### Deploying module

    php public/index.php deploy-module --dir my-dir/www/my-project
