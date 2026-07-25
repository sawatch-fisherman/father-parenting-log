# TotoOps 技術スタック

> 本ドキュメントは、実際に導入・動作確認済みのバージョンをまとめたリファレンスです。
> `docker compose exec app composer show` / `docker compose exec node npm ls` 等で随時実際の値を確認できます（コマンドは[開発コマンド集](../dev-commands.md)参照）。

## バックエンド

| 項目 | バージョン |
|---|---|
| PHP | 8.5.8 |
| Laravel Framework | 13.19.0 |
| Inertia (inertiajs/inertia-laravel) | 3.1.1 |

## フロントエンド

| 項目 | バージョン |
|---|---|
| Node.js | 24.18.0 |
| Vue.js | 3.5.39 |
| @inertiajs/vue3 | 3.6.1 |
| TypeScript | 6.0.3 |
| Tailwind CSS | 4.3.2 |
| Vite | 8.1.4 |
| laravel-vite-plugin | 3.1.0 |

## データベース・インフラ（Docker）

| 項目 | バージョン |
|---|---|
| MySQL | 8.4.10 |
| Nginx | 1.31.2 |

Dockerサービス構成（[docker/docker-compose.yml](../docker/docker-compose.yml)）：`app`（PHP-FPM）・`webserver`（Nginx, `:8080`）・`db`（MySQL, `:3306`）・`node`（Vite dev server, `:5173`）の4コンテナ構成。詳細は[開発コマンド集](../dev-commands.md)を参照。

## 開発ツール

| 項目 | バージョン | 用途 |
|---|---|---|
| Laravel Pint | 1.29.3 | コード整形 |
| Larastan (larastan/larastan) | 3.10.0 | 静的解析（PHPStan level 8、[phpstan.neon](../src/phpstan.neon)） |
| PHPUnit | 12.5.31 | テスト |
| Laravel Boost | 2.4.12 | AIエージェント（Claude Code等）向けの支援ツール |
| Xdebug | 3.5.3 | ステップ実行・デバッグ（既定は無効、`XDEBUG_MODE`で切り替え） |
| beyondcode/laravel-query-detector | 2.3.0 | N+1クエリ検出 |

## バージョン管理の方針

- このファイルは「現在実際に入っているバージョン」の記録です。導入予定・構想段階の技術方針は[decisions.md](decisions.md) §1.2（非公開）を参照してください。
- ライブラリを追加・更新した際は、このファイルも合わせて更新してください。
