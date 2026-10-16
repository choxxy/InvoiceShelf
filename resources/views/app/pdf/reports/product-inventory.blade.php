@extends('app.pdf.reports.partials.layout')

@section('report-title', 'Product Inventory & Stock Report')
@section('footer-label', 'Total Revenue')

@section('footer-value'){!! format_money_pdf($totalRevenue, $currency) !!}@endsection

@section('report-body')
    <div class="report-section">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th class="report-amount report-col-count">Initial Stock</th>
                    <th class="report-amount report-col-count">Units Sold</th>
                    <th class="report-amount report-col-count">Current Stock</th>
                    <th class="report-amount report-col-amount">Sales Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>
                            {{ $item->name }}
                            @if ($item->unit_name)
                                <small style="color: #666;">({{ $item->unit_name }})</small>
                            @endif
                        </td>
                        <td>{{ $item->sku ?: '—' }}</td>
                        <td class="report-amount">{{ number_format($item->initial_quantity, 2) }}</td>
                        <td class="report-amount">{{ number_format($item->sold_quantity, 2) }}</td>
                        <td class="report-amount" style="font-weight: bold; {{ $item->quantity <= 0 ? 'color: #dc2626;' : '' }}">
                            {{ number_format($item->quantity, 2) }}
                        </td>
                        <td class="report-amount">{!! format_money_pdf($item->sold_revenue, $currency) !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="report-muted" colspan="6">No tracked products found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
