<?php
return array(
	'console' => array('router' => array('routes' => array(
		'TestModule' => array(
			'options' => array(
				'route' => 'test-module',
				'defaults' => array(
					'controller' => 'TestModule\Controller\Index',
					'action' => 'index'
				)
			)
		)
	))),
	'controllers' => array(
		'invokables' => array(
			'TestModule\Controller\Index' => 'TestModule\Controller\IndexController'
		)
	)
);