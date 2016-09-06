<?php

namespace lajax\projectscanner\scanners;

use yii\helpers\Console;

/**
 * Class for processing JavaScript files.
 * Language elements detected in JavaScript files:
 * "lajax.t" functions
 *
 * ~~~
 * lajax.t('language element');
 * lajax.t('language element {replace}', {replace:'String'});
 * lajax.t('language element');
 * lajax.t('language element {replace}', {replace:'String'});
 * ~~~
 *
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class JsScanner extends FileScannerAbstract implements ScannerInterface
{

    /**
     * @var string Extension of JavaScript files.
     */
    public $extension = '*.js';

    /**
     * @var array List of the JavaScript function for translating messages.
     */
    public $translators = ['lajax.t'];

    /**
     * @var string Default JavaScript category.
     */
    public $category = 'javascript';

    /**
     * Start scanning JavaScript files.
     */
    public function execute()
    {

        $this->scanner->stdout('Detect JavaScriptFunction - BEGIN', Console::FG_YELLOW);

        parent::execute();

        $this->scanner->stdout('Detect JavaScriptFunction - END', Console::FG_YELLOW);
    }

    /**
     * Returns language elements in the token buffer.
     * If there is no recognisable language element in the array, returns null.
     * @param array $buffer
     * @return array|null
     */
    protected function getLanguageItem($buffer)
    {
        if (isset($buffer[0][0]) && $buffer[0][0] === T_CONSTANT_ENCAPSED_STRING) {

            foreach ($buffer as $data) {
                if (isset($data[0], $data[1]) && $data[0] === T_CONSTANT_ENCAPSED_STRING) {
                    $message = stripcslashes($data[1]);
                    $messages[] = mb_substr($message, 1, mb_strlen($message) - 2);
                } else if ($data === ',') {
                    break;
                }
            }

            $message = implode('', $messages);

            return [
                [
                    'category' => $this->category,
                    'message' => $message
                ]
            ];
        }

        return null;
    }

}
