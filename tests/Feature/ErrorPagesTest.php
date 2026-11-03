<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<int>
     */
    private function codes(): array
    {
        return [400, 401, 403, 404, 405, 419, 429, 500, 503];
    }

    public function test_unknown_pages_render_the_custom_not_found_page(): void
    {
        $this->get('/definitely-not-a-real-page')
            ->assertNotFound()
            ->assertSee(__('app.errors.404.title'))
            ->assertSee(__('app.errors.code_label', ['code' => 404]));
    }

    public function test_error_pages_follow_the_selected_language(): void
    {
        $this->withUnencryptedCookie('locale', 'en')
            ->get('/definitely-not-a-real-page')
            ->assertNotFound()
            ->assertSee('This page could not be found');

        $this->withUnencryptedCookie('locale', 'id')
            ->get('/definitely-not-a-real-page')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');
    }

    public function test_switching_language_saves_the_locale_cookie(): void
    {
        $this->get(route('locale.switch', 'en'))
            ->assertPlainCookie('locale', 'en');
    }

    public function test_every_error_page_has_its_own_title_and_message(): void
    {
        $titles = [];
        $messages = [];

        foreach ($this->codes() as $code) {
            $title = __('app.errors.'.$code.'.title');
            $message = __('app.errors.'.$code.'.message');

            $this->view('errors.'.$code)
                ->assertSee($title)
                ->assertSee($message);

            $this->assertNotContains($title, $titles, 'Two error pages share a title.');
            $this->assertNotContains($message, $messages, 'Two error pages share a message.');

            $titles[] = $title;
            $messages[] = $message;
        }
    }
}
