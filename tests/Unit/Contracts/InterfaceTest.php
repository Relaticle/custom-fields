<?php

declare(strict_types=1);

use Relaticle\CustomFields\Contracts\Builders\BuilderInterface;
use Relaticle\CustomFields\Contracts\Builders\CustomFieldsBuilderInterface;
use Relaticle\CustomFields\Contracts\Builders\FormBuilderInterface;
use Relaticle\CustomFields\Contracts\Builders\InfolistBuilderInterface;
use Relaticle\CustomFields\Contracts\Builders\TableBuilderInterface;
use Relaticle\CustomFields\Contracts\Factories\ComponentFactoryInterface;
use Relaticle\CustomFields\Contracts\Factories\FactoryInterface;
use Relaticle\CustomFields\Contracts\Services\FieldRepositoryInterface;
use Relaticle\CustomFields\Contracts\Services\ServiceInterface;
use Relaticle\CustomFields\Contracts\Services\StateManagerInterface;

it('can load all contract interfaces', function (): void {
    // Builder interfaces
    expect(interface_exists(BuilderInterface::class))->toBeTrue();
    expect(interface_exists(CustomFieldsBuilderInterface::class))->toBeTrue();
    expect(interface_exists(FormBuilderInterface::class))->toBeTrue();
    expect(interface_exists(TableBuilderInterface::class))->toBeTrue();
    expect(interface_exists(InfolistBuilderInterface::class))->toBeTrue();

    // Factory interfaces
    expect(interface_exists(FactoryInterface::class))->toBeTrue();
    expect(interface_exists(ComponentFactoryInterface::class))->toBeTrue();

    // Service interfaces
    expect(interface_exists(ServiceInterface::class))->toBeTrue();
    expect(interface_exists(FieldRepositoryInterface::class))->toBeTrue();
    expect(interface_exists(StateManagerInterface::class))->toBeTrue();
});

it('builder interfaces extend base interfaces correctly', function (): void {
    $formReflection = new ReflectionClass(FormBuilderInterface::class);
    expect($formReflection->getInterfaceNames())->toContain(CustomFieldsBuilderInterface::class);

    $tableReflection = new ReflectionClass(TableBuilderInterface::class);
    expect($tableReflection->getInterfaceNames())->toContain(CustomFieldsBuilderInterface::class);

    $infolistReflection = new ReflectionClass(InfolistBuilderInterface::class);
    expect($infolistReflection->getInterfaceNames())->toContain(CustomFieldsBuilderInterface::class);
});

it('service interfaces extend base service interface', function (): void {
    $fieldRepoReflection = new ReflectionClass(FieldRepositoryInterface::class);
    expect($fieldRepoReflection->getInterfaceNames())->toContain(ServiceInterface::class);

    $stateManagerReflection = new ReflectionClass(StateManagerInterface::class);
    expect($stateManagerReflection->getInterfaceNames())->toContain(ServiceInterface::class);
});

it('factory interfaces extend base factory interface', function (): void {
    $componentFactoryReflection = new ReflectionClass(ComponentFactoryInterface::class);
    expect($componentFactoryReflection->getInterfaceNames())->toContain(FactoryInterface::class);
});