ZF2 Deploy Module, v1.0
=======

[![Build Status](https://travis-ci.org/neilime/zf2-deploy-module.png?branch=master)](https://travis-ci.org/neilime/zf2-deploy-module)
[![Latest Stable Version](https://poser.pugx.org/neilime/zf2-deploy-module/v/stable.png)](https://packagist.org/packages/neilime/zf2-deploy-module)
[![Total Downloads](https://poser.pugx.org/neilime/zf2-deploy-module/downloads.png)](https://packagist.org/packages/neilime/zf2-deploy-module)
![Code coverage](https://raw.github.com/zf2-boiler-app/app-test/master/ressources/100%25-code-coverage.png "100% code coverage")

NOTE : If you want to contribute don't hesitate, I'll review any PR.

Introduction
------------

__ZF2 Deploy Module__ is a Zend Framework 2 provides tools for deploying ZF2 modules into a ZendSkeletonApplication to display / tests module's views. 
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
    "require_dev": {
        "neilime/zf2-deploy-module": "dev-master"
    }
    ```
    
    Or
    
    ```json
    "require": {
        "neilime/zf2-deploy-module": "dev-master"
    }
    ```

2. Now tell composer to download __ZF2 Deploy Module__ by running the command:

    ```bash
    $ php composer.phar update
    ```
 
## How to use _ZF2 Deploy Module_

_ZF2 Deploy Module_ provides console tools.

### Usage

    ```bash
    php ./vendor/bin/deploy_module.php [args]
    ```
    
### Arguments
    
    --help|-h                   Get usage message
    --module|-m [ <string> ] 	Module to deploy; if none provided, assumes current directory
    --dir|-d [ <string> ]    	Directory path where to deploy the module (ex: apache/www/my-module) the directory could be created if needed
    --modules|-a [ <string> ]	(optionnal) Additionnal module namespaces (comma separated) to be used in the application
    --app|-z [ <string> ]   	(optionnal) ZendSkeletonApplication file path, allows locale or remote directory, allows archive (Phar, Rar, Zip) depending on PHP installed libraries
    --composer|-c [ <string> ]  (optionnal) Composer.phar file path, allows locale or remote directory
    --overwrite|-w 				Whether or not to overwrite existing ZendSkeletonApplication
    --verbose|-v 				Whether or not to display trace string when an error occured 
    
## Exemple
 
### Deploy [ZfcUser](https://github.com/ZF-Commons/ZfcUser) to run it with EasyPhp (windows)
 
 This exemple expects that EasyPhp & PHP is installed on windows, "www" EasyPhp directory is in "C:\Program Files\EasyPHP-DevServer\data\localweb". 

1. Install __ZF2 Deploy Module__ into the __ZfcUser__ project as explain above

2. Deploy module into "www\ZfcUser"
    ```bash
    cd path\to\ZfcUser\directory
    php ./vendor/bin/deploy_module.php -d "C:\Program Files\EasyPHP-DevServer\data\localweb\ZfcUser" -m ZfcBase
    ```
    
3. Display it in your browser 
    Go to http://127.0.0.1/ZfcUser
 
### Deploy a module for Selenium tests with "travis-ci.org"
 
1. Edit your .travis.yml
    ```yml
    
    ```

2. Run the build
 
 
