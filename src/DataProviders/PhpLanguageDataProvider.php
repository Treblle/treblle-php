<?php

declare(strict_types=1);

namespace Treblle\Php\DataProviders;

use Treblle\Php\DataTransferObject\Language;
use Treblle\Php\Contract\LanguageDataProvider;

/**
 * Provides PHP language and runtime information.
 *
 * This data provider returns information about the PHP runtime,
 * including the language name ('php') and the currently running
 * PHP version from the PHP_VERSION constant.
 *
 * @package Treblle\Php\DataProviders
 */
final class PhpLanguageDataProvider implements LanguageDataProvider
{
    /**
     * Gets PHP language information.
     *
     * Returns a Language DTO containing:
     * - name: Always 'php'
     * - version: Current PHP version (e.g., '8.2.15', '8.3.0')
     *
     * @return Language The language data transfer object
     */
    public function getLanguage(): Language
    {
        return new Language(
            'php',
            PHP_VERSION,
        );
    }
}
