<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="_token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ARC')</title>
    @arc(['fontawesome', 'datatables[buttons,select]', 'sweetalert'])
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

    <!-- Navbar -->
    <header class="bg-white dark:bg-gray-800 text-gray-500 dark:text-white shadow dark:shadow-white">
        <div class="container mx-auto flex items-center justify-between py-4 px-6">
            <a href="{{ url('/') }}" class="text-xl font-bold">ARC</a>
            <nav class="space-x-4">
                @foreach(arcMenus() as $menu)
                    @include('partials.menu-item', ['menu' => $menu])
                @endforeach
            </nav>            
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto py-6 px-4">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 shadow-inner mt-auto">
        <div class="container mx-auto py-4 px-6 text-center text-sm text-gray-500 dark:text-gray-400">
            &copy; {{ date('Y') }} ARC. All rights reserved.
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
