@php
    $label = (string) ($label ?? 'Export');
    $placeholder = (string) ($placeholder ?? 'Select format');
    $options = is_array($options ?? null) ? $options : [];
    $selectClass = (string) ($selectClass ?? 'input');
@endphp

<label style="display:inline-flex;align-items:center;gap:8px">
    <span class="muted">{{ $label }}</span>
    <select class="{{ $selectClass }}" onchange="if(this.value){window.location.href=this.value;this.selectedIndex=0;}">
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $optionLabel => $optionUrl)
            <option value="{{ $optionUrl }}">{{ $optionLabel }}</option>
        @endforeach
    </select>
</label>
