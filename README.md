config/main.php
```php
return [
    // ...
	'import' => [
		'application.components.zipkin.*',
	],
	'components' => [
		// ...
		'zipkin' => [
			'class' => Zipkin::class,
		],
	],
	'onBeginRequest' => function ($event) {
		\Yii::app()->zipkin->ping();  // По другому не получилось
	}
];
```

code
```php
		\Yii::app()->zipkin->addSpan(
			'get data',
			\Yii::app()->zipkin->newEndpoint(),
			[
				"param" => 1,
			]
		);
```