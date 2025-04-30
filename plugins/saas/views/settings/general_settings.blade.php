@php
    $currencies = getAllSaasCurrencies();
@endphp
@extends('core::base.layouts.master')
@section('title')
    {{ translate('Saas General Settings') }}
@endsection
@section('custom_css')
    <link rel="stylesheet" href="{{ asset('/public/backend/assets/plugins/select2/select2.min.css') }}">
@endsection
@section('main_content')
    <div class="row">
        <div class="col-md-7 mb-30">
            <div class="card">
                <div class="card-body">
                    <div class="post-head d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="content">
                                <h4 class="mb-1">{{ translate('Saas General Settings') }}</h4>
                            </div>
                        </div>
                    </div>

                    <div>
                        <form action="{{ route('plugin.saas.admin.store.general.settings') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="form-row mb-20">
                                <div class="col-md-4">
                                    <label class="font-14 bold black">{{ translate('Select Default Currency') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <select id='selectCurrency' name="default_currency" class="theme-input-style">
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->id }}" class="text-uppercase" @selected($currency->id == $settings_data['default_currency'])>{{ $currency->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('currency'))
                                        <div class="invalid-input">{{ $errors->first('currency') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-row mb-20">
                                <div class="col-md-4">
                                    <label class="font-14 bold black">{{ translate('Email varification') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <label class="switch glow primary medium">
                                        <input type="checkbox" name="email_verification" @checked($settings_data['email_verification']==1)>
                                        <span class="control"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-row mb-20">
                                <div class="col-md-4">
                                    <label
                                        class="font-14 bold black">{{ translate('Auto approve store creation request') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <label class="switch glow primary medium">
                                        <input type="checkbox" name="auto_approve_subscription_request" @checked($settings_data['auto_approve_subscription_request']==1)>
                                        <span class="control"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-row mb-20">
                                <div class="col-md-4">
                                    <label
                                        class="font-14 bold black">{{translate('Days between initial warning and subscription ends')}}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="number" min="1" name="notify_before_expired_days" class="theme-input-style"
                                        type="number" min="1"value="{{$settings_data['notify_before_expired_days']}}">
                                    @if ($errors->has('notify_before_expired_days'))
                                        <div class="invalid-input">{{ $errors->first('notify_before_expired_days') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-row mb-20">
                                <div class="col-md-4">
                                    <label class="font-14 bold black">{{ translate('Interval days between warnings') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="number" min="1" name="notify_before_expired_interval_days" class="theme-input-style" value="{{$settings_data['notify_before_expired_interval_days']}}">
                                    @if ($errors->has('notify_before_expired_interval_days'))
                                        <div class="invalid-input">{{ $errors->first('notify_before_expired_interval_days') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-row mb-20">
                                <div class="col-md-4">
                                    <label class="font-14 bold black">{{ translate('Maximum Free Store A Subscriber Can Create') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="number" min="1" name="maximum_free_store" class="theme-input-style" value="{{$settings_data['maximum_free_store']}}">
                                    @if ($errors->has('maximum_free_store'))
                                        <div class="invalid-input">{{ $errors->first('maximum_free_store') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-row mb-20">
                                <div class="col-md-4">
                                    <label class="font-14 bold black">{{ translate('Database Prefix') }}</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="teext" name="database_prefix" class="theme-input-style" value="{{$settings_data['database_prefix']}}">
                                    @if ($errors->has('database_prefix'))
                                        <div class="invalid-input">{{ $errors->first('database_prefix') }}</div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-md-12 text-right">
                                    <button type="submit" class="btn long">{{ translate('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @include('core::base.media.partial.media_modal')
    </div>
@endsection
@section('custom_scripts')
    <script src="{{ asset('/public/backend/assets/plugins/select2/select2.min.js') }}"></script>
    <script>
        (function($) {
            "use strict";
            initDropzone()
            $(document).ready(function() {
                is_for_browse_file = true
                filtermedia()
                $('#seectRole').select2()
            });
        })(jQuery);
    </script>
@endsection