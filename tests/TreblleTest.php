<?php

declare(strict_types=1);

use Http\Client\HttpClient;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Treblle\Factory\TreblleFactory;
use Treblle\Treblle;

beforeEach(fn () => $this->treblle = TreblleFactory::create(
    apiKey: 'test',
    projectId: 'test',
    debug: true,
));

it('can create a new Treblle instance', function (): void {
    expect(
        $this->treblle,
    )->toBeInstanceOf(Treblle::class);
});

it('can discover the installed Http Client', function (): void {
    expect(
        $this->treblle->client,
    )->toBeInstanceOf(HttpClient::class);
});

it('can set the Http Client', function (): void {
    expect(
        $this->treblle->client,
    )->toBeInstanceOf(HttpClient::class)->and(
        $this->treblle->setClient(
            client: new Client(),
        )->client,
    )->toBeInstanceOf(Client::class);
});

it('can get the request factory', function (): void {
    expect(
        $this->treblle->requestFactory,
    )->toBeInstanceOf(Psr17Factory::class);
});

it('can set an error', function (string $string): void {
    expect(
        $this->treblle->error->get(),
    )->toBeArray()->toBeEmpty();

    $this->treblle->onError(
        type: E_ERROR,
        message: $string,
        file: $string,
        line: 1234,
    );

    expect(
        $this->treblle->error->get(),
    )->toBeArray()->toHaveCount(1);
})->with('strings');

it('can add a new exception', function (string $string): void {
    expect(
        $this->treblle->error->get(),
    )->toBeArray()->toBeEmpty();

    $this->treblle->onException(
        exception: new InvalidArgumentException(
            message: $string,
        )
    );

    expect(
        $this->treblle->error->get(),
    )->toBeArray()->toHaveCount(1);
})->with('strings');

it('can build a payload', function (): void {
    expect(
        $this->treblle->buildPayload(),
    )->toBeArray()->toHaveKeys(
        keys: ['api_key', 'project_id', 'version', 'sdk', 'data'],
    );
});

it('sends a request on shutdown', function (): void {
    $this->treblle->setClient(
        client: new Client(),
    );

    expect(
        $this->treblle->client->getRequests(),
    )->toBeEmpty();

    $this->treblle->onShutdown();

    expect(
        $this->treblle->client->getRequests(),
    )->toHaveCount(1);
});
