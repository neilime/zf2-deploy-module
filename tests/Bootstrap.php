<?php
if(!is_readable($sAutoloaderPath = __DIR__.'/../vendor/autoload.php'))throw new \LogicException('"Composer" autoloader file "'.$sAutoloaderPath.'" is not readable');
if(false === include $sAutoloaderPath)throw new \RuntimeException('An error occured while including "Composer" autoloader file "'.$sAutoloaderPath.'"');