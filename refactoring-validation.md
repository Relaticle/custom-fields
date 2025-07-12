# Custom Fields Validation System Refactoring Plan

## Executive Summary

This document outlines a comprehensive refactoring of the Custom Fields validation system to address critical issues while maintaining the plugin's architectural integrity and Laravel/Filament conventions. The refactoring focuses on creating a dynamic, context-aware validation system that seamlessly integrates with conditional visibility and supports complex validation scenarios.

**Key Framework Alignment**: After analyzing Filament and Laravel source code, this plan follows established patterns including:
- Filament's trait-based approach for validation concerns
- Laravel's ConditionalRules pattern for dynamic rule application  
- Fluent rule building following Laravel's Rule class design
- Dehydration pattern for Filament form integration

## Plugin Context & Goals

The Custom Fields plugin enables dynamic field addition to Eloquent models without database migrations. Core principles:

- **Type Safety**: Leveraging PHP 8.3+ features and Spatie Laravel Data
- **Extensibility**: Field types and validation rules as pluggable components
- **Developer Experience**: Clean API following Laravel/Filament conventions
- **Performance**: Efficient validation with minimal database queries
- **Multi-tenancy**: Complete tenant isolation

## Current Issues

### 1. Critical: Visibility-Validation Conflict
- Required fields hidden by visibility conditions block form submission
- No dynamic adjustment of validation based on field visibility state
- Users cannot complete forms with conditionally hidden required fields

### 2. Missing Cross-Field Validation
- No support for field dependencies (confirmed, same, different)
- Cannot implement conditional requirements (required_if, required_unless)
- Limited to single-field validation rules

### 3. Static Validation Context
- Validation rules don't adapt to runtime conditions
- No consideration for operation context (create vs update)
- Import/export validation uses same rules as forms

### 4. Limited Validation Customization
- No custom error messages per field
- Cannot define validation groups or presets
- Missing async validation support

## Proposed Architecture

### Core Principles

1. **Maintain Existing Patterns**: Use DTOs, Enums, Services, and Factories
2. **Context-Aware Validation**: Rules adapt based on visibility and state
3. **Progressive Enhancement**: Build on existing ValidationService
4. **Type Safety**: Full type coverage with generics and strict types
5. **Testability**: Feature-first testing approach

### New Components

#### 1. Validation Context System

```php
namespace Relaticle\CustomFields\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Relaticle\CustomFields\Enums\ValidationOperation;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ValidationContextData extends Data
{
    public function __construct(
        public readonly array $values,
        public readonly ?Model $model,
        public readonly ValidationOperation $operation,
        public readonly array $visibleFields,
        public readonly array $dirtyFields,
        public readonly ?User $user,
        public readonly array $metadata = [],
    ) {}
    
    /**
     * Create context from Filament form state following Filament patterns
     */
    public static function fromFilamentForm(
        array $state,
        ?Model $record,
        array $visibleFields
    ): self {
        return new self(
            values: $state,
            model: $record,
            operation: $record?->exists ? ValidationOperation::UPDATE : ValidationOperation::CREATE,
            visibleFields: $visibleFields,
            dirtyFields: array_keys($state),
            user: auth()->user(),
        );
    }
}
```

```php
namespace Relaticle\CustomFields\Enums;

enum ValidationOperation: string implements HasLabel
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case IMPORT = 'import';
    case API = 'api';
    case BULK = 'bulk';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATE => __('custom-fields::validation.operations.create'),
            self::UPDATE => __('custom-fields::validation.operations.update'),
            self::IMPORT => __('custom-fields::validation.operations.import'),
            self::API => __('custom-fields::validation.operations.api'),
            self::BULK => __('custom-fields::validation.operations.bulk'),
        };
    }
}
```

#### 2. Enhanced Validation Rule Data  

```php
namespace Relaticle\CustomFields\Data;

use Closure;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;
use Relaticle\CustomFields\Data\VisibilityConditionData;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class EnhancedValidationRuleData extends Data
{
    public function __construct(
        public string $name,
        public array $parameters = [],
        public ?string $message = null,
        public ?VisibilityConditionData $condition = null,
        public bool $skipOnHidden = true,
        public array $operations = [],
        public ?int $priority = null,
    ) {}

    /**
     * Convert to Laravel validation rule following Laravel patterns
     */
    public function toLaravelRule(ValidationContextData $context): mixed
    {
        // Build the base rule
        $rule = $this->buildBaseRule();
        
        // Apply conditional logic following Laravel's ConditionalRules pattern
        if ($this->condition || !empty($this->operations)) {
            return Rule::when(
                fn () => $this->shouldApply($context),
                $rule
            );
        }
        
        return $rule;
    }
    
    private function buildBaseRule(): mixed
    {
        // Handle special Laravel rule objects
        return match ($this->name) {
            'unique' => $this->buildUniqueRule(),
            'exists' => $this->buildExistsRule(),
            'in' => Rule::in($this->parameters),
            'not_in' => Rule::notIn($this->parameters),
            default => $this->buildStringRule(),
        };
    }
    
    private function buildStringRule(): string
    {
        if (empty($this->parameters)) {
            return $this->name;
        }
        
        return $this->name . ':' . implode(',', $this->parameters);
    }

    public function shouldApply(ValidationContextData $context): bool
    {
        // Check if rule applies to current operation
        if (!empty($this->operations) && !in_array($context->operation->value, $this->operations)) {
            return false;
        }

        // Check visibility condition
        if ($this->condition && !$this->evaluateCondition($context)) {
            return false;
        }
        
        // Skip required rules for hidden fields
        if ($this->skipOnHidden && 
            str_starts_with($this->name, 'required') && 
            !in_array($context->field?->code, $context->visibleFields)
        ) {
            return false;
        }

        return true;
    }

    private function evaluateCondition(ValidationContextData $context): bool
    {
        // Create a mock field with the condition for evaluation
        $mockField = new CustomField();
        $mockField->visibility_conditions = [$this->condition];
        
        // Use the visibility service to evaluate
        return app(BackendVisibilityService::class)
            ->coreLogic
            ->evaluateVisibility($mockField, $context->values);
    }
}
```

#### 3. Validation Strategies (Following Filament's Concern Pattern)

