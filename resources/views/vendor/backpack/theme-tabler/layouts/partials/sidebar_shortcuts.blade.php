<div class="w-100 justify-content-center d-none d-lg-flex sidebar-shortcuts">
    @includeWhen(backpack_theme_config('options.showColorModeSwitcher'), backpack_view('layouts.partials.switch_theme'))

    <a href="/admin/telescope" target="_blank" class="btn-link text-secondary border-none decoration-none shadow-none nav-link d-none show-theme-system" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Telescope">
        <i class="la la-terminal fs-2 m-0"></i>
    </a>

    <a href="/api/docs" target="_blank" class="btn-link text-secondary border-none decoration-none shadow-none nav-link d-none show-theme-system" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Swagger">
        <i class="la la-code fs-2 m-0"></i>
    </a>
</div>
<style>
    .sidebar-shortcuts .btn-link {
        margin: 0 7px;
    }
</style>
