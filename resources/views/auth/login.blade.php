<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
@php
    use App\Models\Utility;

    $setting = Utility::settings();
    $company_logo = $setting['company_logo_dark'] ?? '';
    $company_logos = $setting['company_logo_light'] ?? '';
    $company_favicon = $setting['company_favicon'] ?? '';

    $logo = \App\Models\Utility::get_file('uploads/logo/');

    $color = !empty($setting['color']) ? $setting['color'] : 'theme-3';

    if (isset($setting['color_flag']) && $setting['color_flag'] == 'true') {
        $themeColor = 'custom-color';
    } else {
        $themeColor = $color;
    }

    $company_logo = \App\Models\Utility::GetLogo();
    $SITE_RTL = isset($setting['SITE_RTL']) ? $setting['SITE_RTL'] : 'off';

    $lang = \App::getLocale('lang');
    if ($lang == 'ar' || $lang == 'he') {
        $SITE_RTL = 'on';
    } elseif ($SITE_RTL == 'on') {
        $SITE_RTL = 'on';
    } else {
        $SITE_RTL = 'off';
    }

    $metatitle = isset($setting['meta_title']) ? $setting['meta_title'] : '';
    $metsdesc = isset($setting['meta_desc']) ? $setting['meta_desc'] : '';
    $meta_image = \App\Models\Utility::get_file('uploads/meta/');
    $meta_logo = isset($setting['meta_image']) ? $setting['meta_image'] : '';
    $get_cookie = isset($setting['enable_cookie']) ? $setting['enable_cookie'] : '';

