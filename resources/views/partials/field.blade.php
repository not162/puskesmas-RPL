{{-- @include('partials.field', ['name'=>'', 'label'=>'', 'type'=>'text', 'value'=>'', 'hint'=>'', 'attrs'=>'']) --}}
<div class="field">
  <label for="{{ $name }}">{{ $label }}</label>
  @if (($type ?? 'text') === 'textarea')
    <textarea id="{{ $name }}" name="{{ $name }}" class="input @error($name) err @enderror" {!! $attrs ?? '' !!}>{{ old($name, $value ?? '') }}</textarea>
  @else
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ ($type ?? '') === 'password' ? '' : old($name, $value ?? '') }}" class="input @error($name) err @enderror" {!! $attrs ?? '' !!}>
  @endif
  @if (!empty($hint))<div class="hint">{{ $hint }}</div>@endif
  @error($name)<div class="err-msg">{{ $message }}</div>@enderror
</div>

