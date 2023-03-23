<?php

declare(strict_types=1);

use Treblle\Factory\TreblleFactory;
use Treblle\Treblle;

it('can create a new Treblle instance', function (string $string): void {
    expect(
        TreblleFactory::create(
            apiKey: $string,
            projectId: $string,
        ),
    )->toBeInstanceOf(Treblle::class);
})->with('strings');
