<?php

namespace Chanthoeun\FilamentCustomForms\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Chanthoeun\FilamentCustomForms\CustomFormServiceProvider;
use Chanthoeun\FilamentCustomForms\Tests\Models\User;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        // Workaround for Livewire 4.3.1 ViewErrorBag testing bug
        $livewireSupportFile = __DIR__ . '/../vendor/livewire/livewire/src/Features/SupportValidation/SupportValidation.php';
        if (file_exists($livewireSupportFile)) {
            $content = file_get_contents($livewireSupportFile);
            if (strpos($content, '(new ViewErrorBag)->put(\'default\', $this->component->getErrorBag())') !== false) {
                $content = str_replace(
                    '(new ViewErrorBag)->put(\'default\', $this->component->getErrorBag())',
                    '(new ViewErrorBag)->put(\'default\', $this->component->getErrorBag() ?? new \Illuminate\Support\MessageBag)',
                    $content
                );
                file_put_contents($livewireSupportFile, $content);
            }
        }

        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Chanthoeun\\FilamentCustomForms\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->setUpDatabase();

        $this->app['router']->pushMiddlewareToGroup('web', function ($request, $next) {
            $errors = new \Illuminate\Support\ViewErrorBag;
            $errors->put('default', new \Illuminate\Support\MessageBag);
            \Illuminate\Support\Facades\View::share('errors', $errors);
            return $next($request);
        });

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $panel = Filament::getPanel('test');
        Filament::setCurrentPanel($panel);
    }

    protected function setUpDatabase()
    {
        $this->loadLaravelMigrations();
        
        $migration1 = include __DIR__.'/../database/migrations/create_custom_forms_table.php.stub';
        $migration1->up();

        $migration2 = include __DIR__.'/../database/migrations/create_custom_form_fields_table.php.stub';
        $migration2->up();

        $migration3 = include __DIR__.'/../database/migrations/create_custom_form_entries_table.php.stub';
        $migration3->up();

        $migration4 = include __DIR__.'/../vendor/chanthoeun/filament-document-builder/database/migrations/create_document_templates_table.php.stub';
        $migration4->up();
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:Hupx3yAySly9Xiq9fR8P5lK3fS72xXpW4yL1k9xYvG8=');

        config()->set('auth.providers.users.model', User::class);
        config()->set('session.driver', 'array');

        $app['router']->aliasMiddleware('auth', Authenticate::class);

        $app['router']->pushMiddlewareToGroup('web', StartSession::class);
        $app['router']->pushMiddlewareToGroup('web', ShareErrorsFromSession::class);
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            CustomFormServiceProvider::class,
            TestPanelProvider::class,
        ];
    }
}
