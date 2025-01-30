<?php

declare(strict_types=1);

namespace Treblle\Model;

use JsonSerializable;

final class Data implements JsonSerializable
{
    /**
     * @param list<Error> $errors
     */
    public function __construct(
        private Server   $server,
        private Language $language,
        private Request  $request,
        private Response $response,
        private array    $errors
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
