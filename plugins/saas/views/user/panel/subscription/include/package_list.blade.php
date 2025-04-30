@php
    $plans = getAllPlans();
    $plugins = getActivePlugins();
    $payment_methods = getEcommercePaymentGateways();
@endphp
@foreach ($free_packages as $package)
    @php
        $applicable_plugins = $package->plugins->toArray();
        $applicable_payment_methods = $package->payment_methods->toArray();
    @endphp
    <div class="col-lg-4">
        <div class="price-box mb-30">
            <div class="price-header bg-primary">
                <div class="p_header-img text-white w-100 flex-column align-items-start">
                    <h4 class="text-center mb-2">{{ translatePackageName($package->id) }}</h4>
                    <div class="d-flex align-items-end mt-2">
                        <h1>{{ currencyExchange(0) }}</h1>
                        <p class="ml-2">{{ translate('For Lifetime') }}</p>
                    </div>
                </div>
            </div>
            <div class="price-body">
                <h5 class="mb-3">{{ translate('Applicable Features') }}</h5>
                <ul class="list-unstyled mb-4">
                    @foreach ($plugins as $plugin)
                        @if ($plugin->type != 'saas' && $plugin->location != 'tlecommercecore')
                            <li>
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

                @php
                    $privileges = $package->privileges != null ? $package->privileges : null;
                @endphp
                @if ($privileges != null)
                    <h5 class="mb-3">{{ translate('Access Privileges') }}</h5>
                    <ul class="list-unstyled mb-4">
                        @foreach ($privileges as $key => $value)
                            @php
                                $privilege = str_replace('package_privileges_', '', $key);
                                $privilege = ucwords(implode(' ', explode('_', $privilege)));
                            @endphp
                            <li>
                                <i class="icofont-check"></i>
                                {{ $privilege }} - {{ $value == -1 ? 'Unlimitted' : $value }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                <h5 class="mb-3">{{ translate('Applicable Payment Methods') }}</h5>
                <ul class="list-unstyled mb-4">
                    @foreach ($payment_methods as $method => $id)
                        <li>
                            @if (in_array($id, array_column($applicable_payment_methods, 'payment_method')))
                                <i class="icofont-check"></i>
                            @else
                                <i class="icofont-close text-danger"></i>
                            @endif
                            @if ($method == 'cod')
                                Cash On Delivery
                            @else
                                {{ ucfirst($method) }}
                            @endif
                        </li>
                    @endforeach
                </ul>

                <form action="{{ route('plugin.saas.confirm.subscription') }}" method="post">
                    @csrf
                    @php
                        if ($store_id != 'null') {
                            $is_for_update = 1;
                        } else {
                            $is_for_update = 0;
                        }
                    @endphp

                    @if ($current_plan != null && $current_plan['package_id'] == $package->id)
                        <button type="submit" class="btn btn-info sm btn-block"
                            disabled>{{ translate('Current Plan') }}</button>
                    @else
                        @php
                            $button_text = translate('Subscribe Now');
                            if ($current_plan != null) {
                                $button_text = translate('Upgrade Plan');
                                if ($current_plan['package_type'] == 'paid') {
                                    $button_text = translate('Downgrade Plan');
                                }
                            }
                        @endphp
                        @if ($is_for_update == 0)
                            <button type="button" class="btn btn-info sm btn-block" data-toggle="modal"
                                data-target="#storeNameModal"
                                onclick="setDataInModalForFreePackage('{{ $is_for_update }}','{{ $store_id }}','{{ $package->id }}','{{ $plan_id }}','member')">{{ $button_text }}</button>
                        @else
                            <input type="hidden" name="package_id" value="{{ $package->id }}">
                            <input type="hidden" name="plan_id" value="{{ $plan_id }}">
                            <input type="hidden" name="membership_type" value="member">
                            <input type="hidden" name="store_id" value="{{ $store_id }}">
                            <input type="hidden" name="is_for_update" value="{{ $is_for_update }}">
                            <button type="submit" class="btn btn-info sm btn-block">{{ $button_text }}</button>
                        @endif
                    @endif
                </form>
            </div>
        </div>
    </div>
@endforeach
@foreach ($paid_packages as $package)
    @php
        $applicable_plugins = $package->plugins->toArray();
        $applicable_payment_methods = $package->payment_methods->toArray();
    @endphp
    <div class="col-lg-4">
        <div class="price-box mb-30">
            <div class="price-header bg-primary">
                <div class="p_header-img text-white w-100 flex-column align-items-start">
                    <h4 class="text-center">{{ translatePackageName($package->id) }}</h4>
                    <div class="d-flex align-items-end mt-2">
                        <h1>{{ currencyExchange($package->cost) }}</h1>
                        @if ($package->plan_id != config('saas.plans.lifetime'))
                            <p class="ml-2"> {{ translate('For') }} {{ $package->duration }}
                                {{ translate('days') }}</p>
                        @else
                            <p class="ml-2">{{ translate('For Lifetime') }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="price-body">
                <h5 class="mb-3">{{ translate('Applicable Features') }}</h5>
                <ul class="list-unstyled mb-4">
                    @foreach ($plugins as $plugin)
                        @if ($plugin->type != 'saas' && $plugin->location != 'tlecommercecore')
                            <li>
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

                @php
                    $privileges = $package->privileges != null ? json_decode($package->privileges, true) : null;
                @endphp
                @if ($privileges != null)
                    <h5 class="mb-3">{{ translate('Access Privileges') }}</h5>
                    <ul class="list-unstyled mb-4">
                        @foreach ($privileges as $key => $value)
                            @php
                                $privilege = str_replace('package_privileges_', '', $key);
                                $privilege = ucwords(implode(' ', explode('_', $privilege)));
                            @endphp
                            <li>
                                <i class="icofont-check"></i>
                                {{ $privilege }} - {{ $value == -1 ? 'Unlimitted' : $value }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                <h5 class="mb-3">{{ translate('Applicable Payment Methods') }}</h5>
                <ul class="list-unstyled mb-4">
                    @foreach ($payment_methods as $method => $id)
                        <li>
                            @if (in_array($id, array_column($applicable_payment_methods, 'payment_method')))
                                <i class="icofont-check"></i>
                            @else
                                <i class="icofont-close text-danger"></i>
                            @endif
                            @if ($method == 'cod')
                                Cash On Delivery
                            @else
                                {{ ucfirst($method) }}
                            @endif
                        </li>
                    @endforeach
                </ul>

                <form action="{{ route('plugin.saas.confirm.subscription') }}" method="post">
                    @csrf
                    @if ($store_id != null)
                        <input type="hidden" name="is_for_update" value="1">
                    @else
                        <input type="hidden" name="is_for_update" value="0">
                    @endif
                    <input type="hidden" name="store_id" value="{{ $store_id }}">

                    <input type="hidden" name="package_id" value="{{ $package->id }}">
                    <input type="hidden" name="plan_id" value="{{ $plan_id }}">
                    <input type="hidden" name="membership_type"
                        value="{{ $current_plan == null ? 'trail' : 'member' }}">
                    @if ($current_plan != null && $current_plan['package_id'] == $package->id && $current_plan['package_plan'] == $plan_id)
                        <button type="submit" class="btn sm btn-info btn-block"
                            disabled>{{ translate('Current Plan') }}</button>
                    @else
                        @php
                            $button_text = translate('Subscribe Now');
                            if ($current_plan != null) {
                                $button_text = translate('Upgrade Plan');
                                if ($current_plan['package_type'] == 'paid' && $current_plan['plan_duration'] > $package->duration) {
                                    $button_text = translate('Downgrade Plan');
                                }
                            }
                        @endphp
                        <button type="submit" class="btn sm btn-block">{{ $button_text }}</button>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endforeach
