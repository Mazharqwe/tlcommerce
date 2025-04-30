@php
$packages = getAllActivePackages();
@endphp
@extends('core::base.layouts.master')
@section('title')
{{ translate('Stores') }}
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
                    <h4 class="font-20">{{ translate('All Stores') }}</h4>
                </div>
            </div>
            <div class="px-2 filter-area d-flex align-items-center mb-3">
                <!--Filter area-->
                <form method="get" action="{{ route('plugin.saas.all.stores') }}">
                    <select class="theme-input-style mb-2" name="per_page">
                        <option value="">{{ translate('Per page') }}</option>
                        <option value="5" @selected(request()->has('per_page') && request()->get('per_page') == '5')>5</option>
                        <option value="20" @selected(request()->has('per_page') && request()->get('per_page') == '20')>20</option>
                        <option value="50" @selected(request()->has('per_page') && request()->get('per_page') == '50')>50</option>
                        <option value="all" @selected(request()->has('per_page') && request()->get('per_page') == 'all')>All</option>
                    </select>
                    <select class="theme-input-style mb-2" name="subscription_status">
                        <option value="all" @selected(request()->has('subscription_status') && request()->get('subscription_status') == 'all')>{{ translate('Subscription status') }}
                        </option>
                        <option value="0" @selected(request()->has('subscription_status') && request()->get('subscription_status') == 0)>
                            {{ translate('Pending') }}
                        </option>
                        <option value="1" @selected(request()->has('subscription_status') && request()->get('subscription_status') == 1)>
                            {{ translate('Active') }}
                        </option>
                    </select>
                    <input type="text" class="theme-input-style mb-2" id="store_creation_date" placeholder="Store creation date" name="store_creation_date" readonly>
                    <input type="text" name="store_name" class="theme-input-style  mb-2" value="{{ request()->has('store_name') ? request()->get('store_name') : '' }}" placeholder="Enter store name">
                    <input type="text" name="subscriber_name" class="theme-input-style  mb-2" value="{{ request()->has('subscriber_name') ? request()->get('subscriber_name') : '' }}" placeholder="Enter subscriber name">
                    <button type="submit" class="btn long">{{ translate('Filter') }}</button>
                </form>

                @if (request()->has('store_creation_date') || request()->has('subscription_status') || request()->has('store_name'))
                <a class="btn long btn-danger" href="{{ route('plugin.saas.all.stores') }}">
                    {{ translate('Clear Filter') }}
                </a>
                @endif
                <!--End filter area-->
            </div>
            <div class="table-responsive">
                <table class="hoverable text-nowrap border-top2 " id="store_list">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Subscriber') }}</th>
                            <th>{{ translate('Store Name') }}</th>
                            <th>{{ translate('Package Name') }}</th>
                            <th>{{ translate('Plan Name') }}</th>
                            <th>{{ translate('Valid Untill') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Update') }}</th>
                            <th>{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $key = 1;
                        @endphp
                        @foreach ($stores as $store)
                        @php
                        $class = '';
                        if ($store->subscription_status == 0) {
                        $class = 'table-danger';
                        }
                        if ($store->subscription_status == 1) {
                        $class = 'table-warning';
                        }
                        $valid_till = $store->valid_till;

                        $currentDate = (new DateTime())->format('Y-m-d'); // creates a DateTime object representing the current date and time
                        $expired_date = (new DateTime($valid_till))->format('Y-m-d'); // creates a DateTime object from the date string

                        $disable_status = $store->is_db_created == 0 || $store->is_db_updated == 0 || $store->is_plugin_db_updated == 0 || $store->is_system_db_updated == 0 || $expired_date < $currentDate; $update_plan=$store->is_db_created == 1 && $store->is_db_updated == 1 && $store->is_plugin_db_updated == 1 && $store->is_system_db_updated == 1;
                            $jobStatus = jobExistsInQueue();
                            $is_loading = $jobStatus > 0;

                            @endphp
                            <tr class="{{ $class }}">
                                <td>{{ $key }}.</td>
                                <td> {{ $store->subscriber }} </td>
                                <td> {{ $store->store_name }} </td>
                                <td>{{ translatePackageName($store->package_id) }}</td>
                                <td>
                                    @if ($store->plan == null)
                                    <i class="icofont-ban"></i>
                                    @else
                                    {{ translate($store->plan) }}
                                    @endif
                                </td>
                                <td>
                                    @if ($store->valid_till == null || $store->plan_id == config('saas.plans.lifetime'))
                                    {{ translate('Lifetime') }}
                                    @else
                                    {{ $store->valid_till }}
                                    @endif
                                </td>
                                <td>
                                    <label class="switch glow primary medium">
                                        <input type="checkbox" name="status" id="change-status-{{ $store->id }}" class="change-status" data-storeid="{{ $store->id }}" data-tenant="{{ $store->domain == null ? 0 : 1 }}" data-database="{{ $store->database }}" data-isdbcreated="{{ $store->is_db_created }}" @checked($store->status == 1)
                                        @disabled($disable_status)>
                                        <span class="control"></span>
                                    </label>
                                </td>
                                <td>
                                    @if ($store->is_db_created == 0)
                                    <button class="btn btn-success sm change-status" data-storeid="{{ $store->id }}" data-tenant="{{ $store->domain == null ? 0 : 1 }}" data-database="{{ $store->database }}" data-isdbcreated="{{ $store->is_db_created }}" {{ $is_loading ? 'disabled' : '' }}  @if($is_loading) data-toggle="tooltip" data-placement="top" title="{{translate('Please wait fiew minites and reload the page')}}" @endif>
                                        {{ translate('Create Databse') }}
                                    </button>
                                    @elseif($store->is_db_updated == 0)
                                    <button class="btn btn-success sm update-database" data-storeid="{{ $store->id }}" {{ $is_loading ? 'disabled' : '' }} @if($is_loading) data-toggle="tooltip" data-placement="top" title="{{translate('Please wait fiew minites and reload the page')}}" @endif>
                                        {{ translate('Update Databse') }}
                                    </button>
                                    @elseif($store->is_system_db_updated == 0)
                                    <button class="btn btn-success sm update-system" data-storeid="{{ $store->id }}" {{ $is_loading ? 'disabled' : '' }} @if($is_loading) data-toggle="tooltip" data-placement="top" title="{{translate('Please wait fiew minites and reload the page')}}" @endif>
                                        {{ translate('Update System') }}
                                    </button>
                                    @elseif($store->is_plugin_db_updated == 0)
                                    <button class="btn btn-success sm update-plugin" data-storeid="{{ $store->id }}" {{ $is_loading ? 'disabled' : '' }} @if($is_loading) data-toggle="tooltip" data-placement="top" title="{{translate('Please wait fiew minites and reload the page')}}" @endif>
                                        {{ translate('Update Plugin') }}
                                    </button>
                                    @else
                                    <i class="icofont-ban"></i>
                                    @endif
                                </td>
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
                                            <a href="{{ route('plugin.saas.store.details', $store->id) }}">{{ translate('Show Details') }}</a>
                                            @if ($store->domain != null)
                                            <a href="https://{{ $store->domain }}/admin">{{ translate('Visit Admin Panel') }}</a>
                                            <a href="https://{{ $store->domain }}">{{ translate('Visit Frontend') }}</a>
                                            @endif
                                            @if ($update_plan)
                                            <a href="#" data-target="#updatePlan" class="update_plan" data-storeid="{{ $store->id }}" data-customerid="{{ $store->user_id }}">
                                                {{ translate('Update Plan') }}
                                            </a>
                                            @endif
                                            <a href="#" onclick="deleteConfirmation('{{ $store->id }}')">{{ translate('Delete Store') }}</a>
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
                    {!! $stores->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5-custom') !!}
                </div>
            </div>
        </div>
    </div>
    <!-- Store List-->

    <!-- Create Tenant -->
    <div class="modal fade" id="create_tenant" tabindex="-1" role="dialog" aria-labelledby="create_tenant" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('plugin.saas.create.tenant') }}" method="post" id="create-tenant">
                    @csrf
                    <input type="hidden" name="store_id" id="store_id" value="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{{ translate('Create Tenant') }}</h5>
                        <button type="button" class="close create_tenant_close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h6>{{ translate('Before clicking create button, please confirm following things - ') }}</h6>
                        <ul class="mt-3">
                            <li>{{ translate('You have created a new database - ') }} <b id="database"></b></li>
                            <li>{{ translate('Your database user name is - ') . env('DB_USERNAME') }}</li>
                            <li>{{ translate('Your database password is - ') . env('DB_PASSWORD') }}</li>
                            <li>{{ translate('Your database host is - ') . env('DB_HOST') }}</li>
                            <li>{{ translate('Your database port is - ') . env('DB_PORT') }}</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn sm btn-secondary create_tenant_close" data-dismiss="modal" id="create-database">
                            {{ translate('Close') }}
                        </button>
                        <button type="submit" class="btn sm btn-primary" id="create-store">
                            <img src="{{ asset('/public/backend/assets/img/loader-w4.svg') }}" alt="" class="d-none" id="loader">
                            {{ translate('Create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--Store Delete Modal-->
    <div id="delete-modal" class="delete-modal modal fade show" aria-modal="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ translate('Delete Confirmation') }}</h4>
                </div>
                <div class="modal-body text-center">
                    <p class="mt-1">
                        {{ translate('Are you sure to delete this store ? Once you delete, all data related to this will be deleted') }}.
                    </p>
                    <form method="POST" action="{{ route('plugin.saas.store.delete') }}" id="delete-tenant">
                        @csrf
                        <input type="hidden" id="store_id_to_delete" name="store_id">
                        <button type="button" class="btn long mt-2 btn-danger" data-dismiss="modal">{{ translate('cancel') }}</button>
                        <button type="submit" class="btn long mt-2" id="delete-store">
                            <img src="{{ asset('/public/backend/assets/img/loader-w4.svg') }}" alt="" class="d-none" id="loader2">
                            {{ translate('Delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--Store Delete Modal End-->

    <!-- Update Plan-->
    <div class="modal fade" id="updatePlan" tabindex="-1" role="dialog" aria-labelledby="updatePlanLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createStoreLabel">{{ translate('Update Plan') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('plugin.saas.update.store.plan') }}" method="post" id="update-plan-form">
                    @csrf
                    <input type="hidden" name="customer_id" id="customer_id">
                    <input type="hidden" name="store_id" id="update_store_id">
                    <div class="modal-body">
                        <div class="form-group mb-20">
                            <label for="package" class="mb-2 font-14 bold black">{{ translate('Package') }}<span class="text-danger"> *
                                </span></label>
                            <select class="theme-input-style" id="package" onchange="getAllPlansOfPackage()">
                                <option>{{ translate('Select Package') }}</option>
                                @for ($i = 0; $i < sizeof($packages); $i++) <option value="{{ $packages[$i]->id }}" {{ collect(old('packages'))->contains($packages[$i]->id) ? 'selected' : '' }}>
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
                            <label for="plan" class="mb-2 font-14 bold black">{{ translate('Plans') }}<span class="text-danger"> *
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
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn-primary" id="update-plan">
                            <img src="{{ asset('/public/backend/assets/img/loader-w4.svg') }}" alt="" class="d-none" id="update-plan-loader">
                            {{ translate('Update Plan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Update Plan-->
</div>
@endsection
@section('custom_scripts')
<script src="{{ asset('/public/backend/assets/plugins/moment/moment.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('/public/backend/assets/plugins/daterangepicker/daterangepicker.js') }}">
</script>
<script>
    (function($) {
        "use strict";

        $(document).ready(function() {
            'use strict'
            $('#delete-tenant').submit(function(event) {
                event.preventDefault()
                $('#delete-store').prop('disabled', true)
                $('#loader2').removeClass('d-none')
                this.submit();
            });

            $('#create-tenant').submit(function(event) {
                event.preventDefault()
                $('#create-store').prop('disabled', true)
                $('#loader').removeClass('d-none')
                this.submit();
            });


            $('#update-plan-form').submit(function(event) {
                event.preventDefault()
                $('#update-plan').prop('disabled', true)
                $('#update-plan-loader').removeClass('d-none')
                this.submit();
            });

            $('.update_plan').on('click', function() {
                let customer_id = $(this).data('customerid')
                let store_id = $(this).data('storeid')

                $('#customer_id').val(customer_id)
                $('#update_store_id').val(store_id)

                $('#updatePlan').modal('show');
            })
        });

        // Filter date range
        function cb(start, end) {

            let initVal =
                '{{ request()->has("store_creation_date") ? request()->get("store_creation_date") : "" }}';
            $('#store_creation_date').val(initVal);
        }

        var start = moment().subtract(0, 'days');
        var end = moment();

        $('#store_creation_date').on('apply.daterangepicker', function(ev, picker) {
            let val = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format(
                'YYYY-MM-DD')
            $('#store_creation_date').val(val);
        });
        $('#store_creation_date').daterangepicker({
            startDate: start,
            endDate: end,
            showCustomRangeLabel: true,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                    'month').endOf('month')]
            },
            locale: {
                format: 'YYYY-MM-DD', // Adjust the format as needed
            }
        }, cb);

        cb(start, end);

        /**
         * 
         * Change featured  status 
         * 
         * */
        $('.change-status').on('click', function(e) {
            let $this = $(this);
            let id = $this.data('storeid');
            let tenant = $this.data('tenant')
            let database = $this.data('database')
            let isdbcreated = $this.data('isdbcreated')

            if (isdbcreated == 0) {
                $('#store_id').val(id)
                $('#database').html(database)
                $('#create_tenant').modal('show')
            } else {
                $.post('{{ route("plugin.saas.update.store.status") }}', {
                    _token: '{{ csrf_token() }}',
                    id: id
                }, function(data) {
                    if (data.success) {
                        toastr.success(
                            '{{ translate("Store status updated successfully") }}',
                            "Success");
                    } else {
                        toastr.error('{{ translate("Store status update failed") }}',
                            "Error!");
                    }
                })
            }
        });

        /**
         * 
         * update database 
         * 
         * */
        $('.update-database').on('click', function(e) {
            let $this = $(this);
            let id = $this.data('storeid');

            $.post('{{ route("plugin.saas.update.tenant") }}', {
                _token: '{{ csrf_token() }}',
                id: id
            }, function(data) {
                if (data.success) {
                    $this.prop('disabled', true)
                    toastr.success(
                        '{{ translate("Store database updated successfully") }}',
                        "Success");
                    location.reload()
                } else {
                    toastr.error('{{ translate("Store database update failed") }}',
                        "Error!");
                }
            })
        });

        /**
         * Update plugin
         */
        $('.update-plugin').on('click', function(e) {
            let $this = $(this);
            let id = $this.data('storeid');

            $.post('{{ route("plugin.saas.update.tenant.plugin") }}', {
                _token: '{{ csrf_token() }}',
                id: id
            }, function(data) {
                if (data.success) {
                    $this.prop('disabled', true)
                    toastr.success(
                        '{{ translate("Store database updated successfully") }}',
                        "Success");
                    location.reload()
                } else {
                    toastr.error('{{ translate("Store database update failed") }}',
                        "Error!");
                }
            })
        });


        /**
         * Update System
         */
        $('.update-system').on('click', function(e) {
            let $this = $(this);
            let id = $this.data('storeid');

            $.post('{{ route("plugin.saas.update.tenant.system") }}', {
                _token: '{{ csrf_token() }}',
                id: id
            }, function(data) {
                if (data.success) {
                    $this.prop('disabled', true)
                    toastr.success(
                        '{{ translate("Store database updated successfully") }}',
                        "Success");
                    location.reload()
                } else {
                    toastr.error('{{ translate("Store database update failed") }}',
                        "Error!");
                }
            })
        });
    })(jQuery);

    /**
     * show store delete confirmation modal
     */
    function deleteConfirmation(id) {
        "use strict";
        $("#store_id_to_delete").val(id);
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
            url: '{{ route("plugin.saas.get.plans.according.to.package") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                package_id: selected_package
            },
            success: function(response) {
                $("#register").prop("disabled", false);
                if (response.success) {
                    let is_redeem_coupon = $('#is_redeem_coupon').val()
                    let lifetime_plan = '{{ config("saas.plans.lifetime") }}'

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