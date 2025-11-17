# Troubleshooting 502 Bad Gateway and SSL Errors

## Issue 1: SSL Error in Postman

**Error:** `Error:write EPROTO 80186496:error:100000f7:SSL routines:OPENSSL_internal:WRONG_VERSION_NUMBER`

**Cause:** Postman is trying to use HTTPS, but the server only serves HTTP.

**Solution:**
1. In Postman, make sure you're using **HTTP** (not HTTPS):
   - ✅ Correct: `http://localhost:8000`
   - ❌ Wrong: `https://localhost:8000`

2. If Postman auto-detects HTTPS:
   - Click on the URL bar
   - Change `https://` to `http://`
   - Or disable SSL certificate verification in Postman Settings (not recommended for production)

## Issue 2: 502 Bad Gateway

**Error:** `502 Bad Gateway` when accessing the API

**Diagnosis Steps:**

1. **Check if containers are running:**
   ```bash
   docker-compose ps
   ```
   All services (app, webserver, db, redis) should show "Up" status.

2. **Check PHP-FPM is listening on port 9000:**
   ```bash
   docker-compose exec app netstat -tlnp | grep 9000
   ```
   Should show: `tcp 0 0.0.0.0:9000 0.0.0.0:* LISTEN`

3. **Check app container logs:**
   ```bash
   docker-compose logs app
   ```
   Look for PHP-FPM startup messages and any errors.

4. **Check nginx logs:**
   ```bash
   docker-compose logs webserver
   ```
   Look for connection errors to `app:9000`.

5. **Test PHP-FPM connection from nginx container:**
   ```bash
   docker-compose exec webserver nc -zv app 9000
   ```
   Should show: `app (172.x.x.x:9000) open`

6. **Test health endpoint:**
   ```bash
   curl http://localhost:8000/health
   ```
   Should return: `healthy`

**Common Fixes:**

1. **Rebuild containers after configuration changes:**
   ```bash
   docker-compose down
   docker-compose up -d --build
   ```

2. **Restart PHP-FPM:**
   ```bash
   docker-compose restart app
   ```

3. **Check PHP-FPM configuration:**
   ```bash
   docker-compose exec app cat /usr/local/etc/php-fpm.d/www.conf | grep listen
   ```
   Should show: `listen = 0.0.0.0:9000`

4. **Verify network connectivity:**
   ```bash
   docker-compose exec webserver ping -c 2 app
   ```

5. **Check file permissions:**
   ```bash
   docker-compose exec app ls -la /var/www/html/public
   ```

## Quick Verification

Run these commands to verify everything is working:

```bash
# 1. Check containers
docker-compose ps

# 2. Check PHP-FPM is running
docker-compose exec app ps aux | grep php-fpm

# 3. Test health endpoint
curl http://localhost:8000/health

# 4. Test API endpoint (replace with your actual endpoint)
curl http://localhost:8000/api/health
```

## Still Having Issues?

1. **View all logs:**
   ```bash
   docker-compose logs -f
   ```

2. **Check nginx error log:**
   ```bash
   docker-compose exec webserver cat /var/log/nginx/error.log
   ```

3. **Check PHP-FPM error log:**
   ```bash
   docker-compose exec app tail -f /var/log/php-fpm.log
   ```

4. **Rebuild from scratch:**
   ```bash
   docker-compose down -v
   docker-compose up -d --build
   ```

