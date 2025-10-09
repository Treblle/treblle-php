<?php

declare(strict_types=1);

namespace Treblle\Php\Contract;

use Treblle\Php\DataTransferObject\Language;

interface LanguageDataProvider
{
    public function getLanguage(): Language;
}
