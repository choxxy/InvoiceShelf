<?php

use App\Domains\Accounts\Models\User;
use App\Domains\Catalog\Models\Item;
use App\Domains\Contacts\Models\Customer;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->company = $user->companies()->first();
    $this->withHeaders([
        'company' => $this->company->id,
    ]);
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('create tracked inventory item initializes stock and tracks sku', function () {
    $payload = [
        'name' => 'Carpet Cleaning Solution A',
        'price' => 2500,
        'sku' => 'SOL-001',
        'track_inventory' => true,
        'initial_quantity' => 100,
        'reorder_level' => 15,
        'company_id' => $this->company->id,
    ];

    $response = postJson('api/v1/items', $payload);
    $response->assertOk();

    $this->assertDatabaseHas('items', [
        'sku' => 'SOL-001',
        'track_inventory' => 1,
        'initial_quantity' => 100,
        'quantity' => 100,
        'reorder_level' => 15,
    ]);
});

test('create invoice with carpet dimensions stores width, length, count, and area', function () {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);

    $invoiceData = Invoice::factory()->raw([
        'customer_id' => $customer->id,
        'company_id' => $this->company->id,
        'items' => [
            InvoiceItem::factory()->raw([
                'name' => 'Persian Rug Cleaning',
                'description' => '10x12 ft cleaning per sq ft',
                'quantity' => 240, // 10 * 12 * 2
                'price' => 300,    // $3.00 / sq ft in cents
                'width' => 10,
                'length' => 12,
                'count' => 2,
                'area' => 240,
                'total' => 72000,
            ]),
        ],
    ]);

    $response = postJson('api/v1/invoices', $invoiceData);
    $response->assertOk();

    $this->assertDatabaseHas('invoice_items', [
        'name' => 'Persian Rug Cleaning',
        'width' => 10,
        'length' => 12,
        'count' => 2,
        'area' => 240,
        'quantity' => 240,
    ]);
});

test('invoice creation decrements tracked product stock and invoice deletion restores it', function () {
    $customer = Customer::factory()->create(['company_id' => $this->company->id]);
    $item = Item::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Wool Safe Shampoo',
        'sku' => 'WSS-50',
        'track_inventory' => true,
        'initial_quantity' => 50,
        'quantity' => 50,
        'price' => 1200,
    ]);

    $invoiceData = Invoice::factory()->raw([
        'customer_id' => $customer->id,
        'company_id' => $this->company->id,
        'items' => [
            InvoiceItem::factory()->raw([
                'item_id' => $item->id,
                'name' => $item->name,
                'quantity' => 6,
                'price' => 1200,
                'total' => 7200,
            ]),
        ],
    ]);

    $response = postJson('api/v1/invoices', $invoiceData);
    $response->assertOk();
    $invoiceId = $response->json('data.id');

    // Stock should be decremented from 50 to 44
    $item->refresh();
    expect((float) $item->quantity)->toBe(44.0);

    // Deleting the invoice should restore stock
    postJson('api/v1/invoices/delete', ['ids' => [$invoiceId]])->assertOk();

    $item->refresh();
    expect((float) $item->quantity)->toBe(50.0);
});

test('product inventory report endpoint returns json data for company', function () {
    Item::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Oriental Fringe Protector',
        'sku' => 'OFP-01',
        'track_inventory' => true,
        'initial_quantity' => 30,
        'quantity' => 25,
        'price' => 1500,
    ]);

    $response = $this->getJson("/reports/inventory/{$this->company->unique_hash}");
    $response->assertOk();
    $response->assertJsonStructure([
        'items',
        'total_sold_quantity',
        'total_revenue',
    ]);
});
