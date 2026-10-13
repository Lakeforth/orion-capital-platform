<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You — {{ $campaign->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fadeUp 0.5s ease forwards; }
        @keyframes checkPop { 0% { transform: scale(0); opacity: 0; } 70% { transform: scale(1.15); } 100% { transform: scale(1); opacity: 1; } }
        .check-pop { animation: checkPop 0.5s ease 0.3s both; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background-image: linear-gradient(rgba(0,0,0,0.72), rgba(0,0,0,0.72)), url('/images/surveybck.png'); background-size: cover; background-position: center; background-attachment: fixed;">

    <div class="w-full max-w-md fade-up">

        <div class="bg-white rounded-2xl shadow-2xl p-10 text-center">

            <div class="flex justify-center mb-6">
                <div class="check-pop w-20 h-20 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-3">You're all set!</h1>
            <p class="text-gray-500 text-sm leading-relaxed">
                Thank you for your interest. One of our specialists will be in touch with you shortly to walk you through your options.
            </p>

            <div class="mt-8 pt-6 border-t border-gray-100">
                <p class="text-xs text-gray-400">{{ $campaign->name }}</p>
            </div>

        </div>

    </div>

</body>
</html>