```php
namespace Relaticle\CustomFields\Services\Validation\Concerns;

use Relaticle\CustomFields\Data\ValidationContextData;
use Relaticle\CustomFields\Models\CustomField;

/**
 * Following Filament's trait-based concerns pattern
 */
trait HasValidationStrategies  
{
    /**
     * @var array<string, ValidationStrategy>
     */
    protected array $strategies = [];
    
    protected function bootHasValidationStrategies(): void
    {
        $this->registerDefaultStrategies();
    }
    
    public function registerStrategy(string $name, ValidationStrategy $strategy): static
    {
        $this->strategies[$name] = $strategy;
        
        return $this;
    }
    
    public function applyStrategies(CustomField $field, ValidationContextData $context): array
    {
        $rules = [];
        
        foreach ($this->getOrderedStrategies() as $strategy) {
            $rules = array_merge($rules, $strategy->getRules($field, $context));
        }
        
        return array_unique($rules);
    }
    
    protected function getOrderedStrategies(): array
    {
        return collect($this->strategies)
            ->sortBy(fn (ValidationStrategy $strategy) => $strategy->getPriority())
            ->values()
            ->all();
    }
}

namespace Relaticle\CustomFields\Services\Validation\Strategies;

interface ValidationStrategy
{
    public function getRules(CustomField $field, ValidationContextData $context): array;
    public function getPriority(): int;
}

final class VisibilityAwareStrategy implements ValidationStrategy
{
    public function __construct(
        private readonly BackendVisibilityService $visibilityService,
        private readonly ValidationService $validationService,
    ) {}

    public function getRules(CustomField $field, ValidationContextData $context): array
    {
        // Get base rules from existing service
        $rules = $this->validationService->getValidationRules($field);
        
        // If field is not visible, apply visibility-aware filtering
        if (!in_array($field->code, $context->visibleFields)) {
            return $this->filterRulesForHiddenField($rules, $field);
        }

        return $rules;
    }
    
    private function filterRulesForHiddenField(array $rules, CustomField $field): array
    {
        // Remove required rules for hidden fields
        $rules = array_filter($rules, function ($rule) {
            if (is_string($rule)) {
                return !str_starts_with($rule, 'required');
            }
            
            // Handle rule objects
            return true;
        });
        
        // Add nullable if no other presence rules exist
        if (!$this->hasPresenceRule($rules)) {
            $rules[] = 'nullable';
        }
        
        return $rules;
    }
    
    private function hasPresenceRule(array $rules): bool  
    {
        $presenceRules = ['required', 'filled', 'nullable', 'sometimes'];
        
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                foreach ($presenceRules as $presenceRule) {
                    if (str_starts_with($rule, $presenceRule)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    public function getPriority(): int
    {
        return 100;
    }
}

final class CrossFieldValidationStrategy implements ValidationStrategy
{
    /**
     * Rules that reference other fields - following Filament's approach
     */
    private array $crossFieldRules = [
        'confirmed', 'different', 'same', 'required_if', 'required_unless',
        'required_with', 'required_without', 'prohibited_if', 'prohibited_unless',
        'required_if_accepted', 'required_if_declined', 'exclude_if', 'exclude_unless'
    ];

    public function getRules(CustomField $field, ValidationContextData $context): array
    {
        $rules = [];

        foreach ($field->validation_rules as $ruleData) {
            if (!$this->isCrossFieldRule($ruleData->name)) {
                continue;
            }

            // Transform using Filament's state path pattern
            $rules[] = $this->transformFieldReferences($ruleData, $field, $context);
        }

        return $rules;
    }
    
    private function isCrossFieldRule(string $ruleName): bool
    {
        return in_array(explode(':', $ruleName)[0], $this->crossFieldRules);
    }

    private function transformFieldReferences(
        ValidationRuleData $rule,
        CustomField $field,
        ValidationContextData $context
    ): string {
        $ruleParts = explode(':', $rule->name);
        $ruleName = $ruleParts[0];
        
        // Get parameters - could be field references
        $parameters = $rule->parameters;
        
        // Transform field codes to Filament state paths
        $transformedParams = array_map(function ($param) use ($context) {
            // Check if this parameter is a field code
            if ($this->isFieldCode($param, $context)) {
                return $this->getFieldStatePath($param);
            }
            
            return $param;
        }, $parameters);
        
        // Rebuild the rule string
        return $ruleName . ':' . implode(',', $transformedParams);
    }
    
    private function isFieldCode(string $value, ValidationContextData $context): bool
    {
        // Check if value matches a known field code
        return collect($context->metadata['allFields'] ?? [])
            ->pluck('code')
            ->contains($value);
    }
    
    private function getFieldStatePath(string $fieldCode): string
    {
        // Following Filament's naming convention for custom fields
        return "custom_fields.{$fieldCode}";
    }

    public function getPriority(): int
    {
        return 200;
    }
}
```

#### 4. Enhanced Validation Service (Following Laravel Service Pattern)

