<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\SiteSettingController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerPriceListController;
use App\Http\Controllers\SupplierPriceListController;
use App\Http\Controllers\CvsMapController;

use App\Http\Controllers\Accounting\ChartOfAccountController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\AccountingVoucherController;
use App\Http\Controllers\Accounting\AccountingPeriodController;
use App\Http\Controllers\Accounting\AccountsReceivableController;
use App\Http\Controllers\Accounting\AccountsPayableController;

use App\Http\Controllers\Attendance\AttendanceRecordController;
use App\Http\Controllers\Attendance\ShiftTypeController;
use App\Http\Controllers\Attendance\LeaveTypeController;
use App\Http\Controllers\Attendance\LeaveApplicationController;
use App\Http\Controllers\Attendance\AttendanceController;

use App\Http\Controllers\Inventory\ProductBatchController;
use App\Http\Controllers\Inventory\InventoryMovementController;
use App\Http\Controllers\Inventory\StockCountController;
use App\Http\Controllers\Inventory\InventoryAdjustController;

use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Sales\SalesOrderActionController;
use App\Http\Controllers\Sales\StoreCheckoutController;
use App\Http\Controllers\Sales\StoreOrderController;

use App\Http\Controllers\Purchase\PurchaseOrderController;
use App\Http\Controllers\Purchase\PurchaseOrderActionController;

use App\Http\Controllers\Manufacturing\BomController;
use App\Http\Controllers\Manufacturing\WorkOrderController;
use App\Http\Controllers\Manufacturing\WorkOrderActionController;

// =====================================================
// PUBLIC ROUTES (no auth required)
// =====================================================

// Auth
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');

// Public product/category browsing
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Store (public checkout & order lookup)
Route::post('/store/checkout', [StoreCheckoutController::class, 'checkout'])->middleware('throttle:public-form');
Route::get('/store/orders', [StoreOrderController::class, 'index']);

// Contact (public POST)
Route::post('/contact/messages', [ContactMessageController::class, 'store'])->middleware('throttle:public-form');

// Visitors (public POST)
Route::post('/visitors', [VisitorController::class, 'store'])->middleware('throttle:public-form');

// CVS Map (7-Eleven store search)
Route::post('/cvs-map/seven-eleven', [CvsMapController::class, 'search']);
Route::get('/cvs-map/seven-eleven', [CvsMapController::class, 'query']);

// Attendance PIN verification (used by timeclock — requires auth)
Route::middleware('auth:sanctum')->post('/attendance/verify-pin', [AttendanceController::class, 'verifyPin']);

// =====================================================
// AUTHENTICATED ROUTES
// =====================================================

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'profile']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // =====================================================
    // INTERNAL ROUTES (auth + non-customer role)
    // =====================================================
    Route::middleware('role:admin,manager,staff')->group(function () {

        // Products (write operations)
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        // Categories (write operations)
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Customers
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('customers.price-list', CustomerPriceListController::class)
            ->shallow()->parameters(['price-list' => 'customerPriceList']);

        // Suppliers
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('suppliers.price-list', SupplierPriceListController::class)
            ->shallow()->parameters(['price-list' => 'supplierPriceList']);

        // Departments & Employees
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('employees', EmployeeController::class);

        // Contact Messages (read/update/delete)
        Route::get('/contact/messages', [ContactMessageController::class, 'index']);
        Route::get('/contact/messages/{id}', [ContactMessageController::class, 'show']);
        Route::put('/contact/messages/{id}', [ContactMessageController::class, 'update']);
        Route::delete('/contact/messages/{id}', [ContactMessageController::class, 'destroy']);

        // Visitors (read)
        Route::get('/visitors', [VisitorController::class, 'index']);

        // Site Settings
        Route::get('/settings/site', [SiteSettingController::class, 'index']);
        Route::put('/settings/site', [SiteSettingController::class, 'update']);

        // Payment Methods
        Route::apiResource('settings/payment-methods', PaymentMethodController::class)
            ->parameters(['payment-methods' => 'paymentMethod']);

        // Sales Orders
        Route::apiResource('sales-orders', SalesOrderController::class);
        Route::post('/sales-orders/{id}/ship', [SalesOrderActionController::class, 'ship']);
        Route::post('/sales-orders/{id}/return', [SalesOrderActionController::class, 'returnOrder']);
        Route::post('/sales-orders/{id}/refund', [SalesOrderActionController::class, 'refund']);

        // Purchase Orders
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
        Route::post('/purchase-orders/{id}/receive', [PurchaseOrderActionController::class, 'receive']);
        Route::post('/purchase-orders/{id}/return', [PurchaseOrderActionController::class, 'returnOrder']);
        Route::post('/purchase-orders/{id}/refund', [PurchaseOrderActionController::class, 'refund']);

        // Manufacturing
        Route::apiResource('boms', BomController::class);
        Route::apiResource('work-orders', WorkOrderController::class);
        Route::post('/work-orders/{id}/start', [WorkOrderActionController::class, 'start']);
        Route::post('/work-orders/{id}/complete', [WorkOrderActionController::class, 'complete']);
        Route::post('/work-orders/{id}/cancel', [WorkOrderActionController::class, 'cancel']);

        // Inventory
        Route::apiResource('inventory/batches', ProductBatchController::class)
            ->parameters(['batches' => 'batch']);
        Route::apiResource('inventory/movements', InventoryMovementController::class)
            ->parameters(['movements' => 'movement']);
        Route::apiResource('inventory/stock-counts', StockCountController::class)
            ->parameters(['stock-counts' => 'stockCount']);
        Route::post('/inventory/adjust', [InventoryAdjustController::class, 'adjust']);

        // Accounting
        Route::apiResource('accounting/accounts', ChartOfAccountController::class)
            ->parameters(['accounts' => 'account']);
        Route::apiResource('accounting/journal-entries', JournalEntryController::class)
            ->parameters(['journal-entries' => 'journalEntry']);
        Route::apiResource('accounting/vouchers', AccountingVoucherController::class)
            ->parameters(['vouchers' => 'voucher']);
        Route::apiResource('accounting/periods', AccountingPeriodController::class)
            ->parameters(['periods' => 'period']);
        Route::apiResource('accounting/receivables', AccountsReceivableController::class)
            ->parameters(['receivables' => 'receivable']);
        Route::apiResource('accounting/payables', AccountsPayableController::class)
            ->parameters(['payables' => 'payable']);

        // Attendance
        Route::apiResource('attendance/records', AttendanceRecordController::class)
            ->parameters(['records' => 'record']);
        Route::apiResource('attendance/shifts', ShiftTypeController::class)
            ->parameters(['shifts' => 'shift']);
        Route::apiResource('attendance/leave-types', LeaveTypeController::class)
            ->parameters(['leave-types' => 'leaveType']);
        Route::apiResource('attendance/leave-applications', LeaveApplicationController::class)
            ->parameters(['leave-applications' => 'leaveApplication']);
    });

    // =====================================================
    // ADMIN ONLY ROUTES
    // =====================================================
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
