<?php

declare(strict_types=1);

namespace Treblle;

use Treblle\Model\Language;
use Treblle\Contract\LanguageDataProvider;

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