```php
namespace Relaticle\CustomFields\Services\Validation;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Relaticle\CustomFields\Data\ValidationContextData;
use Relaticle\CustomFields\Data\ValidationResult;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\TenantContextService;
use Relaticle\CustomFields\Services\Validation\Concerns\HasValidationStrategies;
use Relaticle\CustomFields\Services\Validation\Strategies\CrossFieldValidationStrategy;
use Relaticle\CustomFields\Services\Validation\Strategies\DatabaseConstraintStrategy;
use Relaticle\CustomFields\Services\Validation\Strategies\ImportValidationStrategy;
use Relaticle\CustomFields\Services\Validation\Strategies\VisibilityAwareStrategy;
use Relaticle\CustomFields\Services\Visibility\BackendVisibilityService;

final class EnhancedValidationService
{
    use HasValidationStrategies;

    public function __construct(
        private readonly ValidationService $baseValidationService,
        private readonly BackendVisibilityService $visibilityService,
        private readonly TenantContextService $tenantService,
        private readonly ValidationFactory $validationFactory,
    ) {
        $this->bootHasValidationStrategies();
    }

    public function getContextualRules(
        CustomField $field,
        ValidationContextData $context
    ): array {
        $rules = collect();

        // Apply strategies in priority order
        $this->strategies
            ->sortBy(fn($strategy) => $strategy->getPriority())
            ->each(function ($strategy) use ($field, $context, $rules) {
                $strategyRules = $strategy->getRules($field, $context);
                $rules->push(...$strategyRules);
            });

        return $rules->unique()->values()->toArray();
    }

    /**
     * Validate fields following Laravel's validation factory pattern
     */
    public function validateFields(
        Collection $fields,
        array $data,
        ValidationContextData $context
    ): ValidationResult {
        // Build validation components following Filament's dehydration pattern
        $rules = [];
        $messages = [];
        $attributes = [];

        foreach ($fields as $field) {
            $this->dehydrateFieldValidation(
                $field, 
                $context,
                $rules,
                $messages,
                $attributes
            );
        }

        // Create validator using Laravel's factory
        $validator = $this->validationFactory->make(
            $data,
            $rules,
            $messages,
            $attributes
        );
        
        // Apply after hooks for complex validation
        $this->applyAfterHooks($validator, $fields, $context);
        
        // Perform validation
        $validator->validate();
        
        return ValidationResult::fromValidator($validator);
    }
    
    /**
     * Following Filament's dehydration pattern for validation rules
     */
    private function dehydrateFieldValidation(
        CustomField $field,
        ValidationContextData $context,
        array &$rules,
        array &$messages,  
        array &$attributes
    ): void {
        $fieldKey = "custom_fields.{$field->code}";
        
        // Get contextual rules using strategies
        $fieldRules = $this->getContextualRules($field, $context);
        
        if (empty($fieldRules)) {
            return;
        }
        
        $rules[$fieldKey] = $fieldRules;
        $attributes[$fieldKey] = $field->name;
        
        // Dehydrate custom messages
        foreach ($field->validation_rules as $rule) {
            if ($rule->message) {
                $ruleKey = explode(':', $rule->name)[0];
                $messages["{$fieldKey}.{$ruleKey}"] = $rule->message;
            }
        }
    }
    
    /**
     * Apply complex validation logic after basic rules
     */
    private function applyAfterHooks(
        \Illuminate\Validation\Validator $validator,
        Collection $fields,
        ValidationContextData $context
    ): void {
        $validator->after(function ($validator) use ($fields, $context) {
            // Apply cross-field validation that requires access to all values
            $this->validateCrossFieldDependencies($validator, $fields, $context);
            
            // Apply business rule validation
            $this->validateBusinessRules($validator, $fields, $context);
        });
    }

    public function registerStrategy(ValidationStrategy $strategy): void
    {
        $this->strategies->push($strategy);
    }

    protected function registerDefaultStrategies(): void
    {
        $this->registerStrategy(
            'visibility',
            new VisibilityAwareStrategy($this->visibilityService, $this->baseValidationService)
        );
        
        $this->registerStrategy(
            'cross_field',
            new CrossFieldValidationStrategy()
        );
        
        $this->registerStrategy(
            'database',
            new DatabaseConstraintStrategy($this->baseValidationService)
        );
        
        $this->registerStrategy(
            'import',
            new ImportValidationStrategy()
        );
    }
    
    /**
     * Get contextual rules for a field using all strategies
     */
    public function getContextualRules(
        CustomField $field,
        ValidationContextData $context
    ): array {
        // Add field reference to context for strategies
        $contextWithField = clone $context;
        $contextWithField->field = $field;
        
        return $this->applyStrategies($field, $contextWithField);
    }
}
```

#### 5. Validation Result DTO

```php
namespace Relaticle\CustomFields\Data;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\MessageBag;
use Spatie\LaravelData\Data;

final class ValidationResult extends Data
{
    public function __construct(
        public readonly bool $passes,
        public readonly MessageBag $errors,
        public readonly array $validated,
        public readonly array $warnings = [],
        public readonly array $metadata = [],
    ) {}
    
    /**
     * Create from Laravel validator instance
     */
    public static function fromValidator(Validator $validator): self
    {
        return new self(
            passes: !$validator->fails(),
            errors: $validator->errors(),
            validated: $validator->validated(),
            warnings: [],
            metadata: [
                'failed_rules' => $validator->failed(),
            ],
        );
    }

    public function fails(): bool
    {
        return !$this->passes;
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
    
    /**
     * Get errors for a specific field
     */
    public function getFieldErrors(string $fieldCode): array
    {
        $fieldKey = "custom_fields.{$fieldCode}";
        
        return $this->errors->get($fieldKey, []);
    }
}
```

### Integration with Existing Components

#### 1. Enhanced Form Component Configuration (Following Filament Patterns)

```php
namespace Relaticle\CustomFields\Filament\Integration\Concerns\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Relaticle\CustomFields\Data\ValidationContextData;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\Validation\EnhancedValidationService;
use Relaticle\CustomFields\Services\ValidationService;

/**
 * Following Filament's trait pattern for form configuration
 */
trait ConfiguresEnhancedValidation
{
    /**
     * Configure validation following Filament's reactive pattern
     */
    protected function configureValidation(Field $field, CustomField $customField): Field
    {
        return $field
            // Use closure for reactive required state
            ->required(fn (Field $component): bool => 
                $this->shouldBeRequired($customField, $component)
            )
            // Dynamic rules that respond to form state changes  
            ->rules(function (Field $component) use ($customField): array {
                $context = $this->buildValidationContext($component);
                
                return app(EnhancedValidationService::class)
                    ->getContextualRules($customField, $context);
            })
            // Custom validation messages
            ->validationMessages(
                $this->getCustomValidationMessages($customField)
            )
            // Allow reactive validation
            ->reactive()
            // Live validation on blur for better UX
            ->live(onBlur: true);
    }
    
    /**
     * Build validation context from current form state
     */
    private function buildValidationContext(Field $component): ValidationContextData
    {
        $container = $component->getContainer();
        $livewire = $container->getLivewire();
        
        // Get all form state
        $state = $livewire->data;
        
        // Get visible fields based on current state
        $visibleFields = $this->getVisibleFieldCodes($state);
        
        return ValidationContextData::fromFilamentForm(
            state: $state,
            record: $livewire->getRecord(),
            visibleFields: $visibleFields
        );
    }

    /**
     * Determine if field should be required based on visibility
     */
    private function shouldBeRequired(
        CustomField $field,
        Field $component
    ): bool {
        // Check base required status
        if (!app(ValidationService::class)->isRequired($field)) {
            return false;
        }
        
        // Check visibility status
        $context = $this->buildValidationContext($component);
        
        return in_array($field->code, $context->visibleFields);
    }
    
    /**
     * Get custom validation messages following Filament pattern
     */
    private function getCustomValidationMessages(
        CustomField $customField
    ): array {
        $messages = [];
        
        foreach ($customField->validation_rules as $rule) {
            if ($rule->message) {
                $ruleKey = explode(':', $rule->name)[0];
                $messages[$ruleKey] = $rule->message;
            }
        }
        
        return $messages;
    }
}
```

#### 2. Validation Component Enhancement (Using Filament Components)

Update `CustomFieldValidationComponent` to support enhanced rules:

```php
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select; 
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Group;
use Filament\Forms\Get;
use Filament\Forms\Set;

private function getEnhancedValidationSchema(): array
{
    return [
        Grid::make(2)
            ->schema([
                $this->buildEnhancedRuleSelector(),
                $this->buildParametersField(),
            ]),
            
        Group::make([
            $this->buildConditionalValidation(),
            $this->buildOperationSelector(),
            $this->buildCustomMessageField(),
        ])->visible(fn (Get $get): bool => 
            filled($get('name'))
        ),
    ];
}

private function buildEnhancedRuleSelector(): Select
{
    return Select::make('name')
        ->label(__('custom-fields::validation.rule'))
        ->options(fn (Get $get) => $this->getContextualRuleOptions($get))
        ->searchable()
        ->required()
        ->reactive()
        ->afterStateUpdated(function (Set $set, ?string $state, Get $get) {
            // Auto-populate parameters for cross-field rules
            if ($this->isCrossFieldRule($state)) {
                $set('parameter_type', 'field_reference');
                $set('available_fields', $this->getAvailableFieldsForReference($get));
            }
            
            // Set default parameters based on rule
            $set('parameters', $this->getDefaultParametersForRule($state));
        });
}

private function buildConditionalValidation(): Group  
{
    return Group::make([
        Toggle::make('has_condition')
            ->label(__('custom-fields::validation.conditional'))
            ->reactive()
            ->afterStateUpdated(fn (Set $set, bool $state) => 
                $state ? null : $set('condition', null)
            ),
            
        Group::make([
            // Visibility condition builder component
            VisibilityConditionBuilder::make('condition')
                ->label(__('custom-fields::validation.apply_when'))
        ])->visible(fn (Get $get): bool => 
            $get('has_condition') === true
        ),
    ]);
}

private function buildOperationSelector(): Select
{
    return Select::make('operations')
        ->label(__('custom-fields::validation.apply_on_operations'))
        ->multiple()
        ->options(ValidationOperation::class)
        ->placeholder(__('custom-fields::validation.all_operations'));
}
```

### Migration Strategy

#### Phase 1: Foundation (Week 1)  
1. Create new DTOs following Spatie Laravel Data patterns:
   - ValidationContextData with factory methods for different contexts
   - EnhancedValidationRuleData with Laravel rule conversion
   - ValidationResult with validator integration
2. Create ValidationOperation enum following existing enum patterns
3. Implement ValidationStrategy interface and strategies:
   - VisibilityAwareStrategy (critical for fixing hidden field validation)
   - CrossFieldValidationStrategy (enables field dependencies)  
   - DatabaseConstraintStrategy (maintains existing behavior)
   - ImportValidationStrategy (context-specific rules)
4. Create EnhancedValidationService:
   - Integrate with Laravel's validation factory
   - Use Filament's dehydration pattern
   - Maintain backward compatibility with ValidationService

#### Phase 2: Integration (Week 2)
1. Update form components to use EnhancedValidationService
2. Implement visibility-aware validation
3. Add cross-field validation support
4. Update validation UI component

#### Phase 3: Testing & Refinement (Week 3)
1. Comprehensive test suite for new validation system
2. Migration of existing tests
3. Performance optimization
4. Documentation updates

### Testing Strategy (Using Pest PHP Patterns)

```php
use Relaticle\CustomFields\Data\ValidationContextData;
use Relaticle\CustomFields\Enums\ValidationOperation;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Services\Validation\EnhancedValidationService;

describe('Enhanced Validation System', function () {
    beforeEach(function () {
        $this->service = app(EnhancedValidationService::class);
    });
    
    it('skips required validation for hidden fields', function () {
        // Arrange
        $field = CustomField::factory()
            ->required()
            ->conditionallyVisible('trigger', 'equals', 'show')
            ->create();

        $context = new ValidationContextData(
            values: ['trigger' => 'hide', 'custom_fields' => []],
            model: null,
            operation: ValidationOperation::CREATE,
            visibleFields: [], // Field is not visible
            dirtyFields: ['trigger'],
            user: null
        );

        // Act
        $rules = $this->service->getContextualRules($field, $context);

        // Assert
        expect($rules)
            ->not->toContain('required')
            ->toContain('nullable');
    });
    
    it('applies required validation for visible fields', function () {
        // Arrange  
        $field = CustomField::factory()
            ->required()
            ->conditionallyVisible('trigger', 'equals', 'show')
            ->create();

        $context = new ValidationContextData(
            values: ['trigger' => 'show', 'custom_fields' => []],
            model: null,
            operation: ValidationOperation::CREATE,
            visibleFields: [$field->code], // Field is visible
            dirtyFields: ['trigger'],
            user: null
        );

        // Act
        $rules = $this->service->getContextualRules($field, $context);

        // Assert
        expect($rules)->toContain('required');
    });

    it('applies cross-field validation correctly', function () {
        $field = CustomField::factory()
            ->withValidation([
                new EnhancedValidationRuleData(
                    name: 'confirmed',
                    parameters: ['password_field']
                )
            ])
            ->create();

        $rules = app(EnhancedValidationService::class)
            ->getContextualRules($field, $this->context);

        expect($rules)->toContain('confirmed:custom_fields.password_field');
    });
});
```

