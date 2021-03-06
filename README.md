ZF2 Deploy Module
=======

__⚠️ This module is for Zend Framework 2, it is deprecated ⚠️__ 

[![Build Status](https://travis-ci.org/neilime/zf2-deploy-module.png?branch=master)](https://travis-ci.org/neilime/zf2-deploy-module)
[![Latest Stable Version](https://poser.pugx.org/neilime/zf2-deploy-module/v/stable.png)](https://packagist.org/packages/neilime/zf2-deploy-module)
[![Total Downloads](https://poser.pugx.org/neilime/zf2-deploy-module/downloads.png)](https://packagist.org/packages/neilime/zf2-deploy-module)

NOTE : If you want to contribute don't hesitate, I'll review any PR.

Introduction
------------

__ZF2 Deploy Module__ provides tools for deploying ZF2 modules into a ZendSkeletonApplication to display / tests module's views. 
It is useful for ZF2 modules developers, in order to render modules views (for humans, selenium...).

The benefit of this tool is that it does not change the module to deploy (no moving / changing / adding files), it manages the autoloading, [composer](http://getcomposer.org/) (install / update), and adding the module(s) in the application configuration.

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
        "neilime/zf2-deploy-module": "1.*"
    }
    ```
    
    Or
    
    ```json
    "require": {
        "neilime/zf2-deploy-module": "1.*"
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
    --module|-m [ <string> ] 	Module path to deploy; if none provided, assumes current directory
    --dir|-d [ <string> ]    	Directory path where to deploy the module (ex: apache/www/my-module), the directory could be created if needed
    --modules|-a [ <string> ]	(optionnal) Additionnal module namespaces (comma separated) to be used in the application
    --zapp|-z [ <string> ]   	(optionnal) ZendSkeletonApplication file path, allows locale or remote directory, allows archive (Phar, Rar, Zip) depending on PHP installed libraries
    --composer|-c [ <string> ]  (optionnal) Composer.phar file path, allows locale or remote directory
    --overwrite|-w 				Whether or not to overwrite existing deployed ZendSkeletonApplication
    --verbose|-v 				Whether or not to display execution trace
    
## Exemple
 
### Deploy a module to run it with EasyPhp (windows)
 
This exemple expects :  
- EasyPhp & PHP is installed on windows
- A virtual host named "www.test-module.com" redirect to DocumentRoot "C:\Program Files\EasyPHP-DevServer\data\localweb\TestModule\public"

1. Install __ZF2 Deploy Module__ into the your module project as explain above

2. Deploy module into EasyPhp "\TestModule"
    ```bash
    cd path\to\your\module\directory
    php ./vendor/bin/deploy_module.php -d "C:\Program Files\EasyPHP-DevServer\data\localweb\TestModule" -v
    ```
    
3. Display it in your browser 
    Go to http://www.test-module.com
 
### Deploy a module for Selenium tests with "travis-ci.org"
 
1. Edit your .travis.yml
    ```yml
    before_install:
# Update composer
 - composer self-update
# Install project
 - composer install --dev -o
#deploy module
 - mkdir ../deploy
 - php ./vendor/bin/deploy_module.php -d ../deploy -v
# Install php packages
 - "sudo apt-get update > /dev/null"
 - "sudo apt-get install -y --force-yes apache2 libapache2-mod-php5 php5-curl php5-mysql php5-intl"
# Create VirtualHost
 - sudo sed -i -e "s,/var/www,$(pwd)/../deploy/public,g" /etc/apache2/sites-available/default
 - sudo sed -i -e "/DocumentRoot/i\ServerName test-selenium.dev" /etc/apache2/sites-available/default
 - echo "127.0.0.1 test-selenium.dev" | sudo tee -a /etc/hosts
 - "sudo /etc/init.d/apache2 restart"
#Run selenium
 - "sh -e /etc/init.d/xvfb start"
 - "export DISPLAY=:99.0"
 - "wget http://selenium.googlecode.com/files/selenium-server-standalone-2.25.0.jar"
 - "java -jar selenium-server-standalone-2.25.0.jar > /dev/null 2>&1 &"
 - "sleep 30"
    ```

2. Run the build
