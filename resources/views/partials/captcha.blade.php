<div class="field">
  <label for="captcha">Verifikasi keamanan</label>
  <div class="captcha">
    <span class="captcha-q" aria-live="polite">{{ $soal }} = ?</span>
    <input id="captcha" name="captcha" inputmode="numeric" class="input @error('captcha') err @enderror" required autocomplete="off" placeholder="Jawaban" style="max-width:130px">
  </div>
  @error('captcha')<div class="err-msg">{{ $message }}</div>@enderror
</div>

