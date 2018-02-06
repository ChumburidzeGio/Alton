# API (Lumen)

## Docker setup
We have split the composer dependencies from the api code.   
Since composer files only change rarely, we keep large image overhead small and builds fast.

### Directory structure
```
src/          // Api code 
composer.*    // Composer files, after modifying composer files you run the setup again to trigger the change
docker/       // Local development docker support files (nginx, phpfpm)
seeds/        // Place to put mysql seed files for the docker environment
_data/        // Tmp data mysql, such that mysql doesn't lose state during restarts. This folder can be deleted to start fresh.
```

### Development environment setup
```
docker-compose down                         // To make sure you start from a clean env
docker-compose up -d                          // First time docker image layer cache is built, which includes a `composer install --no-autoloader`. Second time this goes faster.
docker-compose exec api composer install    // Run the autoload stuff
curl http://localhost/index.php             // Test => Awesomeness is coming shortly!

// You can stop the running container with CTRL+C, docker-compose stop or docker-compose down
```

### Seed database
```
// Seed the DB, put productDB.sql.gz in `seeds/mysql/`
docker-compose exec mysql bash /seed/import.sh
```

### Environment reset
```
docker-compose down                         // To make sure you start from a clean env
rm -rf _data
// Follow setup guide again
```