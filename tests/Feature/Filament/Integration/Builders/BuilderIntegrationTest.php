<?php

declare(strict_types=1);

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Infolists\Components\Entry;
use Relaticle\CustomFields\Filament\Integration\Builders\FormBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\TableBuilder;
use Relaticle\CustomFields\Filament\Integration\Builders\InfolistBuilder;
use Relaticle\CustomFields\Models\CustomField;
use Relaticle\CustomFields\Models\CustomFieldSection;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;
use Relaticle\CustomFields\Data\CustomFieldSettingsData;

beforeEach(function () {
    // Create authenticated user
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    // Set up test data
    $this->entityType = Post::class;
});

describe('FormBuilder Integration', function () {
    it('can build form components with sections and fields', function () {
        // Create a section with fields
        $section = CustomFieldSection::factory()
            ->forEntityType($this->entityType)
            ->create([
                'name' => 'General Information',
                'description' => 'Basic details',
            ]);
        
        $field1 = CustomField::factory()
            ->create([
                'custom_field_section_id' => $section->id,
                'entity_type' => $this->entityType,
                'code' => 'company_name',
                'name' => 'Company Name',
                'type' => 'text',
                'sort_order' => 1,
            ]);
        
        $field2 = CustomField::factory()
            ->create([
                'custom_field_section_id' => $section->id,
                'entity_type' => $this->entityType,
                'code' => 'company_email',
                'name' => 'Company Email',
                'type' => 'email',
                'sort_order' => 2,
            ]);
        
        // Build form components
        $builder = FormBuilder::make()
            ->forModel($this->entityType);
        
        $result = $builder->build();
        
        // Assert structure
        expect($result)->toBeInstanceOf(Grid::class);
        
        $sections = $result->getChildComponents();
        expect($sections)->toHaveCount(1);
        expect($sections[0])->toBeInstanceOf(Section::class);
        expect($sections[0]->getHeading())->toBe('General Information');
        
        // Check fields within section
        $fields = $sections[0]->getChildComponents();
        expect($fields)->toHaveCount(2);
    });
    
    it('can filter fields with only()', function () {
        $section = CustomFieldSection::factory()
            ->forEntityType($this->entityType)
            ->create();
        
        CustomField::factory()
            ->create([
                'custom_field_section_id' => $section->id,
                'entity_type' => $this->entityType,
                'code' => 'name',
                'name' => 'Name',
            ]);
        
        CustomField::factory()
            ->create([
                'custom_field_section_id' => $section->id,
                'entity_type' => $this->entityType,
                'code' => 'email',
                'name' => 'Email',
            ]);
        
        $components = FormBuilder::make()
            ->forModel($this->entityType)
            ->only('name')
            ->components();
        
        expect($components)->toHaveCount(1);
        expect($components->first()->getChildComponents())->toHaveCount(1);
    });
    
    it('can filter fields with except()', function () {
        $section = CustomFieldSection::factory()
            ->forEntityType($this->entityType)
            ->create();
        
        CustomField::factory()
            ->create([
                'custom_field_section_id' => $section->id,
                'entity_type' => $this->entityType,
                'code' => 'name',
                'name' => 'Name',
            ]);
        
        CustomField::factory()
            ->create([
                'custom_field_section_id' => $section->id,
                'entity_type' => $this->entityType,
                'code' => 'email',
                'name' => 'Email',
            ]);
        
        $components = FormBuilder::make()
            ->forModel($this->entityType)
            ->except('email')
            ->components();
        
        expect($components)->toHaveCount(1);
        expect($components->first()->getChildComponents())->toHaveCount(1);
    });
});

describe('TableBuilder Integration', function () {
    it('can build table columns', function () {
        CustomField::factory()
            ->create([
                'entity_type' => $this->entityType,
                'code' => 'name',
                'name' => 'Name',
                'type' => 'text',
                'settings' => new CustomFieldSettingsData(
                    visible_in_list: true,
                ),
            ]);
        
        CustomField::factory()
            ->create([
                'entity_type' => $this->entityType,
                'code' => 'status',
                'name' => 'Status',
                'type' => 'select',
                'settings' => new CustomFieldSettingsData(
                    visible_in_list: true,
                ),
            ]);
        
        $columns = TableBuilder::make()
            ->forModel($this->entityType)
            ->columns();
        
        expect($columns)->toHaveCount(2);
        expect($columns[0])->toBeInstanceOf(Column::class);
        expect($columns[1])->toBeInstanceOf(Column::class);
    });
    
    it('can build table filters', function () {
        CustomField::factory()
            ->create([
                'entity_type' => $this->entityType,
                'code' => 'status',
                'name' => 'Status',
                'type' => 'select',
                'settings' => new CustomFieldSettingsData(
                    searchable: true,
                ),
            ]);
        
        $filters = TableBuilder::make()
            ->forModel($this->entityType)
            ->filters();
        
        expect($filters)->toHaveCount(1);
        expect($filters[0])->toBeInstanceOf(BaseFilter::class);
    });
    
    it('returns both columns and filters in build()', function () {
        CustomField::factory()
            ->create([
                'entity_type' => $this->entityType,
                'code' => 'name',
                'name' => 'Name',
                'type' => 'text',
                'settings' => new CustomFieldSettingsData(
                    visible_in_list: true,
                    searchable: true,
                ),
            ]);
        
        $result = TableBuilder::make()
            ->forModel($this->entityType)
            ->build();
        
        expect($result)->toBeArray();
        expect($result)->toHaveKey('columns');
        expect($result)->toHaveKey('filters');
        expect($result['columns'])->toHaveCount(1);
        expect($result['filters'])->toHaveCount(1);
    });
});

describe('InfolistBuilder Integration', function () {
    it('can build infolist entries with sections', function () {
        $section = CustomFieldSection::factory()
            ->forEntityType($this->entityType)
            ->create([
                'name' => 'Company Details',
            ]);
        
        CustomField::factory()
            ->create([
                'custom_field_section_id' => $section->id,
                'entity_type' => $this->entityType,
                'code' => 'name',
                'name' => 'Name',
                'type' => 'text',
            ]);
        
        $result = InfolistBuilder::make()
            ->forModel($this->entityType)
            ->build();
        
        expect($result)->toBeInstanceOf(Grid::class);
        
        $sections = $result->getChildComponents();
        expect($sections)->toHaveCount(1);
        expect($sections[0]->getHeading())->toBe('Company Details');
    });
    
    it('can get entries without sections', function () {
        CustomField::factory()
            ->create([
                'entity_type' => $this->entityType,
                'code' => 'name',
                'name' => 'Name',
                'type' => 'text',
            ]);
        
        CustomField::factory()
            ->create([
                'entity_type' => $this->entityType,
                'code' => 'email',
                'name' => 'Email',
                'type' => 'email',
            ]);
        
        $entries = InfolistBuilder::make()
            ->forModel($this->entityType)
            ->entries();
        
        expect($entries)->toHaveCount(2);
        expect($entries[0])->toBeInstanceOf(Entry::class);
        expect($entries[1])->toBeInstanceOf(Entry::class);
    });
});