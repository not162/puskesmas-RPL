<?php

/*
| Formularium Puskesmas (OBT-001 s.d. OBT-020) + data awal mesin rekomendasi.
| Dipakai seeder & App\Support\RekomendasiObat. Daftar obat tetap dikelola apoteker di menu Obat & Stok.
*/
return [
    'kategori' => [
        'Demam, Nyeri & Peradangan', 'Batuk, Sesak & Alergi', 'Lambung & Pencernaan',
        'Penyakit Kronis', 'Antibiotik', 'Vitamin & Pemulihan', 'Suplemen Ibu Hamil (KIA/ANC)',
    ],

    // kode => [nama, bentuk, satuan, kategori, zat aktif, golongan, poli, keamanan hamil, kegunaan, indikasi, jumlah, aturan, catatan, stok]
    'obat' => [
        'OBT-001' => ['Parasetamol 500 mg', 'Tablet', 'tablet', 'Demam, Nyeri & Peradangan', 'Parasetamol', null, 'semua', 'aman', 'Pereda demam & nyeri ringan–sedang', 'demam, panas, nyeri, sakit kepala, pusing, pegal, ispa, flu, sakit gigi, meriang', 10, '3x1 sesudah makan jika demam', 'Jangan melebihi 4 kali sehari.', 500],
        'OBT-002' => ['Amoksisilin 500 mg', 'Kapsul', 'kapsul', 'Antibiotik', 'Amoksisilin', 'antibiotik', 'umum', 'hati_hati', 'Antibiotik infeksi bakteri', 'infeksi bakteri, faringitis, tonsilitis, radang tenggorokan, amandel, abses gigi, otitis, ispa bakteri, sinusitis', 15, '3x1 sesudah makan, dihabiskan', 'Antibiotik: wajib resep dan harus dihabiskan.', 300],
        'OBT-003' => ['Ibuprofen 400 mg', 'Tablet', 'tablet', 'Demam, Nyeri & Peradangan', 'Ibuprofen', 'oains', 'umum', 'hindari', 'Antiinflamasi & pereda nyeri', 'nyeri, radang, sakit gigi, nyeri sendi, keseleo, nyeri otot, haid, dismenore, demam', 10, '3x1 sesudah makan', 'Diminum sesudah makan. Hindari pada kehamilan trimester akhir dan maag berat.', 200],
        'OBT-004' => ['Cetirizine 10 mg', 'Tablet', 'tablet', 'Batuk, Sesak & Alergi', 'Cetirizine', null, 'umum', 'hati_hati', 'Antihistamin untuk alergi', 'alergi, gatal, bersin, pilek alergi, rinitis, biduran, urtikaria, ruam, mata gatal', 10, '1x1 malam', 'Dapat menyebabkan kantuk.', 250],
        'OBT-005' => ['Omeprazole 20 mg', 'Kapsul', 'kapsul', 'Lambung & Pencernaan', 'Omeprazole', null, 'umum', 'hati_hati', 'Menurunkan asam lambung', 'maag, asam lambung, gerd, ulu hati, nyeri ulu hati, dispepsia, tukak lambung, perih lambung', 10, '1x1 pagi sebelum makan', 'Diminum 30 menit sebelum makan pagi.', 200],
        'OBT-006' => ['Antasida Doen', 'Tablet Kunyah', 'tablet', 'Lambung & Pencernaan', 'Aluminium hidroksida + Magnesium hidroksida', null, 'semua', 'aman', 'Menetralkan asam lambung', 'maag, kembung, ulu hati, perih lambung, dispepsia, mual, begah', 10, '3x1 dikunyah sebelum makan', 'Dikunyah, jangan ditelan utuh.', 400],
        'OBT-007' => ['Amlodipine 5 mg', 'Tablet', 'tablet', 'Penyakit Kronis', 'Amlodipine', null, 'umum', 'konsultasi', 'Hipertensi / tekanan darah tinggi', 'hipertensi, darah tinggi, tensi tinggi, tekanan darah tinggi, kontrol hipertensi', 30, '1x1 pagi', 'Minum rutin setiap hari walau merasa sehat.', 300],
        'OBT-008' => ['Metformin 500 mg', 'Tablet', 'tablet', 'Penyakit Kronis', 'Metformin', null, 'umum', 'konsultasi', 'Diabetes melitus tipe 2', 'diabetes, dm, gula darah, kencing manis, hiperglikemia, kontrol diabetes', 30, '2x1 sesudah makan', 'Diminum bersama atau sesudah makan.', 300],
        'OBT-009' => ['Vitamin B Kompleks', 'Tablet', 'tablet', 'Vitamin & Pemulihan', 'Vitamin B1, B6, B12', 'suplemen', 'semua', 'aman', 'Vitamin untuk pemulihan', 'lemas, pemulihan, kesemutan, nafsu makan, kelelahan, ispa, demam', 10, '1x1 pagi sesudah makan', null, 500],
        'OBT-010' => ['Asam Folat 400 mcg', 'Tablet', 'tablet', 'Suplemen Ibu Hamil (KIA/ANC)', 'Asam folat', 'suplemen', 'kandungan', 'aman', 'Perkembangan saraf janin', 'hamil, kehamilan, anc, program hamil, trimester, janin, kontrol kehamilan', 30, '1x1 pagi hari', 'Mulai sejak awal kehamilan atau program hamil.', 400],
        'OBT-011' => ['Tablet Tambah Darah (Fe)', 'Tablet', 'tablet', 'Suplemen Ibu Hamil (KIA/ANC)', 'Ferro sulfat + asam folat', 'suplemen', 'kandungan', 'aman', 'Mencegah & mengatasi anemia ibu hamil', 'anemia, kurang darah, hamil, kehamilan, anc, pucat, lemas, hb rendah, trimester', 30, '1x1 malam sebelum tidur', 'Minum malam hari dengan air putih atau jus jeruk; jangan bersama teh, kopi, atau susu.', 600],
        'OBT-012' => ['Kalsium Laktat 500 mg', 'Tablet', 'tablet', 'Suplemen Ibu Hamil (KIA/ANC)', 'Kalsium laktat', 'suplemen', 'kandungan', 'aman', 'Kalsium ibu & tulang janin', 'hamil, kehamilan, anc, kram, tulang, kalsium, trimester', 30, '1x1 siang sesudah makan', 'Beri jarak 2 jam dengan Tablet Tambah Darah.', 350],
        'OBT-013' => ['Salbutamol 2 mg', 'Tablet', 'tablet', 'Batuk, Sesak & Alergi', 'Salbutamol', null, 'umum', 'hati_hati', 'Pelega saluran napas / asma', 'sesak, asma, mengi, bronkospasme, napas berbunyi, bengek', 10, '3x1 bila sesak', 'Dapat menyebabkan berdebar.', 150],
        'OBT-014' => ['Ambroxol 30 mg', 'Tablet', 'tablet', 'Batuk, Sesak & Alergi', 'Ambroxol', null, 'umum', 'hati_hati', 'Pengencer dahak', 'batuk, batuk berdahak, dahak, berdahak, ispa, bronkitis', 10, '3x1 sesudah makan', 'Perbanyak minum air putih.', 300],
        'OBT-015' => ['Oralit Sachet 200 ml', 'Sachet', 'sachet', 'Lambung & Pencernaan', 'Garam rehidrasi oral', null, 'semua', 'aman', 'Rehidrasi saat diare/muntah', 'diare, mencret, dehidrasi, muntah, gastroenteritis, buang air cair, bab cair, berak cair', 6, 'Larutkan 1 sachet dalam 200 ml air, minum setiap selesai BAB cair', 'Larutan habiskan dalam 24 jam.', 150],
        'OBT-016' => ['Zinc Sulfat 20 mg', 'Tablet', 'tablet', 'Lambung & Pencernaan', 'Zinc sulfat', null, 'umum', 'konsultasi', 'Terapi pendamping diare', 'diare, mencret, gastroenteritis, buang air cair, bab cair, berak cair', 10, '1x1 selama 10 hari', 'Lanjutkan 10 hari walau diare sudah berhenti.', 200],
        'OBT-017' => ['Cefadroxil 500 mg', 'Kapsul', 'kapsul', 'Antibiotik', 'Cefadroxil', 'antibiotik', 'umum', 'hati_hati', 'Antibiotik infeksi bakteri', 'infeksi bakteri, infeksi kulit, bisul, selulitis, isk, infeksi saluran kemih, faringitis, tonsilitis', 10, '2x1 sesudah makan, dihabiskan', 'Antibiotik: wajib resep dan harus dihabiskan.', 150],
        'OBT-018' => ['Domperidone 10 mg', 'Tablet', 'tablet', 'Lambung & Pencernaan', 'Domperidone', null, 'umum', 'konsultasi', 'Mengatasi mual & muntah', 'mual, muntah, kembung, begah, dispepsia', 10, '3x1 sebelum makan', 'Diminum 15–30 menit sebelum makan.', 200],
        'OBT-019' => ['Vitamin C 500 mg', 'Tablet', 'tablet', 'Vitamin & Pemulihan', 'Asam askorbat', 'suplemen', 'semua', 'aman', 'Daya tahan tubuh & pemulihan', 'daya tahan, pemulihan, flu, sariawan, ispa, lemas, imun', 10, '1x1 sesudah makan', null, 400],
        'OBT-020' => ['Loratadine 10 mg', 'Tablet', 'tablet', 'Batuk, Sesak & Alergi', 'Loratadine', null, 'umum', 'hati_hati', 'Antihistamin (tidak terlalu mengantuk)', 'alergi, gatal, bersin, pilek alergi, rinitis, biduran, urtikaria', 10, '1x1 pagi', 'Lebih sedikit menimbulkan kantuk.', 18],
    ],

    /*
    | Graf awal "sering diresepkan bersama" (bobot prior). Bobot akhir = prior + jumlah resep
    | sungguhan yang memuat kedua obat (dipelajari otomatis dari riwayat resep).
    */
    'pasangan' => [
        ['OBT-001', 'OBT-009', 4], // contoh resep U-001 ISPA ringan/demam
        ['OBT-001', 'OBT-014', 3], ['OBT-001', 'OBT-019', 3], ['OBT-014', 'OBT-019', 2],
        ['OBT-015', 'OBT-016', 5], // standar diare: oralit + zinc
        ['OBT-010', 'OBT-011', 5], // contoh resep K-001 ANC trimester 2
        ['OBT-011', 'OBT-012', 3], ['OBT-010', 'OBT-012', 3],
        ['OBT-006', 'OBT-018', 2], ['OBT-005', 'OBT-018', 2], ['OBT-003', 'OBT-005', 2],
        ['OBT-002', 'OBT-001', 2], ['OBT-017', 'OBT-001', 1], ['OBT-013', 'OBT-014', 2],
        ['OBT-004', 'OBT-019', 1], ['OBT-007', 'OBT-008', 2],
    ],

    // Tips hidup sehat (diterbitkan sebagai Info Sehat)
    'tips' => [
        ['Cukupi cairan & pola makan seimbang', 'Minum air putih minimal 2 liter sehari, perbanyak sayur dan buah, serta batasi gula, garam (mencegah hipertensi), dan gorengan berlebih.'],
        ['Aktivitas fisik & istirahat teratur', 'Lakukan olahraga ringan–sedang 30 menit sehari seperti jalan cepat, dan usahakan tidur cukup 7–8 jam setiap malam.'],
        ['Bijak menggunakan obat', 'Jangan minum antibiotik (Amoksisilin, Cefadroxil) tanpa resep dokter dan habiskan sesuai anjuran. Sampaikan riwayat alergi obat saat mendaftar. Pantau tekanan darah dan gula darah rutin bila punya riwayat hipertensi atau diabetes.'],
        ['Etika batuk & kebersihan', 'Cuci tangan dengan sabun sebelum makan dan pakai masker saat flu/ISPA agar tidak menular.'],
        ['Ibu hamil: rutin periksa kehamilan (ANC)', 'Kontrol kehamilan sesuai jadwal Poli Kandungan, termasuk pemeriksaan tekanan darah, berat badan, tinggi fundus, dan detak jantung janin.'],
        ['Ibu hamil: minum Tablet Tambah Darah dengan tepat', 'Minum Tablet Fe malam hari dengan air putih atau jus kaya vitamin C. Hindari meminumnya bersama teh, kopi, atau susu.'],
        ['Ibu hamil: kenali tanda bahaya', 'Segera ke IGD/Puskesmas bila terjadi perdarahan, sakit kepala hebat dengan pandangan kabur, demam tinggi, gerakan janin berkurang drastis, atau nyeri perut hebat.'],
        ['Ibu hamil: nutrisi & hidrasi', 'Makan porsi kecil tapi sering saat mual, cukupi protein (telur, ikan matang, tahu, tempe), dan minum cukup air.'],
    ],
];

