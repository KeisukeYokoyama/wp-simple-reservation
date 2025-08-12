# WP Simple Reservation

シンプルな予約管理プラグイン。オンラインで空きスケジュールを確認し、予約できるWordPressプラグインです。

## 機能

### ✅ 実装済み機能

#### 基本機能
- 予約フォームの表示（ショートコード対応）
- レスポンシブデザイン
- 日付選択（カレンダー形式）
- 時間枠選択（Ajax連携）
- 個人情報入力フォーム
- フォームバリデーション
- 予約送信（Ajax）
- 成功/エラーメッセージ表示

#### 管理画面機能
- 予約一覧表示
- 予約編集機能
- スケジュール管理画面
- スケジュール追加・編集・削除
- 複数時間枠登録
- フォーム設定（カスタムフィールド）
- 予約締切日設定

#### 予約制限機能
- 予約締切日設定（○日前まで、○時間前まで）
- 締切日を過ぎた時間枠の視覚的表示（グレーアウト、TEL表示）
- フロントエンド・バックエンド両方でのバリデーション

### 🚧 開発中・予定機能

- メール通知機能
- 予約キャンセル機能
- 検索・フィルター機能
- 統計・レポート機能
- 繰り返しスケジュール機能

## インストール方法

1. このリポジトリをクローンまたはダウンロード
2. `wp-simple-reservation`フォルダを`wp-content/plugins/`に配置
3. WordPress管理画面でプラグインを有効化
4. 管理画面 → 予約管理 で設定を行う

## 使用方法

### ショートコード

基本的な予約フォームを表示：
```
[wp_simple_reservation_form]
```

カスタムタイトルで予約フォームを表示：
```
[wp_simple_reservation_form title="カスタムタイトル"]
```

### 管理画面での設定

1. **スケジュール管理**
   - 予約可能な日時を設定
   - 複数の時間枠を登録可能

2. **予約締切日設定**
   - 予約締切日（○日前まで）
   - 予約締切時間（○時間前まで）

3. **フォーム設定**
   - カスタムフィールドの追加・編集
   - 必須項目の設定

## 技術仕様

- **PHP**: 7.4以上
- **WordPress**: 5.0以上
- **JavaScript**: jQuery
- **CSS**: レスポンシブデザイン対応
- **データベース**: カスタムテーブル使用

## ファイル構成

```
wp-simple-reservation/
├── wp-simple-reservation.php    # メインファイル
├── includes/                    # クラスファイル
│   ├── class-wpsr-admin.php
│   ├── class-wpsr-form-manager.php
│   └── class-wpsr-email-manager.php
├── assets/                      # 静的ファイル
│   ├── css/
│   │   └── wpsr-styles.css
│   └── js/
│       ├── wpsr-scripts.js
│       └── wpsr-admin-scripts.js
├── templates/                   # テンプレートファイル
│   ├── admin/
│   │   ├── reservations.php
│   │   ├── schedules.php
│   │   ├── settings.php
│   │   └── form-settings.php
│   └── reservation-form.php
└── languages/                   # 翻訳ファイル
```

## 開発環境

### 必要な環境
- WordPress 5.0以上
- PHP 7.4以上
- MySQL 5.7以上

### ローカル開発環境の構築
1. Docker環境を使用（推奨）
2. WordPress + MySQL + phpMyAdmin
3. プラグインを`wp-content/plugins/`に配置

## ライセンス

GPL v2 or later

## 作者

Pejite

## サポート

- GitHub Issues: [https://github.com/KeisukeYokoyama/wp-simple-reservation/issues](https://github.com/KeisukeYokoyama/wp-simple-reservation/issues)
- プラグインURI: [https://pejite.com/wp-simple-reservation](https://pejite.com/wp-simple-reservation)

## 更新履歴

### 1.0.0
- 初期リリース
- 基本的な予約機能
- 管理画面機能
- 予約締切日設定機能
