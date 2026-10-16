<?php

namespace App\Domains\Reporting\Http\Controllers;

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\CompanySetting;
use App\Domains\Catalog\Models\Item;
use App\Domains\Money\Models\Currency;
use App\Domains\Sales\Models\InvoiceItem;
use App\Platform\Http\Controller;
use App\Platform\Pdf\Facades\Pdf;
use App\Platform\Pdf\Rendering\PdfPageSetup;
use App\Platform\Pdf\Rendering\PdfTemplateUtils;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Silber\Bouncer\BouncerFacade;

class ProductInventoryReportController extends Controller
{
    /**
     * Render the product inventory report for the company named by hash.
     */
    public function __invoke(Request $request, string $hash)
    {
        $company = $this->reportedCompany($hash);

        App::setLocale(CompanySetting::getSetting('language', $company->id));

        $window = $request->only(['from_date', 'to_date']);

        $trackedItems = Item::query()
            ->where('company_id', $company->id)
            ->where('track_inventory', true)
            ->with('unit')
            ->orderBy('name')
            ->get();

        $salesQuery = InvoiceItem::query()
            ->whereCompany($company->id)
            ->whereNotNull('item_id');

        if (! empty($window['from_date']) && ! empty($window['to_date'])) {
            $salesQuery->applyInvoiceFilters($window);
        }

        $salesByItem = $salesQuery
            ->selectRaw('item_id, sum(quantity) as sold_qty, sum(base_total) as sold_amount')
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        $reportItems = $trackedItems->map(function (Item $item) use ($salesByItem) {
            $sales = $salesByItem->get($item->id);
            $soldQty = $sales ? (float) $sales->sold_qty : 0.0;
            $soldRevenue = $sales ? (int) $sales->sold_amount : 0;

            return (object) [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'unit_name' => $item->unit?->name,
                'initial_quantity' => (float) $item->initial_quantity,
                'quantity' => (float) $item->quantity,
                'sold_quantity' => $soldQty,
                'sold_revenue' => $soldRevenue,
                'price' => $item->price,
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'items' => $reportItems,
                'total_sold_quantity' => $reportItems->sum('sold_quantity'),
                'total_revenue' => $reportItems->sum('sold_revenue'),
            ]);
        }

        view()->share([
            'items' => $reportItems,
            'totalSoldQuantity' => $reportItems->sum('sold_quantity'),
            'totalRevenue' => $reportItems->sum('sold_revenue'),
        ] + $this->pageChrome($request, $company));

        return $this->emit($request, 'product-inventory');
    }

    private function reportedCompany(string $hash): Company
    {
        $company = Company::query()->where('unique_hash', $hash)->firstOrFail();

        BouncerFacade::scope()->to($company->id);

        $this->authorize('view report', $company);

        return $company;
    }

    private function pageChrome(Request $request, Company $company): array
    {
        $pattern = CompanySetting::getSetting('carbon_date_format', $company->id);
        $opened = ! empty($request->from_date) ? Carbon::createFromFormat('Y-m-d', $request->from_date)->translatedFormat($pattern) : null;
        $closed = ! empty($request->to_date) ? Carbon::createFromFormat('Y-m-d', $request->to_date)->translatedFormat($pattern) : null;
        $currencyId = CompanySetting::getSetting('currency', $company->id);
        $currency = Currency::findOrFail($currencyId);

        return [
            'company' => $company,
            'logo' => $company->logo_path,
            'from_date' => $opened,
            'to_date' => $closed,
            'currency' => $currency,
        ];
    }

    private function emit(Request $request, string $design)
    {
        $design = PdfTemplateUtils::resolveView('reports', $design);

        $document = Pdf::loadView($design, [], PdfPageSetup::forReports());

        if ($request->exists('preview')) {
            return view($design);
        }

        return $request->exists('download') ? $document->download() : $document->stream();
    }
}
