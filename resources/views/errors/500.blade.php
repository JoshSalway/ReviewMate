<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Server Error — ReviewMate</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md text-center">
        <!-- Logo -->
        <div class="mb-8 flex items-center justify-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
            </div>
            <span class="text-lg font-bold text-gray-900">ReviewMate</span>
        </div>

        <!-- Error card -->
        <div class="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
            <div class="mb-4 text-6xl font-bold text-teal-600">500</div>
            <h1 class="mb-2 text-xl font-semibold text-gray-900">Something went wrong</h1>
            <p class="mb-6 text-sm text-gray-500">
                We've hit an unexpected error. Please try again in a moment.
            </p>
            <a
                href="/dashboard"
                class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-700"
            >
                Back to dashboard
            </a>
        </div>
    </div>
</body>
</html>
