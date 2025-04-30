@php
    $plans = getAllPlans();
    $plugins = getActivePlugins();
    $payment_methods = getEcommercePaymentGateways();

    $applicable_plugins = $package_details->plugins->toArray();
    $applicable_payment_methods = $package_details->payment_methods->toArray();
@endphp
@extends('core::base.layouts.master')
@section('title')
    {{ translate('Store Details') }}
@endsection
@section('main_content')
    <div class="row">
        <div class="col-12">
            <div class="invoice-pd c2-bg">
                <div class="row">
                    <div class="col-md-8">
                        <div class="invoice-left">
                            <h3 class="mb-3 text-white">
                                {{ $saas_account_details->store_name }}
                            </h3>

                            <ul class="status-list">
                                @if ($saas_account_details->domain != null)
                                    <li>
                                        <a href="https://{{ $saas_account_details->domain }}">
                                            <h3 class="btn white status-btn btn-danger">{{ translate('Visit Frontend') }}
                                            </h3>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="https://{{ $saas_account_details->domain }}/admin">
                                            <button
                                                class="btn white status-btn btn-dark">{{ translate('Visit Admin Panel') }}</button>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="invoice-right mt-5 mt-md-0">
                            <ul class="status-list">
                                <li><span class="key font-14">{{ translate('Subscriber') }}:</span> <span
                                        class="white bold font-17">{{ $saas_account_details->subscriber }}</span></li>
                                @if ($saas_account_details->domain != null)
                                    <li><span class="key font-14">{{ translate('Domain') }}:</span> <span
                                            class="white bold font-17">{{ $saas_account_details->domain }}</span></li>
                                    <li><span class="key font-14">{{ translate('Database') }}:</span> <span
                                            class="white bold font-17">{{ $saas_account_details->database }}</span></li>
                                @endif
                                <li><span class="key font-14">{{ 'Membership' }}:</span> <span
                                        class="white bold font-17">{{ $saas_account_details->membership_type }}</span></li>
                                <li><span class="key font-14">{{ translate('Package') }}:</span> <span
                                        class="white bold font-17">{{ translatePackageName($saas_account_details->package_id) }}</span></li>
                                <li><span class="key font-14">{{ translate('Plan') }}:</span> <span
                                        class="white bold font-17">{{translate($saas_account_details->plan_name) }}</span></li>
                                <li><span class="key font-14">{{ translate('Subscribed') }}:</span> <span
                                        class="white bold font-17">{{ $saas_account_details->created_at }}</span>
                                </li>
                                <li><span class="key font-14">{{ translate('Due Date') }}:</span> <span
                                        class="white bold font-17">{{ $saas_account_details->due_date == null ? 'Lifetime' : $saas_account_details->due_date }}</span>
                                </li>
                                <li><span class="key font-14">{{ translate('Renewed') }}:</span> <span
                                        class="white bold font-17">{{ $saas_account_details->renewed }}</span>
                                </li>
                                <li><span class="key font-14">{{ translate('Status') }}:</span>
                                    <span class="white bold font-17">
                                        @if ($saas_account_details->status == 0)
                                            {{ translate('Pending') }}
                                        @else
                                            {{ translate('Approved') }}
                                        @endif
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white invoice-pd">
                <div class="row">
                    <div class="col-xl-4 col-md-6">
                        <div class="invoice invoice-form">
                            <h5 class="mb-3">{{ translate('Applicable Features') }}</h5>
                            <ul class="list-unstyled mb-4">
                                @foreach ($plugins as $plugin)
                                    @if ($plugin->type != 'saas' && $plugin->location != 'tlecommercecore')
                                        <li class="mb-2">
                                            @if (in_array($plugin->id, array_column($applicable_plugins, 'plugin_id')))
                                                <i class="icofont-check"></i>
                                            @else
                                                <i class="icofont-close text-danger"></i>
                                            @endif

                                            {{ $plugin->name }}
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <div class="invoice invoice-form">
                            @php
                                $privileges = $package_details->privileges;
                            @endphp
                            @if ($privileges != null)
                                <h5 class="mb-3">{{ translate('Access Privileges') }}</h5>
                                <ul class="list-unstyled mb-4">
                                    @foreach ($privileges as $key => $value)
                                        @php
                                            $privilege = str_replace('package_privileges_', '', $key);
                                            $privilege = ucwords(implode(' ', explode('_', $privilege)));
                                        @endphp
                                        <li class="mb-2">
                                            <i class="icofont-check"></i>
                                            {{ $privilege }} - {{ $value == -1?'Unlimitted':$value }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <div class="invoice invoice-form">
                            <h5 class="mb-3">{{ translate('Applicable Payment Methods') }}</h5>
                            <ul class="list-unstyled mb-4">
                                @foreach ($payment_methods as $method => $id)
                                    <li class="mb-2">
                                        @if (in_array($id, array_column($applicable_payment_methods, 'payment_method')))
                                            <i class="icofont-check"></i>
                                        @else
                                            <i class="icofont-close text-danger"></i>
                                        @endif
                                        @if ($method == 'cod')
                                            {{translate('Cash On Delivery')}}
                                        @else
                                            {{ ucfirst($method) }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <div class="card mb-30">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="font-20">{{ translate('Payment History') }}</h4>
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
                                    <a href="{{ route('plugin.saas.admin.print.subscription.payment.invoice', $saas_account_details->store_id) }}"
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
    </div>
@endsection