### Benefits

1. **Solves Critical Issues**: 
   - Eliminates visibility-validation conflicts (hidden required fields)
   - Enables cross-field validation (confirmed, required_if, etc.)
   - Supports context-aware validation (create vs update)

2. **Framework Alignment**:
   - Follows Filament's trait-based concerns pattern
   - Uses Laravel's validation factory and conditional rules
   - Implements Filament's dehydration pattern for forms
   - Leverages Laravel's Rule class for complex validations

3. **Maintains Architecture**: 
   - Uses existing DTO patterns with Spatie Laravel Data
   - Extends current Services without breaking changes
   - Follows established Enum patterns
   - Preserves multi-tenancy support

4. **Developer Experience**:
   - Type-safe with full IDE support
   - Reactive validation in Filament forms  
   - Clear separation of concerns
   - Extensible via strategy pattern

5. **Performance**: 
   - Efficient rule evaluation with caching
   - Lazy loading of validation rules
   - Minimal overhead on existing validation

### Risks & Mitigation

1. **Risk**: Breaking existing validation behavior
   - **Mitigation**: Keep existing ValidationService, use feature flags

2. **Risk**: Performance impact from dynamic rules
   - **Mitigation**: Implement caching, optimize visibility checks

3. **Risk**: Complex migration for existing users
   - **Mitigation**: Automatic migration, backwards compatibility layer

## Conclusion

This refactoring addresses all critical validation issues while maintaining the plugin's architectural integrity. The solution is type-safe, extensible, and follows Laravel/Filament conventions. The phased implementation approach ensures smooth transition with minimal disruption.

## Implementation Checklist

Upon approval:

### Week 1: Foundation
- [ ] Create feature branch `feature/enhanced-validation`
- [ ] Implement ValidationContextData with tests
- [ ] Implement EnhancedValidationRuleData with tests  
- [ ] Create ValidationOperation enum
- [ ] Implement base ValidationStrategy interface
- [ ] Create VisibilityAwareStrategy (priority: fixes critical bug)
- [ ] Create CrossFieldValidationStrategy
- [ ] Implement EnhancedValidationService with strategy registration
- [ ] Add comprehensive unit tests

### Week 2: Integration  
- [ ] Create ConfiguresEnhancedValidation trait
- [ ] Update FieldComponentFactory to use enhanced validation
- [ ] Enhance CustomFieldValidationComponent UI
- [ ] Add validation context to form state handling
- [ ] Implement reactive validation in forms
- [ ] Add feature tests for form validation

### Week 3: Polish & Migration
- [ ] Add validation result caching
- [ ] Create migration guide documentation
- [ ] Add performance benchmarks
- [ ] Implement backward compatibility layer
- [ ] Add integration tests
- [ ] Update package documentation

## Success Metrics

1. **Bug Resolution**: Hidden required fields no longer block form submission
2. **Feature Completion**: Cross-field validation rules work correctly
3. **Performance**: No regression in validation performance
4. **Test Coverage**: 95%+ coverage for validation system
5. **Developer Adoption**: Clear migration path with minimal changes

## Technical Decisions Summary

After analyzing Filament and Laravel source code, key architectural decisions:

1. **Trait-Based Concerns**: Following Filament's pattern of using traits for component behaviors
2. **Conditional Rules**: Using Laravel's Rule::when() for dynamic rule application
3. **Validation Factory**: Leveraging Laravel's validation factory for consistency
4. **Dehydration Pattern**: Following Filament's approach for form integration
5. **Reactive Validation**: Using Filament's reactive() and live() for real-time validation
6. **Strategy Pattern**: Extensible validation strategies for different contexts
7. **Backward Compatibility**: EnhancedValidationService works alongside existing ValidationService

## Risk Mitigation

1. **Gradual Rollout**: Feature flag to enable enhanced validation per resource
2. **Monitoring**: Log validation rule applications for debugging
3. **Fallback**: Easy reversion to original ValidationService if needed
4. **Testing**: Comprehensive test coverage before production deployment