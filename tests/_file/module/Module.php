<?php
namespace TestModule;
class Module  implements \Zend\ModuleManager\Feature\AutoloaderProviderInterface, \Zend\ModuleManager\Feature\ConfigProviderInterface{
	/**
	 * @see \Zend\ModuleManager\Feature\ConfigProviderInterface::getConfig()
	 * @return array
	 */
	public function getConfig(){
		return include __DIR__ . '/config/module.config.php';
	}

	/**
	 * @see \Zend\ModuleManager\Feature\AutoloaderProviderInterface::getAutoloaderConfig()
	 * @return array
	 */
	public function getAutoloaderConfig(){
		return array(
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(__NAMESPACE__ => __DIR__.'/src/'.__NAMESPACE__)
			)
		);
	}
}