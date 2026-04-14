# Panduan Deployment SIAKAD (Cloud VPS)

Panduan ini ditujukan untuk mengonlinekan sistem SIAKAD ke Cloud VPS agar mampu menangani kapasitas **ribuan pengguna**.

## 1. Spesifikasi Server Minimum (Rekomendasi)

- **VCPU**: 4 Core (Dedicated vCPU lebih baik)
- **RAM**: 8 GB
- **Penyimpanan**: 50 GB SSD/NVMe
- **OS**: Ubuntu 22.04 LTS

## 2. Instalasi Web Server (Nginx + PHP-FPM)

Gunakan Nginx karena performanya lebih stabil untuk koneksi simultan tinggi dibanding Apache.

```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Instal Nginx & MariaDB
sudo apt install nginx mariadb-server -y

# Instal PHP 8.2 dan ekstensi yang dibutuhkan
sudo apt install php8.2-fpm php8.2-mysql php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip -y
```

## 3. Konfigurasi Database

1. Masuk ke MariaDB: `sudo mysql`
2. Buat database: `CREATE DATABASE siakad_db;`
3. Buat user & hak akses:

   ```sql
   CREATE USER 'siakad_user'@'localhost' IDENTIFIED BY 'PasswordRahasia123!';
   GRANT ALL PRIVILEGES ON siakad_db.* TO 'siakad_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

## 4. Persiapan Berkas Kode

1. Clone repositori Git Anda ke `/var/www/siakad`.
2. Salin `.env.example` menjadi `.env` dan sesuaikan nilainya:

   ```env
   DB_HOST=localhost
   DB_NAME=siakad_db
   DB_USER=siakad_user
   DB_PASS=PasswordRahasia123!
   ```

3. Atur izin folder:

   ```bash
   sudo chown -R www-data:www-data /var/www/siakad
   sudo chmod -R 755 /var/www/siakad
   sudo chmod -R 775 /var/www/siakad/uploads
   sudo chmod -R 775 /var/www/siakad/backups
   ```

## 5. Konfigurasi Nginx (Virtual Host)

Buat file `/etc/nginx/sites-available/siakad.conf`:

```nginx
server {
    listen 80;
    server_name domain-kampus-anda.ac.id;
    root /var/www/siakad;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Aktifkan config: `sudo ln -s /etc/nginx/sites-available/siakad.conf /etc/nginx/sites-enabled/` dan `sudo nginx -t && sudo systemctl restart nginx`.

## 6. Keamanan Produksi (PENTING)

1. Aktifkan HTTPS menggunakan Certbot:
   `sudo apt install certbot python3-certbot-nginx -y`
   `sudo certbot --nginx -d domain-kampus-anda.ac.id`
2. Matikan `display_errors` di `php.ini` server produksi.
3. Ubah seluruh password default setelah sistem online.

   ```sql
   UPDATE users SET password = PASSWORD('Baru!') WHERE username = 'admin';
   ```
