<nav class="page-breadcrumb d-flex align-items-center justify-content-between">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">@lang('index.dashboard')</a></li>
        <li class="breadcrumb-item"><a href="{{route('admin.general-settings.index')}}">@lang('index.general_setting_section')</a></li>
        <li class="breadcrumb-item active" aria-current="page">@yield('action')</li>
    </ol>

    @yield('button')

</nav>
