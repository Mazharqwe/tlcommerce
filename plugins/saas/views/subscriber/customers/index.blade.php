@php
    $packages = getAllActivePackages();
@endphp
@extends('core::base.layouts.master')
@section('title')
    {{ translate('Customer List') }}
@endsection
@section('custom_css')
@endsection
@section('main_content')
    <div class="row">
        <!-- Store List-->
        <div class="col-md-12">
            <div class="card mb-30">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="font-20">{{ translate('All Customers') }}</h4>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="hoverable text-nowrap border-top2 " id="store_list">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Name') }}</th>
                                <th>{{ translate('Email') }}</th>
                                <th>{{ translate('Total Store') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $key = 1;
                            @endphp
                            @foreach ($customers as $customer)
                                <tr>
                                    <td>{{ $key }}.</td>
                                    <td> {{ $customer->customer_name }} </td>
                                    <td> {{ $customer->customer_email }} </td>
                                    <td> {{ $customer->total_store }} </td>
                                    <td class="text-center">
                                        <div class="dropdown-button">
                                            <a href="#" class="d-flex align-items-center" data-toggle="dropdown">
                                                <div class="menu-icon style--two mr-0">
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </div>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a href="#" data-toggle="modal" data-target="#createStore"
                                                    data-customer="{{ $customer->id }}" class="createCustomer">{{ translate('Create Store') }}</a>
                                                <a
                                                    href="{{ route('plugin.saas.edit.customer.profile', $customer->id) }}">{{ translate('Edit Profile') }}</a>
                                                <a
                                                    href="{{ route('plugin.saas.customer.details', $customer->id) }}">{{ translate('Details') }}</a>
                                                <a href="#"
                                                    onclick="deleteConfirmation('{{ $customer->id }}')">{{ translate('Delete Customer') }}</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @php
                                    $key++;
                                @endphp
                            @endforeach
                        </tbody>
                    </table>
                    <div class="pgination px-3">
                        {!! $customers->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5-custom') !!}
                    </div>
                </div>
            </div>
        </div>
        <!-- Store List-->

        <!--Store Delete Modal-->
        <div id="delete-modal" class="delete-modal modal fade show" aria-modal="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title h6">{{ translate('Delete Confirmation') }}</h4>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mt-1">
                            {{ translate('Are you sure to delete this customer ? Once you delete, all data related to this will be deleted') }}.
                        </p>
                        <form method="POST" action="{{ route('plugin.saas.customer.delete') }}" id="customer-delete-form">
                            @csrf
                            <input type="hidden" id="customer_id_to_delete" name="customer_id">
                            <button type="button" class="btn long mt-2  btn-danger"
                                data-dismiss="modal">{{ translate('cancel') }}</button>
                            <button type="submit" class="btn long mt-2" id="customer-delete-btn">
                                <img src="{{ asset('/public/backend/assets/img/loader-w4.svg') }}" alt=""
                                    class="d-none" id="loader">
                                {{ translate('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--Store Delete Modal End-->
    </div>
    <!-- Create Tenant -->

    <!-- Create Store-->
    <div class="modal fade" id="createStore" tabindex="-1" role="dialog" aria-labelledby="createStoreLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createStoreLabel">{{ translate('Assign Store') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('plugin.saas.assign.store') }}" method="post" id="assign-store-form">
                    @csrf
                    <input type="hidden" name="customer_id" id="customer_id">
                    <div class="modal-body">
                        <div class="form-group mb-20">
                            <label for="store_name" class="mb-2 font-14 bold black">{{ translate('Store Name') }} <span
                                    class="text-danger"> *
                                </span></label>
                            <input type="text" id="store_name" name="store_name" class="theme-input-style"
                                placeholder="{{ translate('Store Name') }}">
                            @if ($errors->has('store_name'))
                                <div class="text-danger mt-2">{{ $errors->first('store_name') }}
                                </div>
                            @endif
                        </div>
                        <div class="form-group mb-20">
                            <label for="package" class="mb-2 font-14 bold black">{{ translate('Package') }}<span
                                    class="text-danger"> *
                                </span></label>
                            <select class="theme-input-style" id="package" onchange="getAllPlansOfPackage()">
                                <option>{{ translate('Select Package') }}</option>
                                @for ($i = 0; $i < sizeof($packages); $i++)
                                    <option value="{{ $packages[$i]->id }}"
                                        {{ collect(old('packages'))->contains($packages[$i]->id) ? 'selected' : '' }}>
                                        {{ $packages[$i]->name }}
                                    </option>
                                @endfor
                            </select>
                            <input type="hidden" id="selected_package" value="" name="package_id">
                            @if ($errors->has('package_id'))
                                <div class="text-danger mt-2">{{ $errors->first('package_id') }}
                                </div>
                            @endif
                        </div>
                        <div class="form-group mb-20">
                            <label for="plan" class="mb-2 font-14 bold black">{{ translate('Plans') }}<span
                                    class="text-danger"> *
                                </span></label>
                            <select class="theme-input-style" name="plan_id" id="plans">
                                <option value=""> {{ translate('Select Plan') }} </option>
                            </select>
                            @if ($errors->has('plan_id'))
                                <div class="text-danger mt-2">{{ $errors->first('plan_id') }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn-primary" id="assign-store">
                            <img src="{{ asset('/public/backend/assets/img/loader-w4.svg') }}" alt=""
                                class="d-none" id="assign-loader">
                            {{ translate('Save changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Create Store-->
    </div>
@endsection
@section('custom_scripts')
    <script>
        $(document).ready(function() {
            "use strict";
            $('#customer-delete-form').submit(function(event) {
                event.preventDefault()
                $('#customer-delete-btn').prop('disabled', true)
                $('#loader').removeClass('d-none')
                this.submit();
            });

            $('#assign-store-form').submit(function(event) {
                event.preventDefault()
                $('#assign-store').prop('disabled', true)
                $('#assign-loader').removeClass('d-none')
                this.submit();
            });

            $('.createCustomer').on('click',function(){
                let customer_id = $(this).data('customer')
                $('#customer_id').val(customer_id)
            })
        });

        /**
         * show store delete confirmation modal
         */
        function deleteConfirmation(id) {
            "use strict";
            $("#customer_id_to_delete").val(id);
            $('#delete-modal').modal('show');
        }

        /**
         *  Will request for all plans of selected package 
         */
        function getAllPlansOfPackage() {
            'use strict';
            $("#register").prop("disabled", true);
            let selected_package = $('#package').val();
            $('#selected_package').val(selected_package)
            $.ajax({
                url: '{{ route('plugin.saas.get.plans.according.to.package') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    package_id: selected_package
                },
                success: function(response) {
                    $("#register").prop("disabled", false);
                    if (response.success) {
                        let is_redeem_coupon = $('#is_redeem_coupon').val()
                        let lifetime_plan = '{{ config('saas.plans.lifetime') }}'

                        let plans = response.plans

                        let html = ``

                        for (let i = 0; i < plans.length; i++) {
                            html = html + `<option value='` + plans[i]['id'] + `'>` + plans[i]['name'] +
                                `</option>`
                        }
                        $('#plans').html(html)

                        if (is_redeem_coupon == '1') {
                            $('#plans').html(`<option value="` + lifetime_plan +
                                `" selected> {{ translate('Lifetime Plan') }} </option>`)
                            $('#plans').prop('disabled', true);
                        } else {
                            if (html == '') {
                                $('#plans').html(
                                    `<option value="-1"> {{ translate('Select Plan') }} </option>`)
                            }
                        }
                    } else {
                        toastr.error("{{ translate('No plan found with this package !') }}");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $("#register").prop("disabled", false);
                    toastr.error("{{ translate('No plan found with this package !') }}");
                }
            });
        }
    </script>
@endsection
