<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Alumni Tracker') - Sistem Pelacakan Alumni</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' },
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
            <div class="p-6">
                <h1 class="text-xl font-bold text-primary-400">🎓 Alumni Tracker</h1>
                <p class="text-xs text-gray-400 mt-1">Sistem Pelacakan Alumni</p>
            </div>
            <nav class="mt-2">
                <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 text-sm {{ request()->routeIs('dashboard') ? 'bg-primary-700 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <span class="mr-3">📊</span> Dashboard
                </a>
                <a href="{{ route('alumni.index') }}" class="flex items-center px-6 py-3 text-sm {{ request()->routeIs('alumni.*') ? 'bg-primary-700 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <span class="mr-3">👥</span> Data Alumni
                </a>
                <a href="{{ route('verification.index') }}" class="flex items-center px-6 py-3 text-sm {{ request()->routeIs('verification.*') ? 'bg-primary-700 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <span class="mr-3">✅</span> Verifikasi
                </a>
                <a href="{{ route('reports.index') }}" class="flex items-center px-6 py-3 text-sm {{ request()->routeIs('reports.*') ? 'bg-primary-700 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <span class="mr-3">📋</span> Laporan
                </a>
                <a href="{{ route('config.index') }}" class="flex items-center px-6 py-3 text-sm {{ request()->routeIs('config.*') ? 'bg-primary-700 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                    <span class="mr-3">⚙️</span> Konfigurasi
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Logout</button>
                    </form>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        </script>
    @endif
    
    @if(session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '{{ session('error') }}',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        </script>
    @endif

    @if(session('info'))
        <script>
            Swal.fire({
                icon: 'info',
                title: 'Info',
                text: '{{ session('info') }}',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        </script>
    @endif
</body>
</html>
