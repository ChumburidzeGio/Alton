#!/bin/bash
docker-compose down                         
docker-compose up -d                        
docker-compose exec api composer install    