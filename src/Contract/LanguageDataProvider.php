<?php

declare(strict_types=1);

namespace Treblle\Contract;

use Treblle\Model\Language;

interface LanguageDataProvider
{
    public function getLanguage(): Language;
}
