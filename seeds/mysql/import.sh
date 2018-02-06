#!/bin/bash
cat /seed/productDB.sql.gz | gunzip |  mysql -u root -pkomparu_root
mysql -u root -pkomparu_root -e "grant all on komparu_dev.* to komparu_db@'%'"
mysql -u root -pkomparu_root -e "grant all on komparu_product_dev.* to komparu_db@'%'"
mysql -u root -pkomparu_root -e "grant all on komparu_jobs_dev.* to komparu_db@'%'"