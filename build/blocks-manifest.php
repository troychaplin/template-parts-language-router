<?php
// This file is generated. Do not modify it manually.
return array(
	'router' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tp-router/router',
		'version' => '0.1.0',
		'title' => 'Template Parts Router',
		'category' => 'theme',
		'icon' => 'translation',
		'description' => 'Renders the language-suffixed variant of the surrounding template part based on the active WPML language.',
		'attributes' => array(
			'baseSlug' => array(
				'type' => 'string',
				'default' => ''
			),
			'variantType' => array(
				'type' => 'string',
				'default' => 'template-part'
			)
		),
		'supports' => array(
			'html' => false,
			'inserter' => true,
			'multiple' => true,
			'reusable' => false
		),
		'textdomain' => 'tp-router',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'render' => 'file:./render.php'
	)
);
