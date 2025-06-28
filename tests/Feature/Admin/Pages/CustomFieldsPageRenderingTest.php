<?php

declare(strict_types=1);

use Relaticle\CustomFields\Filament\Pages\CustomFieldsPage;
use Relaticle\CustomFields\Tests\Fixtures\Models\Post;
use Relaticle\CustomFields\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    // Arrange: Create authenticated user for all tests
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    // Set up common test entity types for all tests
    $this->postEntityType = Post::class;
    $this->userEntityType = User::class;
});

describe('Page Rendering and Authorization', function (): void {
    it('can render the custom fields management page successfully', function (): void {
        // Act & Assert
        livewire(CustomFieldsPage::class)
            ->assertSuccessful();
    });

    it('can access the page via direct URL', function (): void {
        // Act & Assert
        $this->get(CustomFieldsPage::getUrl())
            ->assertSuccessful();
    });

    it('displays the correct page heading and navigation elements', function (): void {
        // Act & Assert
        livewire(CustomFieldsPage::class)
            ->assertSee(__('custom-fields::custom-fields.heading.title'));
    });

    it('checks authorization via the custom fields plugin', function (): void {
        // Act & Assert
        expect(CustomFieldsPage::canAccess())->toBeTrue();
    });

    it('respects navigation registration configuration', function (): void {
        // Act & Assert
        expect(CustomFieldsPage::shouldRegisterNavigation())->toBeBool();
    });

    it('uses configured navigation properties correctly', function (): void {
        // Act & Assert
        expect(CustomFieldsPage::getNavigationLabel())
            ->toBe(__('custom-fields::custom-fields.nav.label'))
            ->and(CustomFieldsPage::getNavigationIcon())
            ->toBe(__('custom-fields::custom-fields.nav.icon'));
    });
});

describe('Form Actions and User Interface', function (): void {
    it('has a create section action with correct properties', function (): void {
        // Act
        $component = livewire(CustomFieldsPage::class);
        $action = $component->instance()->createSectionAction();
        
        // Assert
        expect($action->getName())->toBe('createSection')
            ->and($action->getLabel())->toBe(__('custom-fields::custom-fields.section.form.add_section'))
            ->and($action->getIcon())->toBe('heroicon-s-plus');
    });

    it('displays the create section button', function (): void {
        // Act & Assert
        livewire(CustomFieldsPage::class)
            ->assertActionExists('createSection');
    });

    it('shows the create section action as visible and enabled', function (): void {
        // Act & Assert
        livewire(CustomFieldsPage::class)
            ->call('setCurrentEntityType', $this->postEntityType)
            ->assertActionExists('createSection')
            ->assertActionVisible('createSection');
    });
});