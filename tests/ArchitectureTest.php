<?php

declare(strict_types=1);

use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Relaticle\CustomFields\Contracts\FieldTypeDefinitionInterface;
use Relaticle\CustomFields\Contracts\FormComponentInterface;
use Relaticle\CustomFields\Models\Concerns\UsesCustomFields;
use Relaticle\CustomFields\Models\Contracts\HasCustomFields;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldOption;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Models\CustomFieldValue;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\PostResource;
use Relaticle\CustomFields\Tests\TestCase;
use Spatie\LaravelData\Data;

test('configurable models are only instantiated via CustomFields facade', function (string $model, string $pattern, string $facade, array $allowedFiles): void {
    $srcPath = dirname(__DIR__).'/src';
    $violations = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcPath, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relativePath = str_replace($srcPath.'/', '', $file->getPathname());

        if (in_array($relativePath, $allowedFiles, true)) {
            continue;
        }

        $lines = explode("\n", file_get_contents($file->getPathname()));

        foreach ($lines as $lineNum => $line) {
            if (str_contains($line, 'use ')) {
                continue;
            }

            if (str_contains($line, '//')) {
                continue;
            }

            if (preg_match($pattern, $line)) {
                $violations[] = $relativePath.':'.($lineNum + 1).sprintf(' -> use %s instead', $facade);
            }
        }
    }

    expect($violations)->toBeEmpty(
        "Direct {$model} instantiation/querying found:\n".implode("\n", $violations),
    );
})->with([
    'CustomField' => [
        'model' => 'CustomField',
        'pattern' => '/(?<![\w\\\\])CustomField::(query|where|find|create|first|all|get)\s*\(|new\s+CustomField[^a-zA-Z]/',
        'facade' => 'CustomFields::newCustomFieldModel()',
        'allowedFiles' => ['CustomFields.php', 'Models/CustomField.php'],
    ],
    'CustomFieldValue' => [
        'model' => 'CustomFieldValue',
        'pattern' => '/CustomFieldValue::(query|where|find|create|first|all|get)\s*\(|new\s+CustomFieldValue[^a-zA-Z]/',
        'facade' => 'CustomFields::newValueModel()',
        'allowedFiles' => ['CustomFields.php', 'Models/CustomFieldValue.php'],
    ],
    'CustomFieldOption' => [
        'model' => 'CustomFieldOption',
        'pattern' => '/CustomFieldOption::(query|where|find|create|first|all|get)\s*\(|new\s+CustomFieldOption[^a-zA-Z]/',
        'facade' => 'CustomFields::newOptionModel()',
        'allowedFiles' => ['CustomFields.php', 'Models/CustomFieldOption.php'],
    ],
    'CustomFieldSection' => [
        'model' => 'CustomFieldSection',
        'pattern' => '/CustomFieldSection::(query|where|find|create|first|all|get)\s*\(|new\s+CustomFieldSection[^a-zA-Z]/',
        'facade' => 'CustomFields::newSectionModel()',
        'allowedFiles' => ['CustomFields.php', 'Models/CustomFieldSection.php'],
    ],
]);

arch('Models extend Eloquent Model')
    ->expect([
        CustomField::class,
        CustomFieldSection::class,
        CustomFieldOption::class,
        CustomFieldValue::class,
    ])
    ->toExtend(Model::class);

arch('Custom field models are tenant-scoped')
    ->expect([
        CustomField::class,
        CustomFieldSection::class,
        CustomFieldOption::class,
        CustomFieldValue::class,
    ])
    ->toHaveAttribute(ScopedBy::class);

arch('Filament Resource extends base Resource')
    ->expect(PostResource::class)
    ->toExtend(Resource::class);

arch('Filament Resource Pages extend base Page')
    ->expect('Relaticle\CustomFields\Tests\Fixtures\Resources\Posts\Pages')
    ->toExtend(Page::class);

arch('No debugging functions are used')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('Enums are backed by strings or integers')
    ->expect('Relaticle\CustomFields\Enums')
    ->toBeEnums();

arch('Factories extend Laravel Factory')
    ->expect('Relaticle\CustomFields\Database\Factories')
    ->toExtend(Factory::class);

arch('Custom field models implement HasCustomFields contract')
    ->expect(Post::class)
    ->toImplement(HasCustomFields::class)
    ->toUse(UsesCustomFields::class);

