<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $basePath = __DIR__.'/../../';
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        $basePath.'src',
    ]);
//    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_80);
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_74);
    $parameters->set(Option::PHPSTAN_FOR_RECTOR_PATH, __DIR__.'/tools/phpstan/config.neon');
    $parameters->set(Option::SKIP, [
        ClassPropertyAssignToConstructorPromotionRector::class,
        CallableThisArrayToAnonymousFunctionRector::class,
    ]);
    $containerConfigurator->import(SetList::CODE_QUALITY);
    $containerConfigurator->import(SetList::PHP_74);
//    $containerConfigurator->import(SetList::PHP_80);
    $containerConfigurator->import(SetList::TYPE_DECLARATION_STRICT);
};
