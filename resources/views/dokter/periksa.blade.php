@extends('layouts.app')
@section('title', 'Pemeriksaan '.$a->nomor)
@section('content')
@php
  $kandungan = $a->spesialisasi->nama === 'Kandungan';
  $kelompok = $obat->groupBy(fn ($o) => $o->kategori ?: 'Lainnya');
  $opsi = function ($pilih = null) use ($kelompok) {
      $h = '<option value="">Ketik atau pilih obat, mis. OBT-001 Parasetamol</option>';
      foreach ($kelompok as $kat => $daftar) {
          $h .= '<optgroup label="'.e($kat).'">';
          foreach ($daftar as $o) {
              $h .= '<option value="'.$o->id_obat.'"'.($pilih == $o->id_obat ? ' selected' : '')
                  .' data-jumlah="'.$o->jumlah_bawaan.'" data-aturan="'.e($o->aturan_bawaan).'" data-satuan="'.e($o->satuan).'" data-stok="'.$o->stok.'"'
                  .' data-guna="'.e($o->kegunaan).'" data-catatan="'.e($o->catatan).'" data-hamil="'.$o->keamanan_hamil.'" data-golongan="'.e($o->golongan).'">'
                  .e($o->label()).' · stok '.$o->stok.'</option>';
          }
          $h .= '</optgroup>';
      }
      return $h;
  };
