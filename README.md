
# start server

```bash
symfony server:start
```

# database

update migrations
```
php bin/console doctrine:migrations:diff
```

update database
```
php bin/console doctrine:migrations:migrate 
```