# ImHere

Just play around

## Config

env variables

| KEY     | VALUES               |
|---------|----------------------|
| APP_ENV | 'testing' / 'production' |
| mail.transport | 'gmail.smtp' |
| gmail.smtp.username | `string`|
| gmail.smtp.password | `string`|
| webapp.url | `string` |


## DB

here we use Doctrine as ORM.

After structure updates, for sync tables, run
```shell
./vendor/bin/doctrine orm:schema-tool:update --dump-sql --force
```