@endphp
<div class="grid periksa-grid">
  <form class="card" method="POST" action="{{ route('dokter.periksa.simpan', $a) }}" id="form-periksa">@csrf
    <div class="card-h"><h3>{{ $a->pasien->nama }} · {{ $a->nomor }}</h3>@include('partials.status', ['s' => $a->status])</div>
    @include('partials.field', ['name' => 'keluhan', 'label' => 'Anamnesis / keluhan', 'type' => 'textarea', 'value' => $a->keluhan_awal ?? $a->konsultasi?->keluhan, 'attrs' => 'required rows="3" placeholder="Contoh: demam 3 hari, batuk berdahak, nafsu makan turun" data-rekom-teks'])
    @include('partials.field', ['name' => 'diagnosis', 'label' => 'Diagnosis', 'type' => 'textarea', 'value' => $a->konsultasi?->diagnosis, 'attrs' => 'required rows="2" placeholder="'.($kandungan ? 'Contoh: Kontrol ANC trimester 2, kehamilan normal' : 'Contoh: ISPA ringan / febris').'" data-rekom-teks'])

    <div class="flex between mt"><h3 class="mb0">Resep obat</h3><span class="muted" style="font-size:13px">Kosongkan bila tidak perlu obat. Resep otomatis diteruskan ke farmasi.</span></div>

    {{-- Saran obat (rekomendasi hibrida) --}}
    <div class="saran-obat" id="saran-obat" aria-live="polite">
      <div class="flex between"><b><x-ico n="pill"/> Saran obat</b>
        <span class="muted saran-ket" title="Graf resep bersama 45% · kecocokan keluhan 35% · riwayat peresepan 20%">berdasarkan resep serupa, keluhan, dan riwayat · keputusan tetap pada dokter</span></div>
      <div id="saran-isi" class="saran-isi"><span class="muted">Isi keluhan/diagnosis atau pilih obat untuk melihat saran…</span></div>
    </div>

    <div id="rx-rows">
      @foreach (old('obat', [[]]) as $i => $row)
        <div class="rx-blok">
          <div class="rx-row">
            <select name="obat[{{ $i }}][id_obat]" class="input rx-obat" aria-label="Obat">{!! $opsi($row['id_obat'] ?? null) !!}</select>
            <input name="obat[{{ $i }}][jumlah]" type="number" min="1" class="input rx-jumlah" placeholder="Jumlah" aria-label="Jumlah" value="{{ $row['jumlah'] ?? '' }}">
            <input name="obat[{{ $i }}][aturan_pakai]" class="input rx-aturan" placeholder="Aturan pakai, mis. 3x1 sesudah makan" aria-label="Aturan pakai" value="{{ $row['aturan_pakai'] ?? '' }}">
            <button type="button" class="btn btn-icon btn-bad" data-rx-del aria-label="Hapus obat"><x-ico n="x"/></button>
          </div>
          <div class="rx-info muted"></div>
        </div>
      @endforeach
    </div>
    <template id="rx-template"><div class="rx-blok"><div class="rx-row">
      <select name="obat[__i__][id_obat]" class="input rx-obat" aria-label="Obat">{!! $opsi() !!}</select>
      <input name="obat[__i__][jumlah]" type="number" min="1" class="input rx-jumlah" placeholder="Jumlah" aria-label="Jumlah">
      <input name="obat[__i__][aturan_pakai]" class="input rx-aturan" placeholder="Aturan pakai, mis. 3x1 sesudah makan" aria-label="Aturan pakai">
      <button type="button" class="btn btn-icon btn-bad" data-rx-del aria-label="Hapus obat"><x-ico n="x"/></button></div><div class="rx-info muted"></div></div></template>
    <button type="button" class="btn btn-sm" id="rx-add"><x-ico n="plus"/> Tambah obat</button>
    <div class="flex mt"><button class="btn btn-primary btn-lg"><x-ico n="check"/> Simpan & selesaikan</button><a href="{{ route('dokter.antrian') }}" class="btn">Kembali</a></div>
  </form>
  <div>
    <div class="card"><h3>Pasien</h3>
      <dl class="kv"><dt>No. RM</dt><dd>{{ $a->pasien->no_rm }}</dd><dt>Usia</dt><dd>{{ $a->pasien->tgl_lahir?->age ?? '-' }} th</dd><dt>JK</dt><dd>{{ $a->pasien->jenis_kelamin }}</dd></dl>
      @if ($kandungan)<div class="alert alert-warn mt mb0"><x-ico n="alert"/><div>Pasien Poli Kandungan: perhatikan keamanan obat bagi ibu hamil. Hindari OAINS (mis. Ibuprofen) terutama trimester akhir kecuali indikasi khusus.</div></div>@endif
      @if ($a->konsultasi && $a->konsultasi->jenis === 'online')<div class="notif-preview"><b>Dari konsultasi online:</b> {{ $a->konsultasi->tanggapan }}</div>@endif
    </div>
    <div class="card"><h3>Riwayat diagnosis</h3>
      <ul class="list-plain">@forelse ($riwayat as $k)<li><span>{{ $k->tanggal->format('d/m/Y') }}<br><small>{{ $k->diagnosis }}</small></span><small>{{ $k->dokter?->nama }}</small></li>@empty<li class="muted">Belum ada riwayat.</li>@endforelse</ul></div>
    <div class="card"><h3>Formularium</h3><p class="muted mb0" style="font-size:13px">{{ $obat->count() }} obat tersedia. <a href="{{ route('formularium') }}" target="_blank">Lihat formularium lengkap →</a></p></div>
  </div>
