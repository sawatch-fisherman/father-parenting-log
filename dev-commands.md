# TotoOps 開発コマンド集

> Docker・Laravel・導入済みライブラリ・フロントエンド(Vite)の操作をターミナルで手動実行するためのチートシートです。
> Dockerコンテナ内で完結する開発環境を前提としています（ホストにPHP/Node/Composerのインストールは不要です）。

## このファイルについて

- 「Dockerコマンド」はリポジトリのホスト側（`docker/`ディレクトリ）で実行します。
- 「Laravel」以降のコマンドは、`app`コンテナに入った状態（`docker compose exec app bash`後）での実行を前提にしています。コンテナに入らず1回だけ実行したい場合は `docker compose exec app <コマンド>` の形でも実行できます。

---

## Dockerコマンド

| 項目 | コマンド |
|:---:|:---:|
| docker-compose.ymlのあるディレクトリへ移動 | `cd docker` |
| コンテナ起動 | `docker compose up -d` |
| コンテナ停止 | `docker compose stop` |
| コンテナ停止＋削除 | `docker compose down` |
| コンテナの状態確認 | `docker compose ps` |
| ログ確認（Laravel/PHP） | `docker compose logs -f app` |
| ログ確認（Vite/フロントエンド） | `docker compose logs -f node` |
| コンテナ（Laravel）に入る | `docker compose exec app bash` |
| PHPイメージを再ビルド（Dockerfile変更後） | `docker compose build app` |
| appコンテナを再作成（設定変更を反映） | `docker compose up -d --force-recreate app` |

---

## Laravel（基本）

| 項目 | コマンド |
|:---:|:---:|
| ルーティングを確認 | `php artisan route:list` |
| 設定キャッシュ削除 | `php artisan config:clear` |
| ルートキャッシュ削除 | `php artisan route:clear` |
| Bladeテンプレートのキャッシュ削除 | `php artisan view:clear` |
| アプリケーションキャッシュ削除 | `php artisan cache:clear` |
| すべてのキャッシュをまとめて削除 | `php artisan optimize:clear` |
| オートロードの再生成 | `composer dump-autoload` |

---

## Laravel（拡張ライブラリ）

| 項目 | コマンド |
|:---:|:---:|
| コード整形チェックのみ（修正しない） | `./vendor/bin/pint --test` |
| コード整形（自動修正） | `./vendor/bin/pint` |
| 静的解析（`phpstan.neon`でlevel 8を設定済み） | `./vendor/bin/phpstan analyse` |
| ログ出力（リアルタイム表示） | `php artisan pail` |
| テストを全実行 | `php artisan test` |
| テストを1ファイルだけ実行 | `php artisan test tests/Feature/ExampleTest.php` |
| テストを名前で絞り込み実行 | `php artisan test --filter=テスト名` |
| Laravel Boostの設定を再生成（AIエージェント向け） | `php artisan boost:install` |
| **コミット前の一括チェック**（整形→静的解析→テストの順に実行、途中で失敗したら停止） | `composer check` |

> **Query Detector（N+1検出）** は `APP_DEBUG=true` の間、自動的に有効です。手動実行コマンドはなく、N+1クエリを検出すると画面上のアラートと`storage/logs`への記録で通知されます。設定は`config/querydetector.php`。

### Xdebug（ホスト側で実行）

Xdebugはコンテナ起動時の環境変数 `XDEBUG_MODE` で有効/無効を切り替えます（`docker/`ディレクトリで実行）。現在のデフォルトは`debug`（有効）です。IDE側でリスナーを起動していない状態だと、`artisan`や`composer`などPHPを実行するたびに`Could not connect to debugging client`という警告が出ますが、実害はありません。気になる場合は無効化してください。

| 項目 | コマンド |
|:---:|:---:|
| Xdebugを無効化して起動（パフォーマンス優先） | `XDEBUG_MODE=off docker compose up -d --force-recreate app` |
| Xdebugをデバッグ用に有効化して起動 | `XDEBUG_MODE=debug,develop docker compose up -d --force-recreate app` |
| 現在有効なモードを確認 | `docker compose exec app php -i \| grep XDEBUG_MODE` |

---

## Laravel（データベース）

| 項目 | コマンド |
|:---:|:---:|
| マイグレーションを実行 | `php artisan migrate` |
| マイグレーションをやり直す（全テーブル再作成） | `php artisan migrate:fresh` |
| シーダーを実行 | `php artisan db:seed` |

---

## フロントエンド（Node / Vite）

`node`コンテナはコンテナ起動時に`npm install && npm run dev`を自動実行するため、通常は以下を手動実行する必要はありません。ビルド確認や型チェックをしたい時に使います（`docker/`ディレクトリで実行）。

| 項目 | コマンド |
|:---:|:---:|
| 依存パッケージを追加インストール | `docker compose exec node npm install` |
| 開発サーバーの状態を手動で再実行 | `docker compose exec node npm run dev` |
| 本番用ビルド | `docker compose exec node npm run build` |
| TypeScriptの型チェックのみ | `docker compose exec node npm run type-check` |
