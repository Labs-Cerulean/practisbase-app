{{-- Compact ? hover help. Usage: @include('partials.help-tip', ['text' => '...']) --}}
@php($tipId = 'help-tip-'.uniqid())
<span class="help-tip" tabindex="0" role="button" aria-describedby="{{ $tipId }}" aria-label="Help">
    <span class="help-tip-mark" aria-hidden="true">?</span>
    <span class="help-tip-bubble" id="{{ $tipId }}" role="tooltip">{{ $text }}</span>
</span>
