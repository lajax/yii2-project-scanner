<?php

namespace lajax\projectscanner\scanners;

use Yii;
use yii\base\Object;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Class for processing PHP and JavaScript files.
 * Language elements detected in JavaScript files:
 *
 * ~~~
 * lajax.t('language element');
 * lajax.t('language element {replace}', {replace:'String'});
 * lajax.t('language element');
 * lajax.t('language element {replace}', {replace:'String'});
 * ~~~
 *
 * Language elements detected in PHP files:
 * "t" functions:
 *
 * ~~~
 * ::t('category of language element', 'language element');
 * ::t('category of language element', 'language element {replace}', ['replace' => 'String']);
 * ::t('category of language element', 'language element');
 * ::t('category of language element', 'language element {replace}', ['replace' => 'String']);
 * ~~~
 *
 * Language elements detected in constant arrays:
 *
 * ~~~
 *  /**
 *   * @translate
 *   *\/
 *  private $_GENDERS = ['Male', 'Female'];
 *  /**
 *   * @translate
 *   *\/
 *   private $_STATUSES = [
 *      self::STATUS_ACTIVE => 'Active',
 *      self::STATUS_INACTIVE => 'Inactive'
 *   ];
 * ~~~
 *
 * Translation of constant arrays:
 * Translation to site language:
 *
 * ~~~
 * $genders = \lajax\translatemanager\helpers\Language::a($this->_GENDERS);
 * ~~~
 *
 * Translating to the language of your coice:
 *
 * ~~~
 * $statuses = \lajax\translatemanager\helpers\Language::a($this->_STATUSES, [], 'de-DE');
 * ~~~
 *
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
abstract class FileScannerAbstract extends Object
{

    /**
     * @var string Extension of PHP files.
     */
    public $extension = '*.php';

    /**
     * @var \lajax\projectscanner\Scanner object containing the detected language elements
     */
    public $scanner;

    /**
     * @var array Array to store paths to project files.
     */
    protected static $files = ['*.php' => [], '*.js' => []];

    /**
     * Scanning project filesistem.
     */
    public function scanProject()
    {
        if (empty(static::$files[$this->extension]) && in_array($this->extension, $this->scanner->patterns)) {
            foreach ($this->scanner->roots as $root) {
                static::$files[$this->extension] = array_merge(static::$files[$this->extension], FileHelper::findFiles($root, [
                            'except' => $this->scanner->ignoredItems,
                            'only' => [$this->extension],
                ]));
            }
        }
    }

    /**
     * Executes the scanning statement.
     */
    public function execute()
    {

        $this->scanProject();

        foreach (static::$files[$this->extension] as $file) {
            if ($this->containsTranslator($this->translators, $file)) {
                $this->extractMessages($file, [
                    'translator' => (array) $this->translators,
                    'begin' => '(',
                    'end' => ')'
                ]);
            }
        }
    }

    /**
     * Extracts messages from a file
     *
     * @param string $fileName name of the file to extract messages from
     * @param array $options Definition of the parameters required to identify language elements.
     * example:
     *
     * ~~~
     * [
     *      'translator' => ['Yii::t', 'Lx::t'],
     *      'begin' => '(',
     *      'end' => ')'
     * ]
     * ~~~
     *
     */
    protected function extractMessages($fileName, $options)
    {
        $this->scanner->stdout('Extracting messages from ' . $fileName, Console::FG_GREEN);
        $subject = file_get_contents($fileName);
        if ($this->extension !== '*.php') {
            $subject = "<?php\n" . $subject;
        }

        foreach ($options['translator'] as $currentTranslator) {
            $translatorTokens = token_get_all('<?php ' . $currentTranslator);
            array_shift($translatorTokens);

            $tokens = token_get_all($subject);

            $this->checkTokens($options, $translatorTokens, $tokens);
        }
    }

    /**
     * @param $options Definition of the parameters required to identify language elements.
     * @param $translatorTokens Translation identification
     * @param $tokens Tokens to search through
     */
    protected function checkTokens($options, $translatorTokens, $tokens)
    {
        $translatorTokensCount = count($translatorTokens);
        $matchedTokensCount = 0;
        $buffer = [];

        foreach ($tokens as $token) {
            // finding out translator call
            if ($matchedTokensCount < $translatorTokensCount) {
                if ($this->tokensEqual($token, $translatorTokens[$matchedTokensCount])) {
                    $matchedTokensCount++;
                } else {
                    $matchedTokensCount = 0;
                }
            } elseif ($matchedTokensCount === $translatorTokensCount) {
                // translator found
                // end of translator call or end of something that we can't extract
                if ($this->tokensEqual($options['end'], $token)) {

                    $languageItems = $this->getLanguageItem($buffer);
                    if ($languageItems) {
                        $this->scanner->addLanguageItems($languageItems);
                    }

                    if (count($buffer) > 4 && $buffer[3] == ',') {
                        array_splice($buffer, 0, 4);
                        $buffer[] = $options['end']; //append an end marker stripped by the current check
                        $this->checkTokens($options, $translatorTokens, $buffer);
                    }

                    // prepare for the next match
                    $matchedTokensCount = 0;
                    $buffer = [];
                } elseif ($token !== $options['begin'] && isset($token[0]) && !in_array($token[0], [T_WHITESPACE, T_COMMENT])
                ) {
                    // ignore comments, whitespaces and beginning of function call
                    $buffer[] = $token;
                }
            }
        }
    }

    /**
     * Finds out if two PHP tokens are equal
     *
     * @param array|string $a
     * @param array|string $b
     * @return boolean
     * @since 2.0.1
     */
    protected function tokensEqual($a, $b)
    {
        if (is_string($a) && is_string($b)) {
            return $a === $b;
        } elseif (isset($a[0], $a[1], $b[0], $b[1])) {
            return $a[0] === $b[0] && $a[1] == $b[1];
        }
        return false;
    }

    /**
     * Determines whether the category received as a parameter can be processed.
     * @param string $category
     * @return boolean
     */
    protected function isValidCategory($category)
    {
        return !in_array($category, $this->scanner->ignoredCategories);
    }

    /**
     * Determines whether the file has any of the translators.
     *
     * @param string[] $translators Array of translator patterns to search (for example: `['::t']`).
     * @param string $file Path of the file.
     * @return bool
     */
    protected function containsTranslator($translators, $file)
    {
        return preg_match(
                        '#(' . implode('\s*\()|(', array_map('preg_quote', $translators)) . '\s*\()#i', file_get_contents($file)
                ) > 0;
    }

    /**
     * Returns language elements in the token buffer.
     * If there is no recognisable language element in the array, returns null.
     */
    abstract protected function getLanguageItem($buffer);
}
