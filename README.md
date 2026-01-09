# 🚗 UAS Traffic App

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.1+-blue?style=flat-square)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4-red?style=flat-square)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

*Sistem Manajemen Lalu Lintas Terintegrasi dengan Interface Modern*

</div>

---

## 📋 Tentang Project

**UAS Traffic App** adalah aplikasi web yang dirancang untuk mengelola dan memonitor lalu lintas secara real-time. Aplikasi ini dibangun dengan framework CodeIgniter 4 dan dilengkapi dengan dashboard interaktif serta RESTful API untuk kemudahan integrasi.

Fitur-fitur unggulan:
- 🎯 **Dashboard Analytics** - Visualisasi data lalu lintas real-time
- 🔌 **RESTful API** - Endpoint API yang lengkap dan terdokumentasi
- 📊 **Sistem Monitoring** - Pantau kondisi jalan dan volume kendaraan
- 🛡️ **Keamanan Tingkat Enterprise** - JWT Authentication dan validasi data
- 💾 **Database Terstruktur** - Schema database yang rapi dan teroptimasi
- 🎨 **UI/UX Modern** - Interface responsif dengan Bootstrap Dashboard

---

## 👥 Tim Pengembang

| Nama | Peran |
|------|-------|
| **Riki** | Pengembang (Developer) |
| **Muchamad Sandy, S.Kom., M.M.SI** | Dosen Pembimbing |

---

## 🚀 Quick Start

### Prasyarat

- PHP 8.1 atau lebih tinggi
- Composer (untuk manajemen dependensi)
- MySQL/MariaDB
- Web Server (Apache/Nginx)

### Extensions yang Diperlukan

- `intl` - Internationalization Support
- `mbstring` - Multi-byte String Support
- `curl` - cURL Support
- `json` - JSON Support

### Instalasi

1. **Clone atau Extract Project**
```bash
cd d:\Application\laragon\laragon\www\uas-traffic-app
```

2. **Install Dependencies**
```bash
composer install
```

3. **Setup Database**
```bash
# Import database dari file traffic.sql
mysql -u root -p your_database < traffic.sql
```

4. **Konfigurasi Environment**
```bash
# Salin file contoh konfigurasi
cp .env.example .env
```
Sesuaikan konfigurasi di file `.env`:
```env
CI_ENVIRONMENT = development

database.default.hostname = localhost
database.default.database = traffic_app
database.default.username = root
database.default.password = your_password
```

5. **Jalankan Development Server**
```bash
php spark serve
```

Akses aplikasi di: `http://localhost:8080`

---

## 📁 Struktur Project

```
uas-traffic-app/
├── app/                      # Aplikasi utama
│   ├── Config/              # File konfigurasi
│   ├── Controllers/         # Controller untuk logic aplikasi
│   ├── Models/              # Model untuk database
│   ├── Services/            # Business logic layer
│   ├── Views/               # Template HTML
│   └── Database/            # Migration & Seeding
├── frontend/                # Frontend Dashboard
│   ├── assets/              # CSS, JS, Images
│   ├── pages/               # HTML Pages
│   └── datta-able-bootstrap-dashboard/  # Bootstrap Template
├── public/                  # Public folder (DocumentRoot)
│   └── index.php            # Entry point
├── system/                  # CodeIgniter Framework Core
├── tests/                   # Unit Tests
├── vendor/                  # Composer packages
├── postman/                 # Postman API Collection
├── writable/                # Cache, Logs, Uploads
└── traffic.sql              # Database Schema
```

---

## 🔌 API Documentation

### Base URL
```
http://localhost:8080/api
```

### Endpoints Utama

#### Traffic Data
```
GET    /api/traffic            # Get semua data lalu lintas
GET    /api/traffic/:id        # Get detail lalu lintas
POST   /api/traffic            # Create data baru
PUT    /api/traffic/:id        # Update data
DELETE /api/traffic/:id        # Delete data
```

**Postman Collection**: Import file `postman/Traffic API.postman_collection.json` ke Postman untuk testing API.

---

## 🛠️ Development Commands

```bash
# Jalankan development server
php spark serve

# Run migrations
php spark migrate

# Generate seed data
php spark db:seed DatabaseSeeder

# Run tests
php vendor/bin/phpunit

# Clear cache
php spark cache:clear

# Check code quality
php spark lint
```

---

## 📊 Database Schema

Aplikasi menggunakan database MySQL dengan tabel-tabel utama:
- `traffic_data` - Data lalu lintas
- `users` - User management
- `logs` - Activity logs
- Dan tabel supporting lainnya

Lihat `traffic.sql` untuk skema lengkap.

---

## 🔐 Security Features

- ✅ **JWT Authentication** - Secure API authentication
- ✅ **CSRF Protection** - Proteksi cross-site request forgery
- ✅ **SQL Injection Prevention** - Query builder dengan parameterized queries
- ✅ **Input Validation** - Validasi ketat untuk semua input
- ✅ **XSS Prevention** - Output escaping otomatis
- ✅ **CORS Configuration** - Configurable CORS rules

---

## 📚 Teknologi Stack

| Layer | Teknologi |
|-------|-----------|
| **Backend Framework** | CodeIgniter 4 |
| **Language** | PHP 8.1+ |
| **Database** | MySQL/MariaDB |
| **Frontend Framework** | Bootstrap 5 |
| **Frontend Template** | Datta Able Dashboard |
| **Authentication** | JWT (Firebase JWT) |
| **Testing** | PHPUnit |

---

## 📝 Dokumentasi Lengkap

- [CodeIgniter 4 Documentation](https://codeigniter.com/user_guide/)
- [Bootstrap Documentation](https://getbootstrap.com/docs/)
- [API Endpoint Documentation](./ENDPOINTS.md) *(jika tersedia)*

---

## 🐛 Troubleshooting

### Error: "Database tidak terhubung"
- Pastikan MySQL service berjalan
- Cek konfigurasi database di file `.env`
- Pastikan database sudah di-create

### Error: "Class not found"
- Jalankan `composer install` atau `composer dump-autoload`
- Pastikan file struktur tidak ada yang corrupt

### Error: "Permission denied" pada folder writable
- Jalankan: `chmod -R 755 writable/` (Linux/Mac)
- Berikan write permission via Properties (Windows)

---

## 📄 License

Proyek ini menggunakan lisensi MIT. Silakan baca file [LICENSE](LICENSE) untuk detail lengkap.

---

## 📞 Support & Contact

Untuk pertanyaan atau masalah teknis, hubungi:
- **Developer**: Riki
- **Supervisor**: Muchamad Sandy, S.Kom., M.M.SI

---

<div align="center">

**Dikembangkan dengan ❤️ untuk UAS**

*Last Updated: January 2026*

</div>
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - If you are still using PHP 7.4 or 8.0, you should upgrade immediately.
> - The end of life date for PHP 8.1 will be December 31, 2025.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library
