<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * i18n 基盤の中核である Inertia 共有props（`locale` / `messages`）を検証する。
 *
 * @see docs/decisions.md §1.3「多言語化（i18n）」
 */
class InertiaSharedPropsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_shares_current_locale_and_messages(): void
    {
        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'ja')
            ->where('messages.nav.record', '記録'),
        );
    }

    public function test_untranslated_locale_falls_back_to_japanese_messages(): void
    {
        $this->withCookie('locale', 'en')
            ->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('locale', 'en')
                // `lang/en/` 未投入の間は日本語で補う（`nav.record` のような生キーを画面に出さない）。
                ->where('messages.nav.record', '記録'),
            );
    }
}
