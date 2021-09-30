<?php

declare(strict_types=1);

namespace Treblle;

use Treblle\Contract\LanguageDataProvider;
use Treblle\Model\Language;

final class PhpLanguageDataProvider implements LanguageDataProvider
{
    private PhpHelper $phpHelper;

    public function __construct(PhpHelper $phpHelper)
    {
        $this->phpHelper = $phpHelper;
    }

    public function getLanguage(): Language
    {
        return new Language(
            'php',
            PHP_VERSION,
            $this->phpHelper->getIniValue('expose_php'),
            $this->phpHelper->getIniValue('display_errors'),
        );
    }
}
