<?php

namespace lajax\projectscanner;

use Yii;
use yii\helpers\Console;

/**
 * Scanner component for scanning project, detecting new language elements
 *
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class Scanner extends \yii\base\Component
{

    use ConsoleOutputTrait;

    /**
     * @var scanners\ScannerInterface
     */
    public $scanners = [];

    /**
     * @var array list of file extensions that contain language elements.
     * Only files with these extensions will be processed.
     */
    public $patterns = ['*.php', '*.js'];

    /**
     * @var array list of the categories being ignored.
     */
    public $ignoredCategories = [];

    /**
     * @var array directories/files being ignored.
     */
    public $ignoredItems = [
        '.svn',
        '.git',
        '.gitignore',
        '.gitkeep',
        '.hgignore',
        '.hgkeep',
        '/messages',
        '/BaseYii.php',
        'runtime',
        'bower',
        'nikic',
    ];

    /**
     * @var array the root directory of the scanning.
     */
    public $roots;

    /**
     * @var integer The max_execution_time used when scanning, when set to null the default max_execution_time will not be modified.
     */
    public $scanTimeLimit = null;

    /**
     * @var array for storing language elements to be translated.
     */
    private $_languageElements = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->initRoots();

        $this->scanners = array_merge($this->coreScanners(), $this->scanners);

        if (!is_null($this->scanTimeLimit)) {
            set_time_limit($this->scanTimeLimit);
        }
    }

    /**
     * Initializing the root directories of the scaning.
     */
    protected function initRoots()
    {
        if (!$this->roots) {
            try {
                Yii::getAlias('@backend');
                $this->roots = [
                    '@backend',
                    '@common',
                    '@frontend',
                    '@vendor',
                ];
                Yii::info('Init Yii2-advanced-template', 'translate-manager');
            } catch (yii\base\InvalidParamException $ex) {
                Yii::info('Init Yii2-basic-template', 'translate-manager');
                $this->roots = [
                    '@app',
                ];
            }
        }

        $this->roots = array_map(function($root) {
            return realpath(Yii::getAlias($root));
        }, $this->roots);
    }

    /**
     * Returns the list of the core scanner configurations.
     * @return array the list of the core scanner configurations.
     */
    protected function coreScanners()
    {
        return [
            'dbScanner' => ['class' => scanners\DbScanner::className()],
            'jsScanner' => ['class' => scanners\JsScanner::className()],
            'phpArrayScanner' => ['class' => scanners\PhpArrayScanner::className()],
            'phpFunctionScanner' => ['class' => scanners\PhpFunctionScanner::className()],
        ];
    }

    /**
     * Scanning project for text not stored in database.
     * @return ScanResult
     */
    public function execute()
    {
        if (!$this->_languageElements) {
            foreach (array_keys($this->scanners) as $scanner) {
                $this->loadScanner($scanner)->execute();
            }
        }

        $languageElements = [];
        foreach ($this->_languageElements as $category => $messages) {
            foreach (array_keys($messages) as $message) {
                $languageElements[] = [
                    'category' => $category,
                    'message' => $message,
                ];
            }
        }

        return new ScanResult($this->_languageElements, $languageElements);
    }

    /**
     * Loads the scanner with the specified ID.
     * @param string $id the ID of the scanner to be loaded.
     * @return scanners\ScannerInterface the loaded scanner
     * @throws NotFoundHttpException
     */
    public function loadScanner($id)
    {
        if (!isset($this->scanners[$id])) {
            throw new \yii\web\NotFoundHttpException('Scanner not found: ' . $id);
        } else if (is_object($this->scanners[$id])) {
            return $this->scanners[$id];
        } else {
            $this->scanners[$id] = Yii::createObject((array) $this->scanners[$id] + ['scanner' => $this]);
        }

        return $this->scanners[$id];
    }

    /**
     * Adding language elements to the array.
     * @param string $category Category of the language element.
     * @param string $message The languageElement.
     */
    public function addLanguageItem($category, $message)
    {
        $this->_languageElements[$category][$message] = true;

        $coloredCategory = Console::ansiFormat($category, [Console::FG_YELLOW]);
        $coloredMessage = Console::ansiFormat($message, [Console::FG_YELLOW]);
        $this->stdout('Detected language element: [ ' . $coloredCategory . ' ] ' . $coloredMessage);
    }

    /**
     * Adding language elements to the array.
     * @param array $languageItems
     * example:
     *
     * ~~~
     * [
     *      [
     *          'category' => 'language',
     *          'message' => 'Active'
     *      ],
     *      [
     *          'category' => 'language',
     *          'message' => 'Inactive'
     *      ],
     * ]
     * ~~~
     *
     */
    public function addLanguageItems($languageItems)
    {
        foreach ($languageItems as $languageItem) {
            $this->addLanguageItem($languageItem['category'], $languageItem['message']);
        }
    }

}
