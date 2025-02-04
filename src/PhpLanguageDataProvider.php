<?php

declare(strict_types=1);

namespace Treblle\Php;

use Treblle\Php\DataTransferObject\Language;
use Treblle\Php\Contract\LanguageDataProvider;

final class PhpLanguageDataProvider implements LanguageDataProvider
{
    public function getLanguage(): Language
    {
        return new Language(
            'php',
            PHP_VERSION,
        );
    }
}
