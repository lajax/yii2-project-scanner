Yii2 Project Scanner
====================
Yii2 Project Scanner Extension

Installation
------------

##composer

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require lajax/yii2-project-scanner
```

or add

```
"lajax/yii2-project-scanner": "^1.0.0"
```

to the require section of your `composer.json` file.


Usage
-----

##Configs

###Minimal configs
```php
[
    'components' => [
        // ...
        'scanner' =>  lajax\projectscanner\Scanner::className(),
        // ...
    ],
],
```

###Full configs

```php
[
    'components' => [
        // ...
        'scanner' =>  [
            'class' => lajax\projectscanner\Scanner::className(),
            'scanTimeLimit' => null,
            'ignoredCategories' => [],
            'ignoredItems' => [
                '.svn',
                '.git',
                '.gitignore',
                '.gitattributes',
                '.gitkeep',
                '.hgignore',
                '.hgkeep',
                '/messages',
                '/BaseYii.php',
                'runtime',
                'bower',
                'nikic',
            ],
            'roots' => [
                '@backend',
                '@common',
                '@console',
                '@frontend',
                '@vendor',
            ],
            'scanners' => [
                'dbScanner' => [
                    'class' => lajax\projectscanner\scanners\DbScanner::className(),
                    'category' => 'database',
                    'tables' => [
                        [
                            'connection' => 'db',
                            'table' => 'language',
                            'columns' => ['name', 'name_ascii'],
                            'category' => 'tableName',
                        ],
                        [
                            'connection' => 'db',
                            'table' => 'tag',
                            'columns' => ['name'],
                            'category' => 'tableName',
                        ],
                        [
                            'connection' => 'db',
                            'table' => 'category',
                            'columns' => ['name', 'description'],
                        ],
                    ],
                ],
                'jsScanner' => [
                    'class' => lajax\projectscanner\scanners\JsScanner::className(),
                    'extension' => '*.js',
                    'translators' => ['lajax.t'],
                    'category' => 'javascript',

                ],
                'phpArrayScanner' => [
                    'class' => lajax\projectscanner\scanners\PhpArrayScanner::className(),
                    'extension' => '*.php',
                    'category' => 'array',
                    'patternArrayTranslator' => '#\@translate[^\$]+(?P<translator>[\w\d\s_]+[^\(\[]+)#s',

                ],
                'phpFunctionScanner' => [
                    'class' => lajax\projectscanner\scanners\PhpFunctionScanner::className(),
                    'extension' => '*.php',
                    'translators' => ['::t'],
                ],
            ],
        ],
        // ...
    ],
],

##Scanning project

```php
$scannerResult = \Yii::$app->scanner->execute();
```

###result

```php
$scannerResult->languageElements;       *Array* List of language elements.
                                        [
                                            ['category' => 'messageCategory', 'message' => 'languageElement'],
                                            ['category' => 'messageCategory', 'message' => 'languageElement'],
                                            // ...
                                        ]
$scannerResult->numberOfLanguageElements;  *Integer* Number of language elements.

$scannerResult->filteredLanguageElements;   *Array* List of language elements.
                                        [
                                            'messageCategory' => [
                                                'languageElement' => true
                                            ]
                                        ]
                                        [
                                            'messageCategory' => [
                                                'languageElement' => true
                                            ]
                                        ]
                                        [
                                            'messageCategory' => [
                                                'languageElement' => true
                                            ]
                                        ]
```


Links
-----

- [GitHub](https://github.com/lajax/yii2-project-scanner)
- [ApiDocs](https://lajax.github.io/yii2-project-scanner)
- [Packagist](https://packagist.org/packages/lajax/yii2-project-scanner)
- [Yii Extensions](http://www.yiiframework.com/extension/yii2-project-scanner)