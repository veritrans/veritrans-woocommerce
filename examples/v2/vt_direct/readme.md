Veritrans VT-Direct in PHP
===========================

Contoh sederhana untuk mengimplementasikan VT-Direct dengan PHP.

### Cara penggunaan
1. Download semua file dalam repositori ini, letakkan di server (Misalnya: folder htdocs, jika Anda menggunakan XAMPP atau MAMP).
2. Ubah konfigurasi `server_key` di file `checkout_process.php` sesuai dengan yang ada di Merchant Administration Portal (MAP) di halaman Setting >> Access Keys.
3. Ubah konfigurasi`client_key` di file `checkout.html` sesuai dengan yang ada di Merchant Administration Portal (MAP) di halaman Setting >> Access Keys.
4. Selesai. Buka checkout.html dari browser.


### Production Environment
Jika Anda sudah selesai melakukan testing dan sudah siap untuk go live di Production Environment, ada 2 hal yang perlu dipastikan:

1. Arahkan konfigurasi `$endpoint` di file `checkout_process.php` ke: 

  ```
  https://api.veritrans.co.id/v2/charge
  ```

2. Gunakan library javascript veritrans production di file `checkout.html`:

  ```
  <script src=”https://api.veritrans.co.id/assets/js/veritrans.js”></script>
  ```


