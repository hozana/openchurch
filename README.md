# openchurch

A few commands:

- To generate our schema:
```
vendor/bin/schema generate-types src/Entity/ config/schema.yaml
```
(`bin/console doctrine:schema:update` don't seems to be needed)

- To test the app:
```
php -S 127.0.0.1:8000 -t public
```
