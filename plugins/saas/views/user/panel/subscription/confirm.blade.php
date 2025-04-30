@php
    $packages = getAllPaidPackages();
    $payment_methods = getAllActivePaymentMethods();
    $countries = getAllCountries();

    $package_id = $data_to_confirm['package_id'];
    $plan_id = $data_to_confirm['plan_id'];

@endphp
@extends('core::base.layouts.master')
@section('title')
    {{ translate('Confirm Subscription') }}
@endsection
@section('custom_styles')
    <style>
        ul.list-group {
            gap: 10px;
        }

        ul.list-group li {
            border: 1px solid rgba(39, 39, 39, 0.125) !important;
        }
    </style>
@endsection
@section('main_content')
    <div class="mn-vh-100 d-flex">
        <div class="container">
            <div class="row">
                <div class="col-md-7">
                    <div class="card justify-content-center billing-card auth-card">
                        <input type="hidden" name="store_id" id="store_id" value="{{ $data_to_confirm['store_id'] }}">
                        <input type="hidden" name="package_id" id="package_id" value="{{ $package_id }}">
                        <input type="hidden" name="plan_id" id="plan_id" value="{{ $plan_id }}">
                        <input type="hidden" name="primary_amount" id="primary_input_amount"
                            value="{{ $data_to_confirm['primary_amount'] }}">
                        <input type="hidden" name="discount_amount" id="discount_input_amount" value="0">
                        <input type="hidden" name="amount" id="amount"
                            value="{{ $data_to_confirm['primary_amount'] }}">

                        @if ($data_to_confirm['store_id'] == 'null')
                            <h4 class="mb-2">{{ translate('Store Info') }}<span class="text-danger"> *
                                </span></h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-20">
                                        <label for="store_name"
                                            class="mb-2 font-14 bold black">{{ translate('Store Name') }}</label>
                                        <input type="text" id="store_name" name="store_name" class="theme-input-style"
                                            placeholder="{{ translate('Store Name') }}" value="{{ old('store_name') }}">
                                        <div class="text-danger mt-2" id="store_name_error"></div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <h4 class="mb-2">{{ translate('Billing Address') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-20">
                                    <label for="name" class="mb-2 font-14 bold black">{{ translate('Full Name') }}
                                        <span class="text-danger"> *
                                        </span></label>
                                    <input type="text" id="name" name="name" class="theme-input-style"
                                        placeholder="{{ translate('Name') }}"
                                        value="{{ $billing_details == null ? old('name') : $billing_details->name }}">
                                    <div class="text-danger mt-2" id="name_error"></div>
                                </div>
                                <div class="form-group mb-20">
                                    <label for="email" class="mb-2 font-14 bold black">{{ translate('Email') }}
                                        <span class="text-danger"> *
                                        </span></label>
                                    <input type="text" id="email" name="email" class="theme-input-style"
                                        placeholder="{{ translate('Email') }}"
                                        value="{{ $billing_details == null ? old('email') : $billing_details->email }}">
                                    <div class="text-danger mt-2" id="email_error"></div>
                                </div>
                                <div class="form-group mb-20">
                                    <label for="phone" class="mb-2 font-14 bold black">{{ translate('Phone Number') }}
                                        <span class="text-danger"> *
                                        </span></label>
                                    <input type="text" id="phone" name="phone" class="theme-input-style"
                                        placeholder="{{ translate('Phone') }}"
                                        value="{{ $billing_details == null ? old('phone') : $billing_details->phone }}">
                                    <div class="text-danger mt-2" id="phone_error"></div>
                                </div>
                                <div class="form-group mb-20">
                                    <label for="post_code"
                                        class="mb-2 font-14 bold black">{{ translate('Postal Code') }}</label>
                                    <input type="text" id="post_code" name="post_code" class="theme-input-style"
                                        placeholder="{{ translate('Post Code') }}"
                                        value="{{ $billing_details == null ? old('post_code') : $billing_details->post_code }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-20">
                                    <label for="country" class="mb-2 font-14 bold black">{{ translate('Country') }}<span
                                            class="text-danger"> *
                                        </span></label>
                                    <select class="theme-input-style" id="country" name="country"
                                        onchange="getAllStatesOfCountry()">
                                        <option>{{ translate('Select Country') }}</option>
                                        @for ($i = 0; $i < sizeof($countries); $i++)
                                            <option value="{{ $countries[$i]->id }}"
                                                {{ $billing_details != null && $billing_details->country == $countries[$i]->id ? 'selected' : '' }}>
                                                {{ $countries[$i]->name }}
                                            </option>
                                        @endfor
                                    </select>
                                    <div class="text-danger mt-2" id="country_error"></div>
                                </div>

                                <div class="form-group mb-20">
                                    <label for="state"
                                        class="mb-2 font-14 bold black">{{ translate('State') }}</label>
                                    <select class="theme-input-style" id="state" name="state"
                                        onchange="getAllCitiesOfState()">
                                    </select>
                                    <div class="text-danger mt-2" id="state_error"></div>
                                </div>

                                <div class="form-group mb-20">
                                    <label for="city" class="mb-2 font-14 bold black">{{ translate('City') }}</label>
                                    <select class="theme-input-style" id="city" name="city">
                                    </select>
                                    <div class="text-danger mt-2" id="state_error"></div>
                                </div>
                                <div class="form-group mb-20">
                                    <label for="address" class="mb-2 font-14 bold black">{{ translate('Address') }}
                                        <span class="text-danger"> *
                                        </span></label>
                                    <input type="text" id="address" name="address" class="theme-input-style"
                                        placeholder="{{ translate('Address') }}"
                                        value="{{ $billing_details == null ? old('address') : $billing_details->address }}">
                                    <div class="text-danger mt-2" id="address_error"></div>
                                </div>
                            </div>
                        </div>

                        <h4 class="mb-2">{{ translate('Payment Method') }}<span class="text-danger"> *
                            </span></h4>
                        <div class="row">
                            <div class="col-12">
                                <div class="text-danger mt-2" id="payment_method_error"></div>
                                <ul class="list-group d-flex flex-row flex-wrap">
                                    @foreach ($payment_gateways as $gateway)
                                        <li class="list-group-item">
                                            <input type="radio" name="payment_method" value="{{ $gateway->id }}">
                                            @if ($gateway->logo != null)
                                                <img src="{{ $gateway->logo }}" alt="{{ $gateway->name }}">
                                            @else
                                                {{ $gateway->name }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <!-- End Form Group -->
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card justify-content-center auth-card billing-card">
                        <h4 class="mb-2">{{ translate('Billing Summary') }}</h4>
                        <table class="shop_table style-two">
                            <tbody>
                                <tr class="cart-subtotal">
                                    <td>
                                        <h6>{{ translate('Package Name') }}</h6>
                                    </td>
                                    <td class="text-right">
                                        {{ $data_to_confirm['package_name'] }}
                                    </td>
                                </tr>
                                <tr class="cart-subtotal">
                                    <td>
                                        <h6>{{ translate('Package Plan') }}</h6>
                                    </td>
                                    <td class="text-right">
                                        {{ $data_to_confirm['package_plan'] }}
                                    </td>
                                </tr>
                                <tr class="cart-subtotal">
                                    <td>
                                        <h6>{{ translate('Primary Amount') }}</h6>
                                    </td>
                                    <td class="text-right">
                                        <span class="Price-amount amount">
                                            {{ currencyExchange($data_to_confirm['primary_amount']) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr class="cart-subtotal">
                                    <td>
                                        <h6>{{ translate('Discount Amount') }}</h6>
                                    </td>
                                    <td class="text-right">
                                        <span class="Price-amount amount" id="discount_amount">
                                            {{ currencyExchange(0) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr class="cart-subtotal">
                                    <td>
                                        <h6>{{ translate('Total Amount') }}</h6>
                                    </td>
                                    <td class="text-right">
                                        <span class="Price-amount amount" id="total_amount">
                                            {{ currencyExchange($data_to_confirm['primary_amount']) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group mb-20 d-flex">
                            <input type="text" id="coupon" name='coupon' value=""
                                class="theme-input-style">
                            <button class="btn btn-square sm"
                                onclick="applyCoupon('{{ $package_id }}','{{ $plan_id }}')">{{ translate('Apply') }}</button>
                        </div>
                        <span class="text-danger" id="coupon_error"></span>
                        <div class="proceed-to-checkout d-flex align-items-center justify-content-end mr-20 mt-4">
                            <button class="btn btn-square btn-block" onclick="makePayment()" id="create-store">
                                <img src="{{ asset('/public/backend/assets/img/loader-w4.svg') }}" alt=""
                                    class="d-none" id="loader">
                                {{ translate('Make Payment') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('custom_scripts')
    <!--Select2-->
    <script src="{{ asset('/public/backend/assets/plugins/select2/select2.min.js') }}"></script>
    <!--End Select2-->

    <script>
        $(document).ready(function() {
            'use strict'
            getAllStatesOfCountry()
        });

        /**
         *  Will request for all states of selected country
         */
        function getAllStatesOfCountry() {
            'use strict';
            let selected_country = $('#country').val();
            $.ajax({
                url: '{{ route('plugin.saas.get.states.of.country') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    country_id: selected_country
                },
                success: function(response) {
                    if (response.success) {
                        let states = response.states

                        let html = ``

                        for (let i = 0; i < states.length; i++) {
                            let state = '{{ $billing_details != null ? $billing_details->state : -1 }}'
                            if (state == states[i]['id']) {
                                html = html + `<option value='` + states[i]['id'] + `' selected>` + states[i][
                                        'name'
                                    ] +
                                    `</option>`
                            } else {
                                html = html + `<option value='` + states[i]['id'] + `'>` + states[i]['name'] +
                                    `</option>`
                            }

                        }
                        $('#state').html(html)
                        getAllCitiesOfState()
                    }
                }
            });
        }

        /**
         *  Will request for all cities of selected state
         */
        function getAllCitiesOfState() {
            'use strict';
            let selected_state = $('#state').val();
            $.ajax({
                url: '{{ route('plugin.saas.get.cities.of.state') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    state_id: selected_state
                },
                success: function(response) {
                    if (response.success) {
                        let cities = response.cities

                        let html = ``

                        for (let i = 0; i < cities.length; i++) {
                            let city = '{{ $billing_details != null ? $billing_details->city : -1 }}'

                            if (city == cities[i]['id']) {
                                html = html + `<option value='` + cities[i]['id'] + `' selected>` + cities[i][
                                        'name'
                                    ] +
                                    `</option>`
                            } else {
                                html = html + `<option value='` + cities[i]['id'] + `'>` + cities[i]['name'] +
                                    `</option>`
                            }


                        }
                        $('#city').html(html)
                    }
                }
            });
        }

        /**
         * Calculate price after coupon discount
         */
        function applyCoupon(package_id, plan_id) {
            'use strict';

            let coupon = $('#coupon').val()

            $.ajax({
                url: '{{ route('plugin.saas.apply.coupon') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    coupon: coupon,
                    package: package_id,
                    plan_id: plan_id
                },
                success: function(response) {
                    if (response.success) {
                        let summery = response.summery
                        $('#discount_amount').html(summery.discount_amount)
                        $('#discount_input_amount').val(summery.discount_amount_value)
                        $('#total_amount').html(summery.total)
                        $('#amount').val(summery.total_value)
                        $('#coupon').prop('readonly', true);
                        $('#coupon_error').html('')
                    } else {
                        $('#coupon').val('')
                        $('#coupon_error').html("{{ translate('Please provide a valid coupon !') }}");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#coupon').val('')
                    $('#coupon_error').html("{{ translate('Please provide a valid coupon !') }}");
                    toastr.error('{{ translate('Please provide a valid coupon !') }}');
                }
            });
        }

        /**
         * Will send payment request
         */
        function makePayment() {
            'use strict'

            $('#create-store').prop('disabled', true)
            $('#loader').removeClass('d-none')

            let package_id = $('#package_id').val()
            let plan_id = $('#plan_id').val()

            let amount = $('#amount').val()
            let discount_amount = $('#discount_input_amount').val()
            let primary_amount = $('#primary_input_amount').val()

            let name = $('#name').val()
            let email = $('#email').val()
            let phone = $('#phone').val()
            let post_code = $('#post_code').val()
            let country = $('#country').val()
            let state = $('#state').val()
            let city = $('#city').val()
            let address = $('#address').val()
            let payment_method = $('input[name=payment_method]:checked').val();
            let coupon_code = $('#coupon').val();
            let store_id = $('#store_id').val();
            let store_name = $('#store_name').val();

            $.ajax({
                url: '{{ route('plugin.saas.make.payment') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    package_id: package_id,
                    plan_id: plan_id,
                    amount: amount,

                    discount_amount: discount_amount,
                    primary_amount: primary_amount,

                    name: name,
                    email: email,
                    phone: phone,
                    post_code: post_code,
                    country: country,
                    state: state,
                    city: city,
                    address: address,
                    payment_method: payment_method,
                    coupon_code: coupon_code,
                    store_id: store_id,
                    store_name: store_name
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.response_url;
                    } else {
                        toastr.error("{{ translate('Please give valid information') }}");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#create-store').prop('disabled', false)
                    $('#loader').addClass('d-none')

                    var response = jqXHR.responseJSON;
                    if (response && response.errors) {
                        $('#name_error').html(response.errors.hasOwnProperty('name') ? response.errors.name[0] :
                            '');
                        $('#email_error').html(response.errors.hasOwnProperty('email') ? response.errors.email[
                            0] : '');
                        $('#store_name_error').html(response.errors.hasOwnProperty('store_name') ? response
                            .errors
                            .store_name[
                                0] : '');
                        $('#phone_error').html(response.errors.hasOwnProperty('phone') ? response.errors.phone[
                            0] : '');
                        $('#country_error').html(response.errors.hasOwnProperty('country') ? response.errors
                            .country[0] : '');
                        $('#state_error').html(response.errors.hasOwnProperty('state') ? response.errors.state[
                            0] : '');
                        $('#city_error').html(response.errors.hasOwnProperty('city') ? response.errors.city[0] :
                            '');
                        $('#address_error').html(response.errors.hasOwnProperty('address') ? response.errors
                            .address[0] : '');
                        $('#payment_method_error').html(response.errors.hasOwnProperty('payment_method') ?
                            response.errors.payment_method[0] : '');
                        toastr.error("{{ translate('Please give valid information') }}");
                    } else {
                        toastr.error("{{ translate('Please give valid information') }}");
                    }
                }
            });
        }
    </script>
@endsection