</div>
@endsection
@push('scripts')
<script>
(() => {
  const KANDUNGAN = @json($kandungan);
  const URL_SARAN = @json(route('dokter.rekomendasi'));
  const rows = document.getElementById('rx-rows');
  const isi = document.getElementById('saran-isi');
  const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]));

  // Info & placeholder per obat terpilih
  const perbarui = blok => {
    const sel = blok.querySelector('.rx-obat'), op = sel.selectedOptions[0], info = blok.querySelector('.rx-info');
    const j = blok.querySelector('.rx-jumlah'), at = blok.querySelector('.rx-aturan');
    if (!op || !op.value) { info.innerHTML = ''; j.placeholder = 'Jumlah'; at.placeholder = 'Aturan pakai, mis. 3x1 sesudah makan'; return; }
    const d = op.dataset;
    j.placeholder = `${d.jumlah} ${d.satuan}`; at.placeholder = d.aturan || 'Aturan pakai';
    if (!j.value) j.value = d.jumlah;
    if (!at.value) at.value = d.aturan || '';
    const tanda = [];
    if (d.golongan === 'antibiotik') tanda.push('<span class="badge b-warn">Antibiotik: wajib dihabiskan</span>');
    if (KANDUNGAN && d.hamil === 'hindari') tanda.push('<span class="badge b-bad">Hindari pada kehamilan</span>');
    if (+d.stok < +d.jumlah) tanda.push(`<span class="badge b-bad">Stok tinggal ${d.stok}</span>`);
    info.innerHTML = `${esc(d.guna)}${d.catatan ? ' · ' + esc(d.catatan) : ''} ${tanda.join(' ')}`;
  };
  const dipilih = () => [...rows.querySelectorAll('.rx-obat')].map(s => s.value).filter(Boolean);
  const tambahBaris = () => { document.getElementById('rx-add').click(); return rows.lastElementChild; };

  // Ambil saran dari server (debounce)
  let t, nomor = 0;
  const muatSaran = () => {
    clearTimeout(t);
    t = setTimeout(async () => {
      const teks = [...document.querySelectorAll('[data-rekom-teks]')].map(x => x.value).join('. ');
      const qs = new URLSearchParams({ teks }); dipilih().forEach(id => qs.append('dipilih[]', id));
      const n = ++nomor;
      try {
        const d = await fetch(URL_SARAN + '?' + qs, { headers: { Accept: 'application/json' } }).then(r => r.json());
        if (n !== nomor) return;
        if (!d.saran.length) { isi.innerHTML = '<span class="muted">Belum ada saran untuk isian ini.</span>'; return; }
        isi.innerHTML = d.saran.map(s => `
          <div class="saran-kartu${s.peringatan.length ? ' ada-peringatan' : ''}">
            <div class="saran-atas"><div><b>${esc(s.nama)}</b> <span class="muted">${esc(s.kode || '')} · ${esc(s.bentuk || '')}</span></div>
              <button type="button" class="btn btn-sm btn-primary" data-tambah='${esc(JSON.stringify({ id: s.id_obat, jumlah: s.jumlah, aturan: s.aturan }))}'>+ Tambah</button></div>
            <div class="saran-skor" title="Skor kecocokan ${Math.round(s.skor * 100)}%"><i style="width:${Math.max(6, Math.round(s.skor * 100))}%"></i></div>
            <ul>${s.alasan.map(x => `<li>${esc(x)}</li>`).join('')}</ul>
            ${s.peringatan.map(x => `<span class="badge b-warn">${esc(x)}</span>`).join(' ')}
            <div class="muted" style="font-size:12px;margin-top:4px">${esc(s.jumlah)} ${esc(s.satuan)} · ${esc(s.aturan || '')}</div>
          </div>`).join('');
      } catch (e) { isi.innerHTML = '<span class="muted">Saran tidak dapat dimuat.</span>'; }
    }, 350);
  };

  isi.addEventListener('click', e => {
    const b = e.target.closest('[data-tambah]'); if (!b) return;
    const d = JSON.parse(b.dataset.tambah);
    let blok = [...rows.querySelectorAll('.rx-blok')].find(x => !x.querySelector('.rx-obat').value) || tambahBaris();
    const sel = blok.querySelector('.rx-obat'); sel.value = d.id;
    blok.querySelector('.rx-jumlah').value = d.jumlah; blok.querySelector('.rx-aturan').value = d.aturan || '';
    perbarui(blok); muatSaran();
    toast('success', 'Obat ditambahkan', sel.selectedOptions[0].textContent.split(' · stok')[0], 2500);
  });
  rows.addEventListener('change', e => { if (e.target.matches('.rx-obat')) { const b = e.target.closest('.rx-blok'); b.querySelector('.rx-jumlah').value = ''; b.querySelector('.rx-aturan').value = ''; perbarui(b); muatSaran(); } });
  rows.addEventListener('click', e => { if (e.target.closest('[data-rx-del]')) setTimeout(muatSaran, 0); });
  document.querySelectorAll('[data-rekom-teks]').forEach(x => x.addEventListener('input', muatSaran));
  rows.querySelectorAll('.rx-blok').forEach(perbarui);
  muatSaran();
})();
</script>
@endpush
