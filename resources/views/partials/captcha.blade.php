@php $vk = \App\Support\Verifikasi::siapkan(); @endphp
<div class="field">
  <label @if($vk['mode'] === 'hitung') for="captcha" @endif>Verifikasi keamanan</label>
  @if ($vk['mode'] === 'turnstile')
    <div class="cf-turnstile" data-sitekey="{{ $vk['site_key'] }}" data-language="id" data-theme="auto"></div>
    @once<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endonce
  @else
    <div class="captcha">
      <span class="captcha-q" aria-live="polite">{{ $vk['soal'] }} = ?</span>
      <input id="captcha" name="captcha" inputmode="numeric" class="input @error('captcha') err @enderror" required autocomplete="off" placeholder="Jawaban" style="max-width:130px">
    </div>
  @endif
  @error('captcha')<div class="err-msg" role="alert">{{ $message }}</div>@enderror
</div>
