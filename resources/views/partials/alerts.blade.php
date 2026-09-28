@if (session('success'))
    <div class="cc-alert cc-alert-success">
        <i class="bi bi-check-circle-fill cc-alert-icon"></i>
        <div class="flex-1">{{ session('success') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="cc-alert cc-alert-danger">
        <i class="bi bi-exclamation-triangle-fill cc-alert-icon"></i>
        <div class="flex-1">{{ session('error') }}</div>
    </div>
@endif

@if (session('info'))
    <div class="cc-alert cc-alert-info">
        <i class="bi bi-info-circle-fill cc-alert-icon"></i>
        <div class="flex-1">{{ session('info') }}</div>
    </div>
@endif

@if (session('status'))
    <div class="cc-alert cc-alert-success">
        <i class="bi bi-info-circle-fill cc-alert-icon"></i>
        <div class="flex-1">{{ session('status') }}</div>
    </div>
@endif

@if ($errors->any() && !request()->routeIs('login') && !request()->routeIs('register'))
    <div class="cc-alert cc-alert-danger">
        <i class="bi bi-x-circle-fill cc-alert-icon"></i>
        <div class="flex-1">
            <div class="font-bold mb-1">Please correct the following errors:</div>
            <ul class="mb-0 ps-3" style="font-size:var(--fs-xs);">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
