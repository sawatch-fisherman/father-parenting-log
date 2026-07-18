# father-parenting-log

## 概要

**TotoOps** は、父親の育児参加を記録し、自分自身の育児行動を振り返るためのWebアプリです。

母親や他の父親と比較するのではなく、育児ログ・称号・累計実績・集計によって、父親本人の育児貢献を可視化することを目的としています。

## 作成目的

このリポジトリは、Laravel / Vue.js を使用したポートフォリオ用Webアプリの開発リポジトリです。

現在は実装前の企画整理・要件定義・設計段階であり、まずはドキュメントを通じて以下を整理しています。

- コンセプト
- 機能一覧
- 画面設計
- データモデル / ER図
- 個人情報・集計方針

## 現在の状態

現段階では、Laravel / Vue.js / Docker の実装はまだ行っていません。

今後、設計資料をもとに、`src/` 配下へLaravelアプリケーション、`docker/` 配下へ開発環境用のDocker設定を追加していく予定です。

## 使用予定技術

- PHP
- Laravel
- Vue.js
- TypeScript
- Inertia.js
- Tailwind CSS
- MySQL
- Docker

## ドキュメント

- [コンセプト](docs/concept.md)
- [機能一覧](docs/features.md)
- [画面設計](docs/screens.md)
- [画面詳細レイアウト（ワイヤーフレーム）](docs/wireframes.md)
- [データモデル / ER図](docs/data-model.md)
- [個人情報・集計方針](docs/privacy.md)
- [技術スタック](docs/tech-stack.md)
- [MVP実装計画（タスク分解）](docs/implementation-plan.md)

## 開発者向け

- [開発コマンド集](dev-commands.md)

## 開発名

TotoOps

## 方針

TotoOpsでは、以下の方針を大切にします。

- 母親との育児量比較を目的にしない
- 他の父親との個人ランキングを作らない
- 県別・地域別ランキングを作らない
- 集計は競争ではなく、育児傾向を見るために使う
- 個人情報は必要最小限のみ扱う

## 今後の予定

- 設計資料の整理
- Laravel / Vue.js の実装開始
- Docker開発環境の作成
- テストコードの追加
- GitHub上でポートフォリオとして公開できる形への整備
