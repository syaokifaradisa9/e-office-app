# Panduan Manajemen Modul (Lepas Pasang)

Dokumen ini menjelaskan tata cara untuk menambah (memasang) atau menghapus (melepas) modul pada aplikasi E-Office.

---

## 🛠 Memasang Modul Baru

Ikuti langkah-langkah berikut untuk menambahkan modul baru ke dalam sistem:

### 1. Tambahkan Folder Modul
Pindahkan atau buat folder modul Anda di dalam direktori `Modules/`.
Contoh struktur:
```text
Modules/
├── Inventory/
└── MyNewModule/  <-- Modul baru Anda
```

### 2. Aktifkan di Konfigurasi
Buka file `modules_statuses.json` di root project dan tambahkan nama modul Anda dengan status `true`.
```json
{
    "Inventory": true,
    "MyNewModule": true
}
```

### 3. Import ke Sidebar
Daftarkan sidebar modul Anda agar muncul di menu navigasi utama.
1. Buka file `resources/js/Components/layouts/SideBar.tsx`.
2. Tambahkan import komponen sidebar modul Anda:
   ```tsx
   import MyNewModuleSidebar from '../../../../Modules/MyNewModule/resources/assets/js/components/layouts/MyNewModuleSidebar';
   ```
3. Tambahkan komponen tersebut di dalam bagian JSX:
   ```tsx
   {/* My New Module */}
   <MyNewModuleSidebar />
   ```

---

## 🗑 Melepas Modul

Ikuti langkah-langkah berikut untuk menghapus modul dari sistem secara bersih:

### 1. Hapus Folder Modul
Hapus folder modul yang bersangkutan dari direktori `Modules/`.
```bash
rm -rf Modules/MyNewModule
```

### 2. Sesuaikan Konfigurasi
Ubah status modul menjadi `false` atau hapus barisnya di file `modules_statuses.json`.
```json
{
    "Inventory": true,
    "MyNewModule": false
}
```

### 3. Bersihkan Sidebar
1. Buka file `resources/js/Components/layouts/SideBar.tsx`.
2. Hapus baris `import` yang berkaitan dengan modul tersebut.
3. Hapus pemanggilan komponen modul di dalam bagian JSX.

---

> [!IMPORTANT]
> Pastikan untuk menjalankan `npm run dev` setelah melakukan perubahan pada Sidebar agar perubahan dapat terlihat di browser.
