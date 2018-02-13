# Alton Scrapper

## Introduction

The scrapper module for Alton infrastructure. Using built-in spiders for the most popular housing websites and database of links to be fetched this application gives full API for indexing all houses on Funda.nl and Pararius.nl.

## Code Samples

To simply test the spider just run in terminal

```bash
scrapy fetch --nolog --spider=pararius https://www.pararius.nl/huurwoningen/amsterdam/page-1
```

You can pass ```--headers``` to print the response’s HTTP headers instead of the response’s body and ```--no-redirect``` to not follow HTTP 3xx redirects (default is to follow them)