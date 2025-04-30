@extends('core::base.auth.auth_layout')
@section('title')
    {{ translate('Try Forgot Password') }}
@endsection
@section('main_content')
    <div class="mn-vh-100 d-flex align-items-center">
        <div class="container">
            <div class="card justify-content-center auth-card">
                <div class="row justify-content-center">
                    <div class="col-xl-7 col-lg-9">
                        <h4 class="mb-5 font-20">{{ translate('Reset Password') }}</h4>
                        <form action="{{ route('subscriber.email.reset.password.link') }}" method="post">
                            @csrf
                            <div class="form-group mb-20">
                                <label for="email" class="mb-2 font-14 bold black">{{ translate('Email') }}</label>
                                <input type="email" id="email" name="email" class="theme-input-style"
                                    placeholder="{{ translate('Email Address') }}">
                                @if ($errors->has('email'))
                                    <div class="invalid-input">{{ $errors->first('email') }}</div>
                                @endif
                            </div>

                            <div class="d-flex align-items-center">
                                <button type="submit"
                                    class="btn long mr-20">{{ translate('Send Password Reset
                                                                        Link') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
