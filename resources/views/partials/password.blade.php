{{-- @include('partials.password', ['name'=>'password','label'=>'Password','hint'=>'','auto'=>'current-password']) --}}
<div class="field">
  <label for="{{ $name }}">{{ $label }}</label>
  <div class="input-group">
    <x-ico n="lock"/>
    <input id="{{ $name }}" name="{{ $name }}" type="password" class="input @error($name) err @enderror" autocomplete="{{ $auto ?? 'new-password' }}" {!! $attrs ?? 'required' !!}>
    <button type="button" class="pw-toggle" data-pw aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan / sembunyikan"><x-ico n="eye"/></button>
  </div>
  @if (!empty($hint))<div class="hint">{{ $hint }}</div>@endif
  @error($name)<div class="err-msg">{{ $message }}</div>@enderror
</div>

