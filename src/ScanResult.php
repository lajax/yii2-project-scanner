<?php

namespace lajax\projectscanner;

/**
 * ScanResult class.
 *
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class ScanResult
{

    /**
     * @var array List of Language elements.
     * eg.:
     * [
     *      [
     *          'category' => 'language',
     *          'message' => 'Active',
     *      ],
     *      [
     *          'category' => 'language',
     *          'message' => 'Inactive',
     *      ],
     *      [
     *          'category' => 'language',
     *          'message' => 'Beta',
     *      ],
     * ]
     */
    public $languageElements;

    /**
     * @var integer Number of language elements.
     * eg.:
     * 3
     */
    public $numberOflanguageElements;
    
    /**
     * @var array List of filtered language elements.
     * [
     *      'language' => [
     *          'Active' => true
     *      ]
     * ]
     * [
     *      'language' => [
     *          'Inactive' => true
     *      ]
     * ]
     * [
     *      'language' => [
     *          'Beta' => true
     *      ]
     * ]
     */
    public $filteredLanguageElements;


    /**
     * @param array $filteredLanguageElements List of language elements.
     * @param array $languageElements List of language elements.
     */
    public function __construct(array $filteredLanguageElements, array $languageElements)
    {
        $this->languageElements = $languageElements;
        $this->numberOflanguageElements = count($languageElements);
        $this->filteredLanguageElements = $filteredLanguageElements;
    }

}
