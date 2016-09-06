<?php

namespace lajax\projectscanner\scanners;

use yii\helpers\Console;

/**
 * Class for processing PHP files.
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
class PhpArrayScanner extends FileScannerAbstract implements ScannerInterface
{

    /**
     *
     * @var string Default Php Array category.
     */
    public $category = 'array';

    /**
     * @var string PHP Regular expression to match arrays containing language elements to translate.
     */
    public $patternArrayTranslator = '#\@translate[^\$]+(?P<translator>[\w\s_]+[^\(\[]+)#s';

    /**
     * Start scanning PHP files.
     */
    public function execute()
    {

        $this->scanner->stdout('Detect PhpArray - BEGIN', Console::FG_BLUE);

        foreach (self::$files[$this->extension] as $file) {
            foreach ($this->getTranslators($file) as $translator) {
                $this->extractMessages($file, [
                    'translator' => [$translator],
                    'begin' => (preg_match('#array\s*$#i', $translator) != false) ? '(' : '[',
                    'end' => ';'
                ]);
            }
        }

        $this->scanner->stdout('Detect PhpArray - END', Console::FG_BLUE);
    }

    /**
     * Returns the names of the arrays storing the language elements to be translated.
     * @param string $file Path to the file to scan.
     * @return array List of arrays storing the language elements to be translated.
     */
    protected function getTranslators($file)
    {
        $matches = [];
        $subject = file_get_contents($file);
        preg_match_all($this->patternArrayTranslator, $subject, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $translators = [];
        foreach ($matches as $data) {
            if (isset($data['translator'][0])) {
                $translators[$data['translator'][0]] = true;
            }
        }

        return array_keys($translators);
    }

    /**
     * Returns language elements in the token buffer.
     * If there are no recognisable language elements in the array, returns null
     * @param array $buffer
     * @return array|null
     */
    protected function getLanguageItem($buffer)
    {

        $index = -1;
        $languageItems = [];
        foreach ($buffer as $key => $data) {
            if (isset($data[0], $data[1]) && $data[0] === T_CONSTANT_ENCAPSED_STRING) {
                $message = stripcslashes($data[1]);
                $message = mb_substr($message, 1, mb_strlen($message) - 2);
                if (isset($buffer[$key - 1][0]) && $buffer[$key - 1][0] === '.') {
                    $languageItems[$index]['message'] .= $message;
                } else {
                    $languageItems[++$index] = [
                        'category' => $this->category,
                        'message' => $message
                    ];
                }
            }
        }

        return $languageItems ? : null;
    }

}
