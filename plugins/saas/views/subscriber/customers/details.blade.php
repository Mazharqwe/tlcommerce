@extends('core::base.layouts.master')
@section('title')
    {{ translate('Customer Details') }}
@endsection
@section('custom_css')
    <!-- ======= BEGIN PAGE LEVEL PLUGINS STYLES ======= -->
    <link rel="stylesheet" href="{{ asset('/public/backend/assets/plugins/data-table/css/jquery.dataTables.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('/public/backend/assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('/public/backend/assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">

    <link rel="stylesheet"
        href="{{ asset('/public/backend/assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- ======= END BEGIN PAGE LEVEL PLUGINS STYLES ======= -->
@endsection
@section('main_content')
    <div class="row">
        <div class="col-md-12">
            <!-- Store List-->
            <div class="card mb-30">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="font-20">{{ translate('All Stores') }}</h4>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="hoverable text-nowrap border-top2 " id="store_list">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Store Name') }} </th>
                                <th>{{ translate('Package Name') }} </th>
                                <th>{{ translate('Plan Name') }}</th>
                                <th>{{ translate('Valid Untill') }}</th>
                                <th>{{ translate('Status') }}</th>
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
                                @endphp
                                <tr class="{{ $class }}">
                                    <td>{{ $key }}.</td>
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
                                        @if ($store->status == 0)
                                            <span class="badge badge-primary">{{ translate('Pending') }}</span>
                                        @else
                                            <span class="badge badge-success">{{ translate('Approved') }}</span>
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
                                                <a
                                                    href="{{ route('plugin.saas.store.details', $store->id) }}">{{ translate('Show Details') }}</a>
                                                @if ($store->domain != null)
                                                    <a
                                                        href="{{ $store->domain }}/admin">{{ translate('Visit Admin Panel') }}</a>
                                                    <a href="{{ $store->domain }}">{{ translate('Visit Frontend') }}</a>
                                                @endif
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
                </div>
            </div>
            <!-- Store List-->

            <!-- Payment Histories-->
            <div class="card mb-30">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="font-20">{{ translate('Payment Histories') }}</h4>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="hoverable text-nowrap border-top2 " id="payment_history">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Payment ID') }} </th>
                                <th>{{ translate('Payment Date') }} </th>
                                <th>{{ translate('Payment Method') }} </th>
                                <th>{{ translate('Payment Currency') }} </th>
                                <th>{{ translate('Final Amout') }} </th>
                                <th>{{ translate('Invoice') }} </th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $key = 1;
                            @endphp
                            @foreach ($payment_history as $payment)
                                <tr>
                                    <td>{{ $key }}</td>
                                    <td>{{ $payment->pid }}</td>
                                    <td>{{ $payment->updated_at }}</td>
                                    <td>{{ $payment->method }}</td>
                                    <td>{{ $payment->currency }}</td>
                                    <td>{{ $payment->final_amount }}</td>
                                    <td>
                                        <a href="{{ route('plugin.saas.admin.print.subscription.payment.invoice', $payment->store_id) }}"
                                            class="btn btn-success sm">{{ translate('Invoice') }}</a>
                                    </td>
                                </tr>
                                @php
                                    $key++;
                                @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Payment Histories-->

            <!-- Custom Domain Requests-->
            <div class="card mb-30">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="font-20">{{ translate('Custom Domain Requests') }}</h4>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="hoverable text-nowrap border-top2" id="domain_request_history">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Store Name') }} </th>
                                <th>{{ translate('Requested For Domain') }} </th>
                                <th>{{ translate('Requested Domain') }} </th>
                                <th>{{ translate('Duration') }} </th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $key = 1;
                            @endphp
                            @foreach ($domain_request as $domain)
                                @php
                                    $status = '';
                                    $class = 'badge-danger';
                                    if ($domain->status == 0) {
                                        $status = 'Pending';
                                        $class = 'badge-warning';
                                    }
                                    if ($domain->status == 1) {
                                        $status = 'Approved';
                                        $class = 'badge-success';
                                    }
                                    if ($domain->status == 2) {
                                        $status = 'Cancelled';
                                        $class = 'badge-danger';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $key }}</td>
                                    <td>{{ $domain->saasAccount->store_name }}</td>
                                    <td>{{ $domain->current_domain }} <span
                                            class="badge mb-2 {{ $class }}">{{ $status }}</span></td>
                                    <td>{{ $domain->requested_domain }}</td>
                                    <td>
                                        @if ($domain->status == 0)
                                            -
                                        @endif
                                        @if ($domain->status == 1)
                                            {{ $domain->approved_date }} - {{ translate('Present') }}
                                        @endif
                                        @if ($domain->status == 2)
                                            {{ $domain->approved_date }} - {{ $domain->cancelled_date }}
                                        @endif
                                    </td>
                                </tr>
                                @php
                                    $key++;
                                @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Custom Domain Requests-->
        </div>
    </div>
@endsection
@section('custom_scripts')
    <script src="{{ asset('/public/backend/assets/plugins/data-table/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('/public/backend/assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('/public/backend/assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}">
    </script>
    <script src="{{ asset('/public/backend/assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}">
    </script>

    <script type="application/javascript">
        (function($) {
            "use strict";
            $("#store_list").DataTable();
            $("#payment_history").DataTable();
            $("#domain_request_history").DataTable();
        })(jQuery);
    </script>
@endsection
