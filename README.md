## Jumia API Integration with Google Sheet API

## Overview

An E-commerce API. All Products stored into a google sheet. Fetching from Google sheet and Create or Update product the vendor store.

## Integration Process

- Generate JWT access token from Jumia.
- Generate Google sheet API and credentials from Google.
- Integrate With Jumia API.

## Work Flow

- Fetch all products from Jumia API
- Fetch all products from Google sheet
- Check condition if product exists or not
- If product exists, update product
- If product does not exist, create product

### Changes Client Requirement

Once created this functionality client change some requirement. He Created Product on his store. I need to update product stock and price every 6 hours. Then i need to update product stock and price every 6 hours.

## Challenges

- API was not ready and has some issues. I Had to research and contact with Jumia support team and fix it.
- Google sheet API. I didn't know that. I had to learn it.
- Business logic was not clear for me. I had to research and implement it.
- First the code was written procedural way. Then I changed it to object oriented way.

## How to use

- Fetch git repo
- Composer install
- Integrate Google sheet API
- Integrate Jumia API
- Run index.php

## Dependencies

- PHP
- Composer
- Jumia API
- Google API PHP Client
- Google Sheets API PHP Client

## Author

[Muhammad Shah jalal](https://github.com/shahjalal132)