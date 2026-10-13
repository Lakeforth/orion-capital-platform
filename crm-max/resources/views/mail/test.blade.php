<x-mail.basic-layout>
    <x-slot name="header">
        <div class="header">


            <div>

                <img src="{{ $message->embed(public_path('images/icons/wallet.png')) }}" alt="Simple Pleb" class="logo">

            </div>


            <h1 class="welcome-text">Hey, Benjamin!</h1>

            <p class="subtitle">Your wallet has been funded successfully! 🤑</p>
            <a href="{{ $actionUrl }}" class="button">GET STARTED</a>
        </div>
    </x-slot>

    <div class="section">
        <h2 class="section-title">What's next</h2>

        @foreach($features as $feature)
            <div class="feature">
                <img src="{{ $message->embed(public_path($feature['icon'])) }}" alt="" class="feature-icon">
                <div class="feature-content">
                    <h3>{{ $feature['title'] }}</h3>
                    <p>{{ $feature['description'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <x-slot name="footer">
        <div class="footer">
            <div class="social-links">
                <a href="{{ $socialLinks['facebook'] }}"><img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Facebook" class="social-icon"></a>
                <a href="{{ $socialLinks['instagram'] }}"><img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Instagram" class="social-icon"></a>
                <a href="{{ $socialLinks['twitter'] }}"><img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Twitter" class="social-icon"></a>
                <a href="{{ $socialLinks['pinterest'] }}"><img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Pinterest" class="social-icon"></a>
            </div>
            <div class="contact-info">
                <p>(738) 479-6719</p>
                <p>info@website.com - https://www.simple-pleb.com</p>
            </div>
            <div class="unsubscribe">
                <a href="{{ $unsubscribeUrl }}">UNSUBSCRIBE</a> | <a href="{{ $sendToFriendUrl }}">SEND TO A FRIEND</a>
            </div>
        </div>
    </x-slot>

</x-mail.basic-layout>
