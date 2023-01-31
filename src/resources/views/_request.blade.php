@if($autoRedirect)
    <p>{{ __('You will be redirected to the secure payment page') }}</p>
    <script>
             window.location.href = '{{ $url }}'
    </script>
@endif

<a href="{{ $url }}" type="submit">
    {{ __('Proceed to Payment') }}
</a>
