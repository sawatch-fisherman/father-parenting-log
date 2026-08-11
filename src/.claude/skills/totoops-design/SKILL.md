---
name: totoops-design
description: TotoOps の画面実装（Vue コンポーネント・Blade テンプレート・Tailwind クラス）をするときに必ず適用する。色・タイポグラフィ・スペーシング・ボタン/フォーム/カード等のコンポーネント仕様、フォーカス/エラー/無効状態の表現、ダークモード非対応、禁止パターン（比較UI・叱責トーン等）を定義する。新しい画面・コンポーネントを作るとき、既存画面にスタイルを追加するとき、色や余白の値で迷ったとき、コードレビューでスタイルを確認するときに使う。
---

# TotoOps デザインシステム運用ガイド

リポジトリルートの `DESIGN.md` が正。ここでは、その内容を**実装時にすぐ使える Tailwind クラス**に落とし込んだチートシートと、運用上の注意点だけをまとめる。仕様の理由・トレードオフは DESIGN.md 側にあるので、迷ったら DESIGN.md の該当章を読む。

## 1. 大前提

- **`DESIGN.md` のトークンは `src/resources/css/app.css` の `@theme` に反映済み。** 生の HEX 値・Tailwind標準のグレー系（`gray-*`・`slate-*`等）・`bg-white`/`text-black`のような素の色は新規コードに書かない。必ず下記のトークンクラスを使う。
- **ダークモードは非対応。** DESIGN.md はダークモードを定義していない（Background は単一トーン）ため、`dark:` バリアントは使わない。
- DESIGN.md に無い色・サイズが必要になった場合、その場で HEX 値を決め打ちしない。既存トークンで表現できないか先に検討し、それでも無ければユーザーに選択肢を提示する（CLAUDE.md「決定事項を確定させる前に、選択肢とトレードオフを提示することを優先する」と同じ原則）。
- DESIGN.md 側の色コードが変わった場合は `src/resources/css/app.css` の `@theme` を同じ値に更新し、`grep -rniE "<旧HEX>" --include="*.css" --include="*.vue" --include="*.php" .`（vendor/node_modules/public/build を除く）で古い値の参照が残っていないか確認する。

## 2. カラートークン（DESIGN.md 5.2）

`--color-*` を `@theme` に定義しているため、Tailwind v4 が自動で `bg-*`/`text-*`/`border-*`/`ring-*` 等のユーティリティを生成する。

| 用途 | クラス例 | 備考 |
|---|---|---|
| 主要CTA背景／ブランドロゴ色 | `bg-primary` / `text-primary` | ホバーは `hover:bg-primary-hover` |
| 選択済み状態の淡い背景 | `bg-primary-subtle` | 8タイルグリッドの選択状態、バッジ背景（例：必須バッジ） |
| 補助操作の文字・枠線 | `text-secondary` | Secondaryボタンの文字色 |
| 祝福演出限定（称号解除等） | `bg-accent` / `text-accent` | 通常のUIには使わない。希少性を保つ |
| ページ全体の背景 | `bg-background` | `<body>` に付与済み（`app.blade.php`） |
| カード・入力欄・モーダルの面 | `bg-surface` | 常に `#FFFFFF` |
| 本文・見出しの文字色 | `text-text-primary` | 純黒ではなく温かいダークグレー |
| 補足文・タイムスタンプ・プレースホルダー | `text-text-secondary` | |
| 区切り線・入力欄枠・カード輪郭 | `border-border` | 影ではなくborderで面を区切る（DESIGN.md 8.2） |
| 保存成功トースト等 | `bg-success` / `text-success` | 青系。Primaryの緑と役割を混同しない |
| やさしいリマインド表示 | `bg-warning` / `text-warning` | 刺激の強い赤系警告色は使わない |
| フォームエラー・送信失敗 | `border-error` / `text-error` | 状態色。ブランド色（Primary）や必須バッジには使わない |
| 補足情報バナー・ツールチップ | `bg-info` / `text-info` | |

