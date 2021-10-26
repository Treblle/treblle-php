<?php

declare(strict_types=1);

namespace Treblle;

use Safe\Exceptions\InfoException;

final class PhpHelper
{
    /**
     * Get PHP configuration variables.
     */
    public function getIniValue(string $variable): string
    {
        try {
            $variableValue = \safe\ini_get($variable);
            $isBool = filter_var($variableValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (\is_bool($isBool)) {
                return ($variableValue !== '' && $variableValue !== '0') ? 'On' : 'Off';
            }

            return (string) $variableValue;
        } catch (InfoException $exception) {
        }

        return '<unknown>';
    }
}
