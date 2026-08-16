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

    /**
     * 全ページ共通のInertia propsとして、現在のロケールと翻訳メッセージが渡ることを検証する。
     *
     * `/` は M1 以降 `auth` ミドルウェア配下（`home`）のため、未認証でも確認できる `/login` で検証する。
     */
    public function test_shares_current_locale_and_messages(): void
    {
        // Act & Assert
        $this->get('/login')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'ja')
            ->where('messages.nav.record', '記録'),
        );
    }

    /**
     * フラッシュされた成功メッセージが、全ページ共通のInertia props として渡ることを検証する。
     *
     * 記録の保存成功トーストは `CareLogController@store` のリダイレクトに乗った `success` を
     * AppLayout の `ToastHost` が拾って表示するため、この共有が経路の要になる。
     */
    public function test_shares_flash_success_message(): void
    {
        // Arrange
        session()->flash('success', 'おむつ交換を記録しました');

        // Act & Assert
        $this->get('/login')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('flash.success', 'おむつ交換を記録しました'),
        );
    }

    /**
     * 翻訳ファイルが未投入のロケールを選んでも、メッセージが日本語にフォールバックすることを検証する。
     *
     * `locale` prop 自体は選択したロケールのまま渡り、メッセージだけが補われる。
     */
    public function test_untranslated_locale_falls_back_to_japanese_messages(): void
    {
        // Arrange & Act & Assert: Cookieの付与からpropsの検証までが1つの連鎖になっている
        $this->withCookie('locale', 'en')
            ->get('/login')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('locale', 'en')
                // `lang/en/` 未投入の間は日本語で補う（`nav.record` のような生キーを画面に出さない）。
                ->where('messages.nav.record', '記録'),
            );
    }
}