@endphp
<style>
    @import url("https://fonts.googleapis.com/css2?family=Raleway:wght@200;300;400;500&display=swap");

    :root {
        --bodybg: #dcdefe;
        --primary-color: #5138ee;
        --grey: #d6d6d6;
        --placeholder: #969696;
        --white: #fff;
        --text: #333;
        --slider-bg: #eff3ff;
        --login-cta-hover: #1f0098;
    }

    * {
        margin: 0;
        padding: 0;
    }

    body {
        background: var(--bodybg);
        font-family: "Raleway", sans-serif;
        height: 100vh;
        display: flex;
    }

    .login-container {
        display: flex;
        max-width:900px;
        background: var(--white);
        margin: auto;
        width: 100%;
        min-width: 320px;
    }

    .login-container .logo svg {
        height: 40px;
        width: 40px;
        fill: var(--primary-color);
    }

    .login-container .login-form {
        width: 50%;
        box-sizing: border-box;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
    }

    .login-container .login-form .login-form-inner {
        max-width: 380px;
        width: 95%;
        padding-top:30px; 
    }

    .login-container .login-form .google-login-button .google-icon svg {
        height: 20px;
        display: flex;
        margin-right: 10px;
    }

    .login-container .login-form .google-login-button {
        color: var(--text);
        border: 1px solid var(--grey);
        margin: 40px 0 20px;
    }

    .login-container .login-form .sign-in-seperator {
        text-align: center;
        color: var(--placeholder);
        position: relative;
        margin: 30px 0 20px;
    }

    .login-container .login-form .sign-in-seperator span {
        background: var(--white);
        z-index: 1;
        position: relative;
        padding: 0 10px;
        font-size: 14px;
    }

    .login-container .login-form .sign-in-seperator:after {
        content: "";
        position: absolute;
        width: 100%;
        height: 1px;
        background: var(--grey);
        left: 0;
        top: 50%;
        z-index: 0;
    }

    .login-container .login-form .login-form-group {
        position: relative;
        display: flex;
        flex-direction: column;
        margin-bottom: 20px;
    }

    .login-container .login-form .login-form-group label {
        font-size: 14px;
        font-weight: 500;
        color: var(--text);
        margin-bottom: 10px;
    }

    .login-container .login-form .login-form-group input {
        padding: 13px 20px;
        box-sizing: border-box;
        border: 1px solid var(--grey);
        border-radius: 50px;
        font-family: "Raleway", sans-serif;
        font-weight: 600;
        font-size: 14px;
        color: var(--text);
        transition: linear 0.2s;
    }

    .login-container .login-form .login-form-group input:focus {
        outline: none;
        border: 1px solid var(--primary-color);
    }

    .login-container .login-form .login-form-group input::-webkit-input-placeholder {
        color: var(--placeholder);
        font-weight: 300;
        font-size: 14px;
    }

    .login-container .login-form .login-form-group.single-row {
        flex-direction: row;
        justify-content: space-between;
        padding-top: 5px;
    }

    /* custom checkbox */
    .login-container .login-form .custom-check input[type="checkbox"] {
        height: 23px;
        width: 23px;
        margin: 0;
        padding: 0;
        opacity: 1;
        appearance: none;
        border: 2px solid var(--primary-color);
        border-radius: 3px;
        background: var(--white);
        position: relative;
        margin-right: 10px;
        cursor: pointer;
    }

    .login-container .login-form .custom-check input[type="checkbox"]:checked {
        border: 2px solid var(--primary-color);
        background: var(--primary-color);
    }

    .login-container .login-form .custom-check input[type="checkbox"]:checked:before,
    .login-container .login-form .custom-check input[type="checkbox"]:checked:after {
        content: "";
        position: absolute;
        height: 2px;
        background: var(--white);
    }

    .login-container .login-form .custom-check input[type="checkbox"]:checked:before {
        width: 8px;
        top: 11px;
        left: 2px;
        transform: rotate(44deg);
    }

    .login-container .login-form .custom-check input[type="checkbox"]:checked:after {
        width: 14px;
        top: 8px;
        left: 5px;
        transform: rotate(-55deg);
    }

    .login-container .login-form .custom-check input[type="checkbox"]:focus {
        outline: none;
    }

    .login-container .login-form .custom-check {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container .login-form .custom-check label {
        margin: 0;
        color: var(--text);
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }

    .login-container .login-form .link {
        color: var(--primary-color);
        font-weight: 700;
        text-decoration: none;
        font-size: 14px;
    }

    .login-container .login-form .link:hover {
        text-decoration: underline;
    }

    .login-container .login-form .login-cta {
        color: var(--white);
        text-decoration: none;
        border: 1px solid var(--primary-color);
        margin: 25px 0 15px;
        background: var(--primary-color);
        cursor: pointer;
    }

    .login-container .login-form .login-cta:hover {
        background: var(--login-cta-hover);
    }

    .login-container .onboarding {
        flex: 1;
        background: var(--slider-bg);
        display: none;
        width: 50%;
    }

    .login-container .login-form .login-ato {
        color: var(--white);
        text-decoration: none;
        border: 1px solid var(--primary-color);
        margin: 5px 0 5px;
        background: var(--primary-color);
        cursor: pointer;
    }

    .login-container .login-form .login-ato:hover {
        background: var(--login-cta-hover);
    }

    .login-container .login-form .login-form-group label .required-star {
        color: var(--primary-color);
        font-size: 18px;
        line-height: 10px;
    }

    .login-container .rounded-button {
        display: flex;
        width: 100%;
        text-decoration: none;
        border-radius: 50px;
        padding: 13px 20px;
        box-sizing: border-box;
        justify-content: center;
        font-size: 14px;
        font-weight: 500;
        align-items: center;
        transition: linear 0.2s;
    }

    .login-container .rounded-button:hover {
        box-shadow: 0px 0px 4px 0px var(--grey);
    }

    .login-container .body-text {
        font-size: 14px;
        font-weight: 500;
        color: var(--text);
    }

    .login-container .onboarding .swiper-container {
        width: 100%;
        height: 100%;
        margin-left: auto;
        margin-right: auto;
        overflow-x: hidden;
    }

    .login-container .onboarding .swiper-slide {
        text-align: center;
        font-size: 18px;
        font-weight: 400;
        color: var(--text);
        /* Center slide text vertically */
        display: -webkit-box;
        display: -ms-flexbox;
        display: -webkit-flex;
        display: flex;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        -webkit-justify-content: center;
        justify-content: center;
        -webkit-box-align: center;
        -ms-flex-align: center;
        -webkit-align-items: center;
        align-items: center;
    }

    .login-container .onboarding .swiper-pagination-bullet-active {
        background-color: var(--primary-color);
    }

    .login-container .onboarding .swiper-slide {
        flex-direction: column;
        display: flex;
    }
    .login-container .onboarding .swiper-slide .slide-image{
        width:350px;
        height: 350px;
    }
    .login-container .onboarding .swiper-slide .slide-image img {
        width: 100%;
        height: 80%;
    }

    .login-container .onboarding .slide-content {
        width: 80%;
    }

    .login-container .onboarding .slide-content h2 {
        font-size: 22px;
        font-weight: 500;
        margin-bottom: 15px;
    }

    .login-container .onboarding .slide-content p {
        font-size: 16px;
        line-height: 22px;
        font-weight: 300;
    }

    .swiper-pagination-fraction,
    .swiper-pagination-custom,
    .swiper-container-horizontal>.swiper-pagination-bullets {
        bottom: 30px;
    }

    .login-container .login-form .login-form-inner h1 {
        margin-bottom: 20px;
        margin-top: 10px;
    }

    @media screen and (min-width: 768px) {
        .login-container .onboarding {
            display: flex;
        }
    }

    @media screen and (max-width: 767px) {
        .login-container {
            height: 100vh;
        }
    }

    @media screen and (width: 768px) {
        .login-container .onboarding {
            order: 0;
        }

        .login-container .login-form {
            order: 1;
        }

        .login-container {
            height: 100vh;
        }
    }

    @media screen and (max-width: 420px) {
        .login-container .login-form {
            padding: 20px;
        }

        .login-container .login-form-group {
            margin-bottom: 16px;
        }

        .login-container {
            margin: 0;
        }
    }
</style>
<div class="login-container">
    <div class="login-form">
        <div class="login-form-inner">
            <div class="logo" style="display: flex; justify-content:center;">
                @if ($setting['cust_darklayout'] == 'on')
                    <img class="logo"
                        src="{{ $logo . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-light.png') . '?' . time() }}"
                        alt="" loading="lazy"  style="width:65%; height:60px;" />
                @elseif ($setting['cust_darklayout'] == 'off')
                    <img class="logo"
                        src="{{ $logo . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png') . '?' . time() }}"
                        alt="" loading="lazy" style="width:65%; height:60px;" />
                @else 
                @endif
            </div>
            <h1>Login</h1>
            <p class="body-text">Simplify your business operations with Creativesuite</p>

            <div class="sign-in-seperator">
                <span>Sign in with Email</span>
            </div>
            {{ Form::open(['route' => 'login', 'method' => 'post', 'id' => 'loginForm']) }}
            @if (session('status'))
                <div class="mb-4 font-medium text-lg text-green-600 text-danger">
                    {{ session('status') }}
                </div>
            @endif
            <div class="login-form-group">
                <label class="form-label">{{ __('Email') }} <span class="required-star">*</span></label>
                {{ Form::text('email', null, ['class' => 'form-control', 'id' => 'email','placeholder' => __('Enter Your Email')]) }}
                @error('email')
                    <span class="error invalid-email text-danger" role="alert">
                        <small style="color:red;">{{ $message }}</small>
                    </span>
                @enderror
            </div>
            <div class="login-form-group">
                <label for="pwd">Password <span class="required-star">*</span></label>
                {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('Enter Your Password'), 'id' => 'input-password']) }}
                @error('password')
                    <span class="error invalid-password text-danger" role="alert">
                        <small style="color:red;">{{ $message }}</small>
                    </span>
                @enderror
            </div>
            {{-- <div class="login-form-group single-row">
                <div class="custom-check">
                    <input autocomplete="off" type="checkbox" checked id="remember"><label for="remember">Remember
                        me</label>
                </div>
                {{-- <a href="#" class="link forgot-link">Forgot Password ?</a> 
            </div> --}}

            {{-- <a href="#" class="rounded-button login-cta">Login</a> --}}
            {{ Form::submit(__('Login'), ['class' => 'rounded-button login-cta', 'id' => 'saveBtn']) }}

            {{-- <div class="register-div">Not registered yet? <a href="#" class="link create-account" -link>Create an
                    account ?</a></div> --}}
        </div>
        {{ Form::close() }}
        <div class="login-form-inner" style=" display:flex; gap: 15px; padding-top: 10px;">
            <button class="rounded-button login-ato" id="company">Company</button>
            <button class="rounded-button login-ato" id="accountant">Accountant</button>
        </div>
        <div class="login-form-inner" style=" display:flex; gap: 15px; padding-top: 2px;">
            <button class="rounded-button login-ato" id="client">Client</button>
            <button class="rounded-button login-ato" id="user">HR</button>
        </div>
    </div>
    <div class="onboarding">
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <div class="swiper-slide color-1">
                    <div class="slide-image">
                        <img src="{{asset('assets/images/auth/sl1.png')}}" loading="lazy"
                            alt="" />
                    </div>
                    <div class="slide-content">
                        <h2>Office Management with CreativeSuite</h2>
                        <p>Businesses can streamline operations using our all-in-one CreativeSuite ERP system</p>
                    </div>
                </div>
                <div class="swiper-slide color-1">
                    <div class="slide-image">
                        <img src="{{asset('assets/images/auth/sl2.png')}}" loading="lazy"
                            alt="" />
                    </div>
                    <div class="slide-content">
                        <h2>Flexible and Scalable Solutions.</h2>
                        <p>Customizable features scale to meet your evolving demands.</p>
                    </div>
                </div>

                <div class="swiper-slide color-1">
                    <div class="slide-image">
                        <img src="{{asset('assets/images/auth/sl3.png')}}" loading="lazy"
                            alt="" />
                    </div>
                    <div class="slide-content">
                        <h2>Empowering Seamless Operations.</h2>
                        <p>Enhance efficiency and ensure smoother operations across every aspect of your organization.</p>
                    </div>
                </div>
            </div>
            {{-- <div class="swiper-pagination"></div> --}}
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const swiper = new Swiper('.swiper-container', {
            slidesPerView: 1,
            spaceBetween: 10,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            autoplay: {
                delay: 3500, // Correct autoplay setting
                disableOnInteraction: false, // Keeps autoplay running even after user interaction
            },
            loop: true, // Enables continuous sliding
            speed: 600, // Animation speed
            grabCursor: true, // Adds a grab cursor effect
        });
    });

 
    document.getElementById('company').addEventListener('click', function() {
        document.getElementById('email').value = 'demo@creativesuite.co';
        document.getElementById('input-password').value = '123456';
        document.getElementById('saveBtn').click();
    });

    document.getElementById('accountant').addEventListener('click', function() {
        document.getElementById('email').value = 'accountant@creativesuite.co';
        document.getElementById('input-password').value = '123456';
        document.getElementById('saveBtn').click();
    });

    document.getElementById('client').addEventListener('click', function() {
        document.getElementById('email').value = 'client@creativesuite.co';
        document.getElementById('input-password').value = '123456';
        document.getElementById('saveBtn').click();
    });

    document.getElementById('user').addEventListener('click', function() {
        document.getElementById('email').value = 'hr@creativesuite.co';
        document.getElementById('input-password').value = '123456';
        document.getElementById('saveBtn').click();
    });
</script>
