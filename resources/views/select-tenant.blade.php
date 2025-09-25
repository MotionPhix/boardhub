<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Agency - AdPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Select Your Agency
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Choose your advertising agency to access your billboard marketplace
                </p>
            </div>

            <div class="space-y-4">
                @foreach(\App\Models\Tenant::active()->get() as $tenant)
                <a href="/t/{{ $tenant->uuid }}"
                   class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <div class="text-center">
                        <div class="font-semibold">{{ $tenant->name }}</div>
                        @if($tenant->settings && isset($tenant->settings['address']))
                        <div class="text-indigo-200 text-xs">{{ $tenant->settings['address'] }}</div>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Don't see your agency?
                    <a href="/contact" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Contact us
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

