{{--
    A session flash shown from WITHIN a Livewire component's own view, not
    just the surrounding layout.

    Livewire's AJAX updates (wire:click, wire:submit, etc. that don't
    redirect) only re-render and morph the component's own template — the
    layout around it is static HTML from the page's original full load, so
    a flash checked only in the layout never appears for any action that
    stays on the same page. Every full-page component that calls
    session()->flash('status', ...) and doesn't redirect afterward must
    include this partial in its own view for the message to actually show.
--}}
@if (session('status'))
    <div class="notice notice-good" role="status">{{ session('status') }}</div>
@endif
