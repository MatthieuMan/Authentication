# Authentication

You only need `docker` and `docker-compose` installed

## Start server

The following command will start the development server at URL http://127.0.0.1:8000/, a PhpMyAdmin at url http://127.0.0.1:8080/ and a mail server at http://localhost:8025/:

```bash
docker-compose up -d
```

### Install depedencies:
```json
docker-compose exec php composer install
```


### Create database and run migration:
```
docker-compose exec php bin/console doctrine:database:create
```

Then : 
```
docker-compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

### Build assets

Run the command below to build assets, such as css :

```
npm install
```

```
npm run dev
```