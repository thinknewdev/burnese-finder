<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bernese Mountain Dog Finder')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bernese: {
                            50: '#fdf8f6',
                            100: '#f2e8e5',
                            200: '#eaddd7',
                            300: '#e0cec7',
                            400: '#d2bab0',
                            500: '#bfa094',
                            600: '#a18072',
                            700: '#977669',
                            800: '#846358',
                            900: '#43302b',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-bernese-900 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <img src="/images/bernese-1.png" alt="Bernese Mountain Dog" class="w-8 h-8 rounded-full object-cover">
                    <span class="font-bold text-xl">Bernese Mountain Dog Finder</span>
                </a>
                <div class="flex space-x-6">
                    <a href="{{ route('dogs.index') }}" class="hover:text-bernese-200 transition">Dogs</a>
                    <a href="{{ route('dogs.top') }}" class="hover:text-bernese-200 transition">Top Rated</a>
                    <a href="{{ route('breeders.index') }}" class="hover:text-bernese-200 transition">Breeders</a>
                    <a href="{{ route('find-best') }}" class="hover:text-bernese-200 transition font-semibold">Find Best Dog</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>

    <footer class="bg-bernese-800 text-white py-6 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p class="text-bernese-200">Bernese Mountain Dog Finder - Find your perfect Bernese Mountain Dog</p>
        </div>
    </footer>
</body>
</html>
