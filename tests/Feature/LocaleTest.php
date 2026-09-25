<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_choose_language_and_keeps_cookie(): void
    {
        $this->post('/idioma', ['locale' => 'en', 'return_to' => '/login'])
            ->assertRedirect('/login')->assertSessionHas('locale', 'en')->assertCookie('locale', 'en');
        $this->get('/login')->assertOk()->assertSee('lang="en"', false)->assertSee('Password');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_invalid_language_is_rejected(): void
    {
        foreach (['fr', '../en', ['en']] as $locale) {
            $this->post('/idioma', ['locale' => $locale])->assertSessionHasErrors('locale');
        }
    }

    public function test_external_redirects_are_rejected(): void
    {
        foreach (['https://example.com', '//example.com', '/\\example.com', '/%2fexample.com', '/%5cexample.com', '/%0d%0aLocation:evil'] as $url) {
            $this->post('/idioma', ['locale' => 'en', 'return_to' => $url])->assertRedirect('/');
        }
    }

    public function test_preference_is_saved_for_authenticated_user_and_overrides_cookie(): void
    {
        $user = User::factory()->create(['locale' => 'pt_BR']);
        $this->actingAs($user)->post('/idioma', ['locale' => 'en'])->assertRedirect('/');
        $this->assertSame('en', $user->fresh()->locale);
        $this->withCookie('locale', 'pt_BR')->get('/profile')->assertOk();
        $this->assertSame('en', app()->getLocale());
    }

    public function test_login_preserves_saved_preference(): void
    {
        $user = User::factory()->create(['locale' => 'pt_BR']);
        $this->withSession(['locale' => 'en'])->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHas('locale', 'pt_BR')->assertCookie('locale', 'pt_BR');
        $this->assertSame('pt_BR', $user->fresh()->locale);
    }

    public function test_first_login_adopts_visitor_choice_and_logout_keeps_language_cookie(): void
    {
        $user = User::factory()->create();
        $this->withSession(['locale' => 'en'])->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertCookie('locale', 'en');
        $this->assertSame('en', $user->fresh()->locale);
        $this->post('/logout')->assertRedirect('/');
        $this->withCookie('locale', 'en')->get('/login')->assertOk();
        $this->assertSame('en', app()->getLocale());
    }

    public function test_default_resets_locale_from_previous_request(): void
    {
        app()->setLocale('en');
        $this->get('/login')->assertOk();
        $this->assertSame('pt_BR', app()->getLocale());
    }

    public function test_cookie_choice_works_without_session_and_invalid_cookie_defaults_to_portuguese(): void
    {
        $this->withCookie('locale', 'en')->get('/login')->assertOk();
        $this->assertSame('en', app()->getLocale());
        $this->withCookie('locale', 'fr')->get('/login')->assertOk();
        $this->assertSame('pt_BR', app()->getLocale());
    }

    public function test_admin_reports_and_client_cache_follow_language_switch(): void
    {
        $admin = User::factory()->admin()->create(['locale' => 'en']);
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id, 'manager_id' => $admin->id]);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('lang="en"', false);
        $this->get(route('admin.reports.show', [$project, 'geral']))->assertOk()->assertSee('General report');
        $user = User::factory()->create(['client_id' => $client->id, 'locale' => 'en']);
        $this->actingAs($user)->get(route('portal.dashboard'))->assertOk()->assertSee('My Construction Projects');
        $this->post('/idioma', ['locale' => 'pt_BR']);
        $this->get(route('portal.dashboard'))->assertOk()->assertSee('Minhas Obras')->assertDontSee('My Construction Projects');
        $this->assertSame('a obra', (new Project(['type' => 'construcao']))->nounWithArticle());
    }

    public function test_login_and_validation_errors_are_in_english(): void
    {
        $this->withSession(['locale' => 'en'])->post('/login', ['email' => 'missing@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'Incorrect email or password.']);
        $this->withSession(['locale' => 'en'])->post('/idioma', ['locale' => 'fr'])
            ->assertSessionHasErrors(['locale' => 'The selected locale is invalid.']);
    }

    public function test_update_type_labels_are_translated_in_the_client_history(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        ProjectUpdate::create([
            'project_id' => $project->id,
            'title' => 'Novas fotos da fachada',
            'type' => 'foto',
            'happened_at' => now(),
        ]);
        $user = User::factory()->create(['client_id' => $client->id, 'locale' => 'en']);

        $this->actingAs($user)->get(route('portal.projects.show', $project))
            ->assertOk()->assertSee('Photo added')->assertDontSee('Foto adicionada');

        $this->post('/idioma', ['locale' => 'pt_BR']);
        $this->get(route('portal.projects.show', $project))
            ->assertOk()->assertSee('Foto adicionada')->assertDontSee('Photo added');
    }

    public function test_money_changes_number_format_without_changing_currency(): void
    {
        app()->setLocale('en');
        $this->assertSame('R$ 1,234.56', money(1234.56, 'BRL'));
        app()->setLocale('pt_BR');
        $this->assertSame('R$ 1.234,56', money(1234.56, 'BRL'));
    }
}
