<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * S7（設定画面ハブ）の表示を担当する。プロフィール編集・ピン留め設定・ログアウトへの入口を
 * 並べるだけで、全体集計への導線は置かない（一覧性より単一の入口を優先する。
 * docs/decisions.md §1.3）。
 */
class SettingsController extends Controller
{
    /**
     * 設定ハブ画面（S7）を表示する。
     */
    public function index(): Response
    {
        return Inertia::render('Settings/Index');
    }
}
