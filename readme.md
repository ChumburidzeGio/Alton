# API (Lumen)

## Docker setup
We have split the composer dependencies from the api code.   
Since composer files only change rarely, we keep large image overhead small and builds fast.

### Directory structure
```
src/          // Api code 
docker/       // Local development docker support files (nginx, phpfpm)
_data/        // Tmp data mysql, such that mysql doesn't lose state during restarts. This folder can be deleted to start fresh.
composer.*    // Composer files, after modifying composer files you run the setup again to trigger the change
```

### Development environment setup
```
docker-compose down                         // To make sure you start from a clean env
docker-compose up                           // First time docker image layer cache is built, which includes a `composer install --no-autoloader`. Second time this goes faster.
docker-compose exec api composer install    // Run the autoload stuff
curl http://localhost/index.php             // Test => Awesomeness is coming shortly!

// You can stop the running container with CTRL+C, docker-compose stop or docker-compose down
```

### Environment reset
```
docker-compose down                         // To make sure you start from a clean env
rm -rf _data
// Follow setup guide again
```