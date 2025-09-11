
## start server

```bash
symfony server:start
```

## database

update migrations
```
php bin/console doctrine:migrations:diff
```

update database
```
php bin/console doctrine:migrations:migrate 
```

## update API

Cache update
```
sudo -u www-data php bin/console cache:clear
```