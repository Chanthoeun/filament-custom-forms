<?php

namespace Dcx\FilamentCustomForms\Tests\Feature;

use Dcx\FilamentCustomForms\Filament\Resources\CustomFormResource;
use Dcx\FilamentCustomForms\Filament\Resources\Pages;
use Dcx\FilamentCustomForms\Models\CustomForm;
use Dcx\FilamentCustomForms\Tests\TestCase;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class CustomFormResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = \Dcx\FilamentCustomForms\Tests\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->actingAs($user);
    }

    /** @test */
    public function it_can_render_page()
    {
        Livewire::test(Pages\ListCustomForms::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_list_forms()
    {
        $forms = CustomForm::factory()->count(10)->create();

        Livewire::test(Pages\ListCustomForms::class)
            ->assertCanSeeTableRecords($forms);
    }

    /** @test */
    public function it_can_create_forms()
    {
        $newData = CustomForm::factory()->make();

        Livewire::test(Pages\CreateCustomForm::class)
            ->fillForm([
                'name' => $newData->name,
                'slug' => $newData->slug,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('custom_forms', [
            'name' => $newData->name,
            'slug' => $newData->slug,
        ]);
    }

    /** @test */
    public function it_can_edit_forms()
    {
        $form = CustomForm::factory()->create();
        $newName = 'Updated Name';

        Livewire::test(Pages\EditCustomForm::class, [
            'record' => $form->getRouteKey(),
        ])
            ->fillForm([
                'name' => $newName,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals($newName, $form->refresh()->name);
    }

    /** @test */
    public function it_can_delete_forms()
    {
        $form = CustomForm::factory()->create();

        Livewire::test(Pages\EditCustomForm::class, [
            'record' => $form->getRouteKey(),
        ])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted($form);
    }
}
