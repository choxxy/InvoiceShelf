<?php

namespace App\Domains\Catalog\Application;

use App\Domains\Catalog\Models\Item;
use App\Domains\Sales\Models\Invoice;

class InventoryService
{
    /**
     * Deduct stock for items on a newly created or updated invoice.
     */
    public function recordInvoice(Invoice $invoice): void
    {
        $isCredit = $invoice->isCreditNote();

        foreach ($invoice->items as $lineItem) {
            if (! $lineItem->item_id) {
                continue;
            }

            $item = Item::find($lineItem->item_id);
            if (! $item || ! $item->track_inventory) {
                continue;
            }

            $qty = (float) $lineItem->quantity;

            if ($isCredit) {
                $item->increment('quantity', $qty);
            } else {
                $item->decrement('quantity', $qty);
            }
        }
    }

    /**
     * Restore stock for items when an invoice is updated or deleted.
     */
    public function restoreInvoice(Invoice $invoice): void
    {
        $isCredit = $invoice->isCreditNote();

        foreach ($invoice->items as $lineItem) {
            if (! $lineItem->item_id) {
                continue;
            }

            $item = Item::find($lineItem->item_id);
            if (! $item || ! $item->track_inventory) {
                continue;
            }

            $qty = (float) $lineItem->quantity;

            if ($isCredit) {
                $item->decrement('quantity', $qty);
            } else {
                $item->increment('quantity', $qty);
            }
        }
    }
}
