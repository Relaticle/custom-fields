<?php

declare(strict_types=1);

use Relaticle\CustomFields\Filament\Pages\CustomFieldsPage;
use Relaticle\CustomFields\Models\CustomFieldSection;
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

describe('CustomFieldsPage - Essential User Interactions', function (): void {
    it('can access page and interact with entity type selection', function (): void {
        // Arrange
        $section = CustomFieldSection::factory()
            ->forEntityType($this->userEntityType)
            ->create();

        // Act & Assert - real user workflow: access page, select entity type, see content
        livewire(CustomFieldsPage::class)
            ->assertSuccessful()
            ->call('setCurrentEntityType', $this->userEntityType)
            ->assertSee($section->name);
    });
});