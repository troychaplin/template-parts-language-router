<?php
// This file is generated. Do not modify it manually.
return array(
	'morsel' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tidbits/morsel',
		'version' => '0.1.0',
		'title' => 'Morsel',
		'category' => 'text',
		'description' => 'A single Tidbit post displayed within a Tidbits block.',
		'parent' => array(
			'tidbits/tidbit'
		),
		'attributes' => array(
			'postId' => array(
				'type' => 'number',
				'default' => 0
			)
		),
		'usesContext' => array(
			'tidbits/displayMode'
		),
		'supports' => array(
			'html' => false,
			'reusable' => false
		),
		'textdomain' => 'tidbits',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'render' => 'file:./render.php'
	),
	'tidbits' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tidbits/tidbit',
		'version' => '0.1.0',
		'title' => 'Tidbits',
		'category' => 'text',
		'keywords' => array(
			'tidbits',
			'tips',
			'tricks',
			'insights',
			'terms',
			'definitions',
			'glossary',
			'facts',
			'fun facts',
			'quick tips',
			'short insights'
		),
		'description' => 'Display hand-picked Tidbit posts in a variety of layouts.',
		'attributes' => array(
			'displayMode' => array(
				'type' => 'string',
				'default' => 'accordion',
				'enum' => array(
					'accordion',
					'stacked',
					'columns'
				)
			)
		),
		'providesContext' => array(
			'tidbits/displayMode' => 'displayMode'
		),
		'supports' => array(
			'html' => false,
			'anchor' => true,
			'align' => array(
				'wide',
				'full'
			),
			'layout' => array(
				'allowEditing' => false
			),
			'spacing' => array(
				'margin' => array(
					'top',
					'bottom'
				),
				'padding' => true
			)
		),
		'textdomain' => 'tidbits',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	)
);
