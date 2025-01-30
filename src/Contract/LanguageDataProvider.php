<?php

declare(strict_types=1);

namespace Treblle\Contract;

use Treblle\DataTransferObject\Language;

interface LanguageDataProvider
{
    public function getLanguage(): Language;
}
