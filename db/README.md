# db サービス

- `conf.d/*.cnf` … mysqld の設定。イメージビルド時に `/etc/mysql/conf.d/` へ入る。
- `init/*.sql` / `init/*.sh` … **初回起動時のみ** (`ssp_db-data` volume が空のとき)
  ファイル名順に実行される。スキーマや初期データはここへ。

例: `db/init/01-schema.sql`

```sql
CREATE TABLE IF NOT EXISTS memo (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  body TEXT
);
```

設定・初期化ファイルを変えたときは以下で作り直す（`-v` は DB データも消える）:

```sh
docker compose down -v && docker compose up -d --build
```