## 3. タイポグラフィトークン（DESIGN.md 6.2）

`--text-*` を `@theme` に定義済み（サイズ＋行間セット）。**ウェイトは別途 `font-bold`（700）／`font-semibold`（600）／`font-normal`（400・既定）をクラスで併用すること**（トークン側はウェイトを含まない）。

| ロール | クラス | ウェイト | 用途 |
|---|---|---|---|
| Display | `text-display` | `font-bold` | S2等、画面内で一度だけ使う特大見出し |
| Heading L | `text-heading-l` | `font-bold` | 画面タイトル（S3/S8/S12/S13/S7等） |
| Heading M | `text-heading-m` | `font-semibold` | カード・セクション内の小見出し |
| Body | `text-body` | `font-normal` | 本文標準（入力欄の値もこれ） |
| Body Small | `text-body-sm` | `font-normal` | タイムスタンプ・補足キャプション・エラー文言。**これより小さいサイズ（`text-xs`等）は使わない** |
| Label / Button | `text-label` | `font-semibold` | ボタン内テキスト、フォームラベル、タブ項目 |

## 4. コンポーネントのクラス早見表（DESIGN.md 10章）

実装例は `resources/js/Pages/Auth/Login.vue`・`Pages/Profile/Register.vue`・`Pages/Settings/ProfileEdit.vue`・`Components/ProfileFormFields.vue` を参照（既にこの規約で書かれている）。

**Primaryボタン**（画面ごとに原則1つだけ）
```
rounded-xl bg-primary px-5 py-3 text-label font-semibold text-white
hover:bg-primary-hover
focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25
disabled:pointer-events-none disabled:bg-border disabled:text-text-secondary
```

**入力欄・セレクト**
```
w-full rounded-md border bg-surface px-4 py-3 text-body text-text-primary
focus:border-primary focus:outline-none focus:ring-[3px] focus:ring-primary/25
```
枠線は通常時 `border-border`、エラー時は `border-error` を動的に切り替える（`form.errors.xxx` の有無で分岐）。

**フォームラベル**：`block text-label font-semibold text-text-primary`

**エラー文言**（入力欄の直下）：`text-body-sm text-error`。DESIGN.md 11章は「アイコン＋文言」を求めているが、Heroiconsが未導入（DESIGN.md 13章の未確認事項）のため現状は文言のみ。Heroicons導入時に揃えること。

**タップ領域**：全てのタップ可能要素は最小 `44×44px` を確保する（DESIGN.md 9章）。

**角丸**：入力欄・カード類は `rounded-md`(6px)/`rounded-xl`(12px)/`rounded-2xl`(16px) の3段階に統一する。それ以外の任意の角丸を増やさない。

## 5. レイアウト（DESIGN.md 8.3）

- ナビ無しの単機能画面（S2/S4/S8/S9/S10/S11）：上部に `pt-6`（24px）の余白を置いた縦1カラム。画面中央に固定で縦centeringしない。
- ナビ有り画面（S3/S12/S13/S7）：メインエリアに `p-8`（32px）相当の内側パディング。
- デスクトップのコンテンツ幅は `max-w-[960px]` を基本とする。

## 6. 禁止パターン（DESIGN.md 15章、抜粋）

- 母親・他の父親との比較UI、個人／地域単位のランキング（許容は育児タスク種別の順位のみ）。
- 「サボり通知」のような赤い警告バナー・叱責トーン。エラー文言は原因ではなく対処法を主語にする。
- 色だけで状態を伝える表現（アイコン・文言の併記が無い状態色の使用）。
- `outline: none` だけでフォーカス表示を消す実装（必ず `focus-visible:ring-*` 等で代替する）。
- 子どもの氏名・誕生日・写真、本人の本名、都道府県・市区町村、位置情報をフォーム・表示に含めること。
