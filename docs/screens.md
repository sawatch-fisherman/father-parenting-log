# TotoOps MVP 画面設計（Step 3）

> 本ドキュメントは Laravel 実装前の **MVP 画面一覧・ルーティング・Controller構成** の設計です。
> [features.md](features.md) のMVP機能一覧・[data-model.md](data-model.md) の8テーブルをもとに、実際の画面単位に落とし込みます。

## 進め方

1. 画面一覧・画面遷移図（俯瞰）
2. ルーティング一覧
3. Controller構成 ← 今ここ

---

## 画面一覧

| # | 画面名 | 概要 | 対応するMVP機能（[features.md](features.md)） |
|---|---|---|---|
| S1 | ログイン画面 | Google SSOボタンのみ | ユーザー登録・ログイン |
| S2 | プロフィール登録画面 | 初回ログイン時のみ。ニックネーム（必須）・年代／子どもの年齢帯（ともに任意） | プロフィール登録 |
| S3 | 記録画面 | ヘッダー（ユーザーアイコン※Google SSOから取得・DBには保存しない。設定へはグローバルナビから遷移）＋常時8アイコン（**短タップ＝即記録**／**長押し＝S10で日時指定**）＋「その他」ボタン（→S4） | 育児イベント種別管理／育児イベント登録 |
| S4 | 「その他」イベント選択画面 | ピン留めされていない残りの候補一覧。項目をタップすると**常にS10（実施日時指定画面）を経由**する（瞬間記録は行わない。低頻度・後追い記録が多いため） | 育児イベント種別管理（その他ボタン） |
| S5 | 称号獲得モーダル | 称号獲得時に記録画面（S3）上にオーバーレイ表示 | 称号獲得 |
| S6 | X投稿文生成モーダル | 称号獲得モーダルから遷移。生成文のコピー・Xを開くリンク | X投稿文生成・コピー |
| S7 | 設定画面（ハブ） | プロフィール編集・ピン留め設定・ログアウトへの入口（Phase 2以降の卒業・全体集計・広告への導線は器として用意） | 設定画面（ハブ） |
| S8 | プロフィール編集画面 | 設定画面から遷移。現在のニックネーム・年代・子どもの年齢帯を表示し、その場で変更できる（閲覧専用画面は別途用意しない） | 設定画面（ハブ） |
| S9 | ピン留め設定画面 | 常時8個の入れ替え（⑤`user_slot_configs`を編集） | 育児イベント種別管理（常時8アイコン） |
| S10 | 実施日時指定画面 | S3の8アイコンを長押し、またはS4の項目をタップした際に遷移する。`occurred_at`を指定して育児イベントを記録する | 育児イベント登録 |
| S11 | ログ編集画面 | S13（記録履歴画面）の各行の「・・・」から遷移する。**「実施日時の変更」または「削除」のみ**可能（イベント種別の変更は不可。種別を変えたい場合は削除→S3/S4から再記録） | 育児イベント登録 |
| S12 | 期間別集計画面 | 日/週/月/**全期間**タブ＋グラフ＋イベント種別ごとの件数内訳（全期間タブでは自分の累計実績を確認できる）。（全期間タブに称号一覧（図鑑）画面への導線を追加予定。Phase 2） | ダッシュボード集計 |
| S13 | 記録履歴画面（タイムライン） | 日付ごとにグループ化したログ一覧（新しい順）。各行「・・・」→S11 | 記録履歴（タイムライン） |

Phase 2以降の「全体タスク傾向」「卒業＆エンドロール」「称号一覧（図鑑）画面」等の画面はこの一覧には含めていません（MVP対象外）。

---

## 共通UI：グローバルナビゲーション

S3・S12・S13・S7の4画面は、常時表示のナビゲーションから互いに行き来する。それ以外の画面（S4・S10・S11・S2・S8・S9）やモーダル（S5・S6）には表示しない。

- **モバイル（優先）**：画面下部のタブバー。項目は「記録（S3）」「履歴（S13）」「集計（S12）」「設定（S7）」の4つ。
- **Web（PC）**：画面左のサイドバー。同じ4項目を縦に並べる。

---

## 画面遷移図（全体俯瞰）

```mermaid
flowchart TD
    START(("開始")) --> S1

    S1["S1 ログイン画面"] -->|"初回ログイン（プロフィール未登録）"| S2
    S1 -->|"2回目以降（登録済み）"| S3

    S2["S2 プロフィール登録画面"] -->|"登録完了"| S3

    S3["S3 記録画面"] -->|"「その他」ボタン"| S4
    S4["S4 その他イベント選択画面"]

    S3 -->|"長押し"| S10
    S4 -->|"タップ（常に日時指定）"| S10
    S10["S10 実施日時指定画面"] -->|"保存（称号条件未達成）"| S3
    S10 -->|"保存（称号条件達成）"| S5

    S3 -->|"称号条件達成（自動・短タップの場合）"| S5
    S5["S5 称号獲得モーダル"] -->|"「Xに投稿」"| S6
    S5 -->|"閉じる"| S3
    S6["S6 X投稿文生成モーダル"] -->|"コピー後閉じる"| S3

    S13["S13 記録履歴画面（タイムライン）"] -->|"「・・・」"| S11
    S11["S11 ログ編集画面"] -->|"保存／削除"| S13

    S7["S7 設定画面（ハブ）"] -->|"プロフィール編集"| S8
    S7 -->|"ピン留め設定"| S9
    S8["S8 プロフィール編集画面"] -->|"保存"| S7
    S9["S9 ピン留め設定画面"] -->|"保存"| S7
    S7 -->|"ログアウト"| S1

    GNAV(("グローバルナビ<br>（タブ／サイドバー）"))
    GNAV --- S3
    GNAV --- S12["S12 期間別集計画面"]
    GNAV --- S13
    GNAV --- S7
```

補足：

- S4・S10・S11は入力・編集を伴うため独立したページ（URLを持つ画面）とする。S5・S6は表示のみのためモーダル（S3上のUI状態）のまま、専用ルートは持たない。詳細は次の「ルーティング一覧」を参照。
- 称号獲得は、育児イベント登録の結果として**自動的に**発生する遷移であり、ユーザー操作による遷移ではない。S3の短タップは直接S5に、長押し・S4経由はS10の保存後にS5に、それぞれ分岐する。
- S4はもはや単独でイベントを記録しない（必ずS10を経由する）。
- S12・S13はS3から分離した独立画面であり、グローバルナビゲーション（タブバー／サイドバー）経由でS3・S7と相互に行き来する。

---

## ルーティング一覧

ページ系（GET、Inertiaでレンダリングする画面）と、データ変更のみを行うアクション系（POST/PATCH/DELETE、ページを返さない）に分けて整理する。Controllerのクラス・メソッド構成は次の「Controller構成」で検討する。

### ページ（GET）

| Method | URI | ルート名 | 対応画面 | 備考 |
|---|---|---|---|---|
| GET | `/login` | `login` | S1 ログイン画面 | Google SSOボタンのみ |
| GET | `/auth/google/redirect` | `auth.google.redirect` | (S1の遷移先) | Google認証開始（Socialite） |
| GET | `/auth/google/callback` | `auth.google.callback` | (S1の遷移先) | 認証コールバック。プロフィール未登録ならS2へ、登録済みならS3へリダイレクト |
| GET | `/profile/register` | `profile.register` | S2 プロフィール登録画面 | 初回ログイン時のみ |
| GET | `/` | `home` | S3 記録画面 | 認証済みユーザーのトップ。未認証は`/login`へリダイレクト |
| GET | `/care-event-types/other` | `care-event-types.other` | S4 「その他」イベント選択画面 | ピン留めされていない候補一覧 |
| GET | `/care-events/create` | `care-events.create` | S10 実施日時指定画面 | クエリで対象イベント種別を受け取る（例：`?care_event_type_id=`） |
| GET | `/care-events/{care_event}/edit` | `care-events.edit` | S11 ログ編集画面 | S13の各行から遷移 |
| GET | `/settings` | `settings.index` | S7 設定画面（ハブ） | |
| GET | `/settings/profile` | `settings.profile.edit` | S8 プロフィール編集画面 | 閲覧も兼ねる |
| GET | `/settings/slots` | `settings.slots.edit` | S9 ピン留め設定画面 | |
| GET | `/stats` | `stats.index` | S12 期間別集計画面 | |
| GET | `/history` | `history.index` | S13 記録履歴画面（タイムライン） | |

### アクション（POST/PATCH/DELETE、ページを返さない）

| Method | URI | ルート名 | 用途 | 備考 |
|---|---|---|---|---|
| POST | `/profile` | `profile.store` | S2の登録処理 | 完了後`/`（S3）へリダイレクト |
| POST | `/care-events` | `care-events.store` | 育児イベント記録 | S3短タップ／S10保存の共通エンドポイント。クライアントは短タップ時もタップ時刻（ミリ秒精度）を`occurred_at`として送信する（省略時の`now()`は二重送信防止が効かないフォールバック。[decisions.md](decisions.md) §1.3） |
| PATCH | `/care-events/{care_event}` | `care-events.update` | S11保存 | `occurred_at`のみ変更可 |
| DELETE | `/care-events/{care_event}` | `care-events.destroy` | S11削除 | |
| PATCH | `/settings/profile` | `settings.profile.update` | S8保存 | |
| PUT | `/settings/slots` | `settings.slots.update` | S9保存 | ピン留め8個の入れ替え |
| POST | `/logout` | `logout` | S7のログアウト | |

**ルートを持たない画面**：S5（称号獲得モーダル）・S6（X投稿文生成モーダル）。S5は`POST /care-events`のレスポンス（称号獲得情報を含む）を受けてVue側で自動表示し、S6はS5内のデータからクライアント側でテキスト生成するのみで、いずれもサーバー往復は不要。

---

## Controller構成

同一リソースを扱う画面はControllerを統合する（Laravelのリソースコントローラ規約に沿う）。`profiles`はS2・S8をまとめて`ProfileController`、`care_events`はS10・S11・保存/削除をまとめて`CareEventController`が担当する。

| 画面/用途 | ルート | Controller | メソッド | FormRequest | Policy |
|---|---|---|---|---|---|
| S1 ログイン画面 | GET `/login` | `Auth\AuthenticatedSessionController` | `create()` | — | — |
| (S1の遷移先) | GET `/auth/google/redirect` | `Auth\GoogleSocialiteController` | `redirect()` | — | — |
| (S1の遷移先) | GET `/auth/google/callback` | `Auth\GoogleSocialiteController` | `callback()` | — | — |
| S7のログアウト | POST `/logout` | `Auth\AuthenticatedSessionController` | `destroy()` | — | — |
| S2 プロフィール登録画面 | GET `/profile/register` | `ProfileController` | `create()` | — | — |
| (S2の送信) | POST `/profile` | `ProfileController` | `store()` | `ProfileRequest` | 不要（IDをURLに含めず常に自分のプロフィールのみ操作） |
| S8 プロフィール編集画面 | GET `/settings/profile` | `ProfileController` | `edit()` | — | 同上 |
| (S8の保存) | PATCH `/settings/profile` | `ProfileController` | `update()` | `ProfileRequest`（storeと共用） | 同上 |
| S3 記録画面 | GET `/` | `RecordController` | `index()` | — | — |
| S4 「その他」イベント選択画面 | GET `/care-event-types/other` | `CareEventTypeController` | `other()` | — | — |
| S10 実施日時指定画面 | GET `/care-events/create` | `CareEventController` | `create()` | — | — |
| (S3短タップ／S10保存) | POST `/care-events` | `CareEventController` | `store()` | `StoreCareEventRequest` | — |
| S11 ログ編集画面 | GET `/care-events/{care_event}/edit` | `CareEventController` | `edit()` | — | `CareEventPolicy@update`（他人の記録IDを弾く） |
| (S11保存) | PATCH `/care-events/{care_event}` | `CareEventController` | `update()` | `UpdateCareEventRequest`（`occurred_at`のみ許可） | `CareEventPolicy@update` |
| (S11削除) | DELETE `/care-events/{care_event}` | `CareEventController` | `destroy()` | — | `CareEventPolicy@delete` |
| S7 設定画面（ハブ） | GET `/settings` | `SettingsController` | `index()` | — | — |
| S9 ピン留め設定画面 | GET `/settings/slots` | `SlotConfigController` | `edit()` | — | — |
| (S9保存) | PUT `/settings/slots` | `SlotConfigController` | `update()` | `UpdateSlotConfigRequest`（重複不可・許可された種別のみ） | 不要（同上の理由） |
| S12 期間別集計画面 | GET `/stats` | `StatsController` | `index()` | — | — |
| S13 記録履歴画面 | GET `/history` | `HistoryController` | `index()` | — | — |

補足：

- Policyが必要なのは`care_events`のみ。`{care_event}`がURLにID付きで現れるため、他人のレコードを弾くための`CareEventPolicy`が必要になる。他のリソース（プロフィール・スロット設定）はIDをURLに含めず常に「自分自身」に暗黙スコープするため、Policyは不要と判断した。
- `ProfileRequest`はstore/updateで同一ルール（`nickname`必須、`age_group`/`child_age_group`任意）のため1クラスを共用する。

---

## Phase 2以降の画面（仕様検討中）

MVPの画面一覧（S1〜S13）には含めないが、実装より前に仕様を検討しているPhase 2以降の画面。

### 称号一覧（図鑑）画面

- **概要**：獲得済み・未獲得の称号を一覧表示（進捗％＝対象イベント種別の累計回数／しきい値）で振り返る（[features.md](features.md)「称号一覧（図鑑）画面」）
- **到達点**：S12（期間別集計画面）の`全期間`タブから遷移。累計実績と同じ「自分の積み上げの振り返り」という文脈でまとめる。グローバルナビには追加しない
