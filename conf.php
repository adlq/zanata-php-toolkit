<?php
/**
 * Configuration file for Zanata PHP Toolkit
 */
$ZANATA['conf'] = array(
	'zanata' => array(
		'url' => 'http://svrtest10.epistema.local:8080/zanata',
		'user' => 'admin',
		'apiKey' => '4a3b7b4e739516b23e80bf72c02c0fd0'
	),
	
	'repos' => array(
		'elms.trunk' => array(
			'projectSlug' => 'lms',
			'iterationSlug' => 'trunk'
		),

		'elms_2013_v1_maintenance' => array(
			'projectSlug' => 'lms',
			'iterationSlug' => '13.1'
		),

		'elms_2012_v1_maintenance' => array(
			'projectSlug' => 'lms',
			'iterationSlug' => '12.1'
		),

		'elms.trunk-test' => array(
			'projectSlug' => 'test',
			'iterationSlug' => '13.1-test'
		)
	)
);
		
$ZANATA['paths'] = array(
	'pophp' => '/home/nduong/l10n/pophp/'
);