arch('Observers follow naming convention')
    ->expect('Relaticle\CustomFields\Observers')
    ->toHaveSuffix('Observer');

arch('Middleware follows naming convention')
    ->expect('Relaticle\CustomFields\Http\Middleware')
    ->toHaveSuffix('Middleware');

arch('Exceptions follow naming convention')
    ->expect('Relaticle\CustomFields\Exceptions')
    ->toHaveSuffix('Exception');

arch('Jobs follow proper structure')
    ->expect('Relaticle\CustomFields\Jobs')
    ->not->toHaveSuffix('Job');

arch('Data objects extend Spatie Data')
    ->expect('Relaticle\CustomFields\Data')
    ->toExtend(Data::class);

arch('Field type definitions implement the field type interface')
    ->expect('Relaticle\CustomFields\FieldTypeSystem\Definitions')
    ->toImplement(FieldTypeDefinitionInterface::class);

arch('Filament form components implement the shared form component interface')
    ->expect('Relaticle\CustomFields\Filament\Integration\Components\Forms')
    ->toImplement(FormComponentInterface::class)
    ->ignoring([
        'Relaticle\CustomFields\Filament\Integration\Components\Forms\PhoneInput',
        'Relaticle\CustomFields\Filament\Integration\Components\Forms\MultiValueInput',
        'Relaticle\CustomFields\Filament\Integration\Components\Forms\RecordSelectInput',
    ]);

arch('Livewire components extend the base Component class')
    ->expect('Relaticle\CustomFields\Livewire')
    ->toExtend(Component::class)
    ->ignoring(['Relaticle\CustomFields\Livewire\Concerns']);

arch('No vendor dependencies in core models')
    ->expect('Relaticle\CustomFields\Models')
    ->not->toUse(['GuzzleHttp', 'Symfony\\Component\\HttpClient'])
    ->ignoring(['Illuminate', 'Carbon', 'Spatie']);

arch('Strict types are declared')
    ->expect('Relaticle\CustomFields')
    ->toUseStrictTypes()
    ->ignoring(['config', 'lang']);

arch('All test classes follow naming conventions')
    ->expect('Relaticle\CustomFields\Tests')
    ->toHaveSuffix('Test')
    ->ignoring([
        TestCase::class,
        'Relaticle\CustomFields\Tests\Fixtures',
        'Relaticle\CustomFields\Tests\Datasets',
        'Relaticle\CustomFields\Tests\Database\Factories',
    ]);

arch('Exceptions provide meaningful context')
    ->expect('Relaticle\CustomFields\Exceptions')
    ->toExtend('Exception')
    ->toHaveMethod('__construct');

test('every HasLabel enum in Relaticle\\CustomFields\\Enums routes getLabel through __()', function (): void {
    $dir = dirname(__DIR__).'/src/Enums';
    $files = glob($dir.'/*.php');

    $violations = [];

    foreach ($files as $file) {
        $class = 'Relaticle\\CustomFields\\Enums\\'.pathinfo($file, PATHINFO_FILENAME);

        if (! enum_exists($class)) {
            continue;
        }

        if (! is_subclass_of($class, HasLabel::class)) {
            continue;
        }

        $source = file_get_contents($file);

        if (! preg_match('/public function getLabel\(\)[^{]*\{(.*?)\n    \}/s', $source, $m)) {
            $violations[] = $class.': getLabel() not found';

            continue;
        }

        if (! str_contains($m[1], '__(')) {
            $violations[] = $class.': getLabel() does not call __()';
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});

test('every Action::make() in src/Livewire has a translated ->label()', function (): void {
    $dir = dirname(__DIR__).'/src/Livewire';
    $files = glob($dir.'/*.php');

    $violations = [];

    foreach ($files as $file) {
        $source = file_get_contents($file);

        // Capture each `Action::make(...)` call plus its chained method calls up to the terminating `;`.
        if (! preg_match_all('/(Action|BulkAction|TestAction)::make\([^)]+\).*?(?=\s*;|\)\s*,)/s', $source, $matches)) {
            continue;
        }

        foreach ($matches[0] as $chain) {
            if (! preg_match('/->label\(\s*__\(/', $chain)) {
                $violations[] = basename($file).': Action::make() without ->label(__()): '.substr(preg_replace('/\s+/', ' ', $chain), 0, 120);
            }
        }
    }

    expect($violations)->toBeEmpty(implode(PHP_EOL, $violations));
});
