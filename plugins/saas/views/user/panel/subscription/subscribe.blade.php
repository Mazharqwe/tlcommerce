@php
    $packages = getAllPaidPackages();
    $payment_methods = getAllActivePaymentMethods();
@endphp
@extends('core::base.layouts.master')
@section('title')
    {{ translate('Subscribe Now') }}
@endsection
@section('main_content')
    <section id="pricing">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-center mb-5 row">
                    @foreach ($package_plans as $plan)
                        <button class="btn btn-info sm mr-1 package-plan mt-2" id="{{ strtolower($plan->id) }}"
                            onclick="getPackagesAccordingToPlan('{{ $plan->id }}')">
                            {{ translate($plan->name) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="row" id="packages"></div>
        <div class="modal fade" id="storeNameModal" tabindex="-1" role="dialog" aria-labelledby="storeNameModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{{ translate('Store Name') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('plugin.saas.confirm.subscription') }}" method="post" id="confirm-subscription">
                        @csrf
                        <div class="modal-body">

                            <input type="hidden" id="is_for_update" name="is_for_update" value="">
                            <input type="hidden" id="store_id" name="store_id" value="">
                            <input type="hidden" id="package_id" name="package_id" value="">
                            <input type="hidden" id="plan_id" name="plan_id" value="">
                            <input type="hidden" id="membership_type" name="membership_type" value="member">

                            <div class="form-group mb-20">
                                <input type="text" id="store_name" name="store_name" class="theme-input-style"
                                    placeholder="{{ translate('Store Name') }}" value="">
                                <div class="text-danger mt-2" id="store_name_error"></div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary sm"
                                data-dismiss="modal">{{ translate('Close') }}</button>
                            <button type="submit" class="btn btn-primary sm" id="create-store">
                                <img src="{{ asset('/public/backend/assets/img/loader-w4.svg') }}" alt=""
                                    class="d-none" id="loader">
                                {{ translate('Save changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('custom_scripts')
    <script>
        $(document).ready(function() {
            'use strict'
            $('#confirm-subscription').submit(function(event) {
                event.preventDefault()
                $('#create-store').prop('disabled', true)
                $('#loader').removeClass('d-none')
                this.submit();
            });
            getPackagesAccordingToPlan('{{ $first_plan }}')
        });

        /**
         * Get all packages according to selected plan
         */
        function getPackagesAccordingToPlan(plan_id) {
            'use strict'
            $('#pricing').addClass('disabled-section')
            $.post("{{ route('plugin.saas.get.packages.according.to.plan') }}", {
                    _token: '{{ csrf_token() }}',
                    plan_id: plan_id,
                    is_for_payment: 1,
                    store_id: '{{ isset($store_id) ? $store_id : 'null' }}'
                })
                .done(function(data) {
                    $('#packages').html(data)
                    $('.package-plan').removeClass('btn-success')
                    $('.package-plan').addClass('btn-info')
                    $('#' + plan_id).addClass('btn-success')
                    $('#pricing').removeClass('disabled-section')
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    $('#pricing').removeClass('disabled-section');
                });
        }

        /**
         * Set data in modal incase of free package
         */
        function setDataInModalForFreePackage(is_for_update, store_id, package_id, plan_id, membership_type) {
            'use strict';

            $('#is_for_update').val(is_for_update)
            $('#store_id').val(store_id)
            $('#package_id').val(package_id)
            $('#plan_id').val(plan_id)
            $('#membership_type').val(membership_type)
        }
    </script>
@endsection
