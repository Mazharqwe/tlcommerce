<!DOCTYPE html>
<html lang="en" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body {
            font-family: '{{ $font_family }}', Arial, sans-serif;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: right; /* Change text alignment to right for RTL */
        }

        .text-right {
            text-align: right;
        }

        .invoice-p {
            margin: 0;
            text-align: right; /* Change text alignment to right for RTL */
        }

        .qr-code {
            max-width: 100%;
            height: auto;
        }

        .thead-light th {
            text-align: right; /* Change text alignment to right for RTL */
        }
    </style>
</head>

<body>
    <table class="table table-borderless invoice-top">
        <tbody>
            <tr>
                <td>
                    @if ($order_info['system_properties']['logo'] != null)
                        <img src="{{ 'https://'.env('CENTRAL_DOMAIN').$order_info['system_properties']['logo'] }}"
                            alt={{ $order_info['system_properties']['title'] }}>
                    @else
                        <h2>{{ $order_info['system_properties']['title'] }}</h2>
                    @endif
                </td>
                <td class="text-right">
                    <p class="invoice-title">{{ translate('INVOICE', getLocale()) }}</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p class="invoice-p">{{ $order_info['system_properties']['title'] }}</p>
                    @if ($order_info['system_properties']['address'] != null)
                        <p class="invoice-p">{{ $order_info['system_properties']['address'] }}</p>
                    @endif
                    @if ($order_info['system_properties']['email'] != null)
                        <p class="invoice-p">{{ translate('Email', getLocale()) }}:
                            {{ $order_info['system_properties']['email'] }}</p>
                    @endif
                    @if ($order_info['system_properties']['phone'] != null)
                        <p class="invoice-p">{{ translate('Phone', getLocale()) }}:
                            {{ $order_info['system_properties']['phone'] }}</p>
                    @endif
                </td>
                <td class="text-right">
                    <p class="invoice-p">{{ translate('Order ID', getLocale()) }}:
                        {{ $order_info['order_code'] }}</p>
                    <p class="invoice-p">{{ translate('Order date', getLocale()) }}: {{ $order_info['date'] }}
                    </p>
                    <p class="invoice-p">{{ translate('Payment method', getLocale()) }}:
                        {{ $order_info['payment_method'] }}</p>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="table table-borderless">
        <tbody>
            <tr>
                <td>
                    <p class="invoice-p">{{ translate('Bill to', getLocale()) }}:</p>
                    <p class="invoice-p">{{ $order_info['billing_info']['name'] }}</p>
                    <p class="invoice-p">
                        @if ($order_info['billing_info']['address'] != null)
                            <span>
                                {{ $order_info['billing_info']['address'] }},
                            </span>
                        @endif
                        @if ($order_info['billing_info']['city'] != null)
                            <span>
                                {{ $order_info['billing_info']['city'] }},
                            </span>
                        @endif
                        @if ($order_info['billing_info']['state'] != null)
                            <span>
                                {{ $order_info['billing_info']['state'] }},
                            </span>
                        @endif
                        @if ($order_info['billing_info']['country'] != null)
                            <span>
                                {{ $order_info['billing_info']['country'] }}.
                            </span>
                        @endif
                    </p>
                    @if ($order_info['billing_info']['postal_code'] != null)
                        <p class="invoice-p">{{ translate('Postal Code', getLocale()) }}:
                            {{ $order_info['billing_info']['postal_code'] }}</p>
                    @endif
                    <p class="invoice-p">{{ translate('Email', getLocale()) }}:
                        {{ $order_info['billing_info']['email'] }}</p>
                    @if ($order_info['billing_info']['phone'] != null)
                        <p class="invoice-p">{{ translate('Phone', getLocale()) }}:
                            {{ $order_info['billing_info']['phone'] }}</p>
                    @endif
                </td>
                <td class="text-right">
                    <img src="data:image/png;base64, {!! $qr_code !!}" class="qr-code">
                </td>
            </tr>
        </tbody>
    </table>
    @php
        $total_amount = 0;
        $sub_total = 0;
        $total_tax = 0;
        $total_discount = 0;
        $total_shipping_cost = 0;
        $total_paid = 0;
    @endphp
    <table class="table invoice-product-table p5">
        <thead class="thead-light">
            <tr>
                <td scope="col" class="invoice-p">{{ translate('Name', getLocale()) }}</td>
                <td scope="col" class="invoice-p">{{ translate('Quantity', getLocale()) }}</td>
                <td scope="col" class="invoice-p">{{ translate('Unit Price', getLocale()) }}</td>
                <td scope="col" class="invoice-p">{{ translate('Tax', getLocale()) }}</td>
                <td scope="col" class="text-right invoice-p">{{ translate('Total', getLocale()) }}</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($order_info['products'] as $item)
                @php
                    $sub_total += $item->unit_price * $item->quantity;
                    $total_tax += $item->tax;
                    $total_shipping_cost += $item->delivery_cost;
                    $total_discount += $item->couponDiscountedAmount();
                    $total_amount += $item->unit_price * $item->quantity + $item->tax + $item->delivery_cost;
                    $total_paid += $item->total_paid;
                    
                @endphp
                <tr>
                    <td class="invoice-p">
                        <p class="invoice-p">{{ $item->product_details->name }}</p>
                        @if ($item->variant != null)
                            <p class="invoice-p">{{ $item->variant }}</p>
                        @endif
                    </td>
                    <td class="invoice-p">{{ $item->quantity }}</td>
                    <td class="invoice-p currency">{{ currencyExchange($item->unit_price, true, null, false) }}</td>
                    <td class="invoice-p currency">{{ currencyExchange($item->tax, true, null, false) }}</td>
                    <td class="text-right invoice-p currency">
                        {{ currencyExchange($item->unit_price * $item->quantity, true, null, false) }}
                    </td>
                </tr>
            @endforeach


            <tr>
                <td colspan="3">
                   
                </td>
                <td colspan="2">
                    <table class="table table-borderless w-100 invoice-summary-table">
                        <tr>
                            <td class="border-top-0 invoice-p p-0">{{ translate('Subtotal', getLocale()) }}</td>
                            <td class="border-top-0 text-right invoice-p p-0 currency">
                                {{ currencyExchange($sub_total, true, null, false) }}</td>
                        </tr>
                        <tr>
                            <td class="border-top-0 invoice-p p-0">{{ translate('Tax', getLocale()) }}</td>
                            <td class="border-top-0 text-right invoice-p p-0 currency">
                                {{ currencyExchange($total_tax, true, null, false) }}</td>
                        </tr>
                        <tr>
                            <td class="border-top-0 invoice-p p-0">{{ translate('Shipping', getLocale()) }}</td>
                            <td class="border-top-0 text-right invoice-p p-0 currency">
                                {{ currencyExchange($total_shipping_cost, true, null, false) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border-top-0 invoice-p p-0">{{ translate('Discount', getLocale()) }}</td>
                            <td class="border-top-0 invoice-p text-right p-0 currency">
                                {{ currencyExchange($total_discount, true, null, false) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border-top-0 invoice-p p-0">{{ translate('Grand Total', getLocale()) }}
                            </td>
                            <td class="border-top-0 invoice-p text-right p-0 currency">
                                {{ currencyExchange($total_amount - $total_discount, true, null, false) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border-top-0 invoice-p p-0">{{ translate('Paid', getLocale()) }}</td>
                            <td class="border-top-0 invoice-p text-right p-0 currency">
                                {{ currencyExchange($total_paid, true, null, false) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="border-top-0 invoice-p p-0">{{ translate('Total Due', getLocale()) }}</td>
                            <td class="border-top-0 invoice-p text-right p-0 currency">
                                {{ currencyExchange($total_amount - $total_discount - $total_paid, true, null, false) }}
                            </td>
                        </tr>

                    </table>
                </td>
            <tr>

        </tbody>
    </table>
</body>

</html>