# Agent Notes for O3-Shop (shop-ce)

## Docker Setup

- The project directory is **bind-mounted** into the container at `/var/www/html`.
- **Do NOT use `docker cp`** to copy files into the container. File changes on the host are immediately visible inside the container.
- Container name: `o3shop-app`
- Database container: `o3shop-db` (MariaDB 10.11, named volume `db_data`)

## Running Tests

- **Full test suite from host**: `bash docker.sh test-all` (from project root)
- **Individual tests inside container**:
  ```bash
  docker exec o3shop-app bash -c "cd /var/www/html && sed -i 's/^O3SHOP_CONF_DBNAME=\"o3shop\"$/O3SHOP_CONF_DBNAME=\"o3shop-test\"/' .env && php vendor/bin/phpunit --bootstrap vendor/o3-shop/testing-library/bootstrap.php --no-coverage tests/Unit/Path/To/TestFile.php 2>&1; sed -i 's/^O3SHOP_CONF_DBNAME=\"o3shop-test\"$/O3SHOP_CONF_DBNAME=\"o3shop\"/' .env"
  ```
- `tests/phpunit.xml` has `stopOnError="true" stopOnFailure="true"` — any error/failure aborts the run.
- `--exclude-group quarantine` is used for normal test runs. `docker.sh quarantine` runs quarantine tests separately.
- `install_shop: true` in `test_config.yml` — `runtests` wrapper always rebuilds the test DB.

## Database

- Production DB: `o3shop`, Test DB: `o3shop-test`
- DB credentials: user=o3shop, pwd=o3shop, root pwd=supersecret
- `.env` file controls which DB is active (`O3SHOP_CONF_DBNAME`)
