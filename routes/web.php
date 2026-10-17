<?php

use App\Http\Controllers\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\Admin\EmployeeController as AdminEmployeeController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
/*
 | Four controllers share the name OrderController, so every import is
 | aliased. Without this PHP fatals with "Cannot use ... as OrderController
 | because the name is already in use".
 */
use App\Http\Controllers\Admin\QuoteController as AdminQuoteController;
use App\Http\Controllers\Admin\RevisionController as AdminRevisionController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\EmailPreferencesController;
use App\Http\Controllers\Employee\OrderController as EmployeeOrderController;
use App\Http\Controllers\Employee\PerformanceController;
use App\Http\Controllers\Employee\TaskController as EmployeeTaskController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceDownloadController;
use App\Http\Controllers\Manager\EmployeeController as ManagerEmployeeController;
use App\Http\Controllers\Manager\OrderController as ManagerOrderController;
use App\Http\Controllers\Manager\QuoteController as ManagerQuoteController;
use App\Http\Controllers\Manager\RevisionController as ManagerRevisionController;
use App\Http\Controllers\Manager\TaskController as ManagerTaskController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\BillingController as UserBillingController;
use App\Http\Controllers\User\OrderController as UserOrderController;
use App\Http\Controllers\User\QuoteController as UserQuoteController;
use App\Http\Controllers\User\RevisionController as UserRevisionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Auth::routes();

/*------------------------------------------
--------------------------------------------
Shared authenticated routes
--------------------------------------------
--------------------------------------------*/

/*
 | Deliberately outside every role group. `user-access` matches a single role,
 | so a file route inside one group would be unreachable by the other three.
 | Authorization is per-record via OrderFilePolicy instead.
 */
Route::middleware('auth')->group(function () {
    Route::get('/files/{orderFile}/download', FileDownloadController::class)->name('files.download');

    /*
     | Invoice PDF. Shared for the same reason as the file download above, and
     | guarded per-record by InvoicePolicy@view.
     */
    Route::get('/invoices/{invoice}/download', InvoiceDownloadController::class)->name('invoices.download');
});

/*------------------------------------------
--------------------------------------------
All Normal Users Routes List
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:user'])->prefix('user')->name('user.')->group(function () {

    Route::get('/dashboard', [HomeController::class, 'userDashboard'])->name('dashboard');

    /* Orders — names unchanged; the sidebar and breadcrumbs depend on them. */
    Route::get('/orders', [UserOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/digitizing/create', [UserOrderController::class, 'digitizing'])->name('orders.digitizing');
    Route::get('/orders/vector/create', [UserOrderController::class, 'vector'])->name('orders.vector');
    Route::get('/orders/patch/create', [UserOrderController::class, 'patch'])->name('orders.patch');
    Route::post('/orders/digitizing', [UserOrderController::class, 'storeDigitizing'])->name('orders.digitizing.store');
    Route::post('/orders/vector', [UserOrderController::class, 'storeVector'])->name('orders.vector.store');
    Route::post('/orders/patch', [UserOrderController::class, 'storePatch'])->name('orders.patch.store');
    /* {order} last, so it cannot swallow /orders/digitizing/create. */
    Route::get('/orders/{order}', [UserOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/edit', [UserOrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [UserOrderController::class, 'update'])->name('orders.update');
    /* Cancel — OrderPolicy@cancel limits the customer to Pending orders. */
    Route::post('/orders/{order}/cancel', [UserOrderController::class, 'cancel'])->name('orders.cancel');

    /* Quotes — a priced enquiry that becomes an order once accepted. */
    Route::get('/quotes', [UserQuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/digitizing/create', [UserQuoteController::class, 'digitizing'])->name('quotes.digitizing');
    Route::get('/quotes/vector/create', [UserQuoteController::class, 'vector'])->name('quotes.vector');
    Route::get('/quotes/patch/create', [UserQuoteController::class, 'patch'])->name('quotes.patch');
    Route::post('/quotes/digitizing', [UserQuoteController::class, 'storeDigitizing'])->name('quotes.digitizing.store');
    Route::post('/quotes/vector', [UserQuoteController::class, 'storeVector'])->name('quotes.vector.store');
    Route::post('/quotes/patch', [UserQuoteController::class, 'storePatch'])->name('quotes.patch.store');
    /* {quote} last, so it cannot swallow /quotes/digitizing/create. */
    Route::get('/quotes/{quote}', [UserQuoteController::class, 'show'])->name('quotes.show');
    Route::post('/quotes/{quote}/approve', [UserQuoteController::class, 'approve'])->name('quotes.approve');
    Route::post('/quotes/{quote}/reject', [UserQuoteController::class, 'reject'])->name('quotes.reject');

    /* Raising a revision, billing & settings. There is no revisions list:
       a revision is raised and read on its order's own page. */
    Route::post('/revisions', [UserRevisionController::class, 'store'])->name('revisions.store');
    Route::get('/billing', [UserBillingController::class, 'index'])->name('billing');
    Route::get('/billing/{invoice}', [UserBillingController::class, 'show'])->name('billing.show');
    Route::get('/settings/email', [EmailPreferencesController::class, 'edit'])->name('settings.email');
    Route::put('/settings/email', [EmailPreferencesController::class, 'update'])->name('settings.email.update');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('settings.password.update');
});

/*------------------------------------------
--------------------------------------------
All Admin Routes List
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [HomeController::class, 'adminDashboard'])->name('dashboard');

    /* Orders — names unchanged; the sidebar and breadcrumbs depend on them. */
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/digitizing/create', [AdminOrderController::class, 'digitizing'])->name('orders.digitizing');
    Route::get('/orders/vector/create', [AdminOrderController::class, 'vector'])->name('orders.vector');
    Route::get('/orders/patch/create', [AdminOrderController::class, 'patch'])->name('orders.patch');
    Route::post('/orders/digitizing', [AdminOrderController::class, 'storeDigitizing'])->name('orders.digitizing.store');
    Route::post('/orders/vector', [AdminOrderController::class, 'storeVector'])->name('orders.vector.store');
    Route::post('/orders/patch', [AdminOrderController::class, 'storePatch'])->name('orders.patch.store');
    /* {order} last, so it cannot swallow /orders/digitizing/create. */
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/edit', [AdminOrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    Route::patch('/orders/{order}/price', [AdminOrderController::class, 'updatePrice'])->name('orders.price');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    /* Final approval of the employee's submitted work. Deliberately NOT
       scopeBindings(): that resolves {file} through $order->files(), which a
       revision deliverable is not in — it hangs off the message it was handed
       in with. The controller checks the file belongs to the order instead. */
    Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/files/{file}/replace', [AdminOrderController::class, 'replaceFile'])
        ->name('orders.files.replace');
    /* Soft delete — admin only, per OrderPolicy@delete. Not offered to manager. */
    Route::delete('/orders/{order}', [AdminOrderController::class, 'destroy'])->name('orders.destroy');

    /* Quotes */
    Route::get('/quotes', [AdminQuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/digitizing/create', [AdminQuoteController::class, 'digitizing'])->name('quotes.digitizing');
    Route::get('/quotes/vector/create', [AdminQuoteController::class, 'vector'])->name('quotes.vector');
    Route::get('/quotes/patch/create', [AdminQuoteController::class, 'patch'])->name('quotes.patch');
    Route::post('/quotes/digitizing', [AdminQuoteController::class, 'storeDigitizing'])->name('quotes.digitizing.store');
    Route::post('/quotes/vector', [AdminQuoteController::class, 'storeVector'])->name('quotes.vector.store');
    Route::post('/quotes/patch', [AdminQuoteController::class, 'storePatch'])->name('quotes.patch.store');
    Route::get('/quotes/{quote}', [AdminQuoteController::class, 'show'])->name('quotes.show');
    Route::patch('/quotes/{quote}/price', [AdminQuoteController::class, 'price'])->name('quotes.price');
    Route::post('/quotes/{quote}/approve', [AdminQuoteController::class, 'approve'])->name('quotes.approve');
    Route::post('/quotes/{quote}/reject', [AdminQuoteController::class, 'reject'])->name('quotes.reject');

    /* User management */
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    /* {user} last, so it cannot swallow /users/create. */
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    /* Tasks */
    Route::get('/tasks', [AdminTaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [AdminTaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}/extend', [AdminTaskController::class, 'extend'])->name('tasks.extend');

    /* Employees & performance */
    Route::get('/employees', [AdminEmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/{employee}', [AdminEmployeeController::class, 'show'])->name('employees.show');

    /* Order revisions. No list page — they are raised, assigned and rejected
       from the order's own page, on its Revisions card. */
    Route::post('/revisions', [AdminRevisionController::class, 'store'])->name('revisions.store');
    Route::post('/revisions/{revision}/assign', [AdminRevisionController::class, 'assign'])->name('revisions.assign');
    Route::patch('/revisions/{revision}/reject', [AdminRevisionController::class, 'reject'])->name('revisions.reject');

    /* Billing & settings */
    Route::get('/billing', [AdminBillingController::class, 'index'])->name('billing');
    Route::post('/billing/generate', [AdminBillingController::class, 'generate'])->name('billing.generate');
    Route::get('/billing/{invoice}', [AdminBillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/{invoice}/payments', [AdminBillingController::class, 'recordPayment'])->name('billing.payments.store');
    Route::get('/settings/email', [EmailPreferencesController::class, 'edit'])->name('settings.email');
    Route::put('/settings/email', [EmailPreferencesController::class, 'update'])->name('settings.email.update');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('settings.password.update');
});

/*------------------------------------------
--------------------------------------------
All manager Routes List
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:manager'])->prefix('manager')->name('manager.')->group(function () {

    Route::get('/dashboard', [HomeController::class, 'managerDashboard'])->name('dashboard');

    /* Orders & quotes – no billing or payment screens for this role. */
    Route::get('/orders', [ManagerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [ManagerOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/edit', [ManagerOrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [ManagerOrderController::class, 'update'])->name('orders.update');
    Route::patch('/orders/{order}/price', [ManagerOrderController::class, 'updatePrice'])->name('orders.price');
    Route::patch('/orders/{order}/status', [ManagerOrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/cancel', [ManagerOrderController::class, 'cancel'])->name('orders.cancel');
    /* Final approval — same gate as admin; OrderPolicy@approve admits both.
       Unscoped {file} for the reason given on the admin route above. */
    Route::post('/orders/{order}/approve', [ManagerOrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/files/{file}/replace', [ManagerOrderController::class, 'replaceFile'])
        ->name('orders.files.replace');

    Route::get('/quotes', [ManagerQuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/{quote}', [ManagerQuoteController::class, 'show'])->name('quotes.show');
    Route::patch('/quotes/{quote}/price', [ManagerQuoteController::class, 'price'])->name('quotes.price');
    Route::post('/quotes/{quote}/approve', [ManagerQuoteController::class, 'approve'])->name('quotes.approve');
    Route::post('/quotes/{quote}/reject', [ManagerQuoteController::class, 'reject'])->name('quotes.reject');

    /* Tasks, employees & revisions */
    Route::get('/tasks', [ManagerTaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [ManagerTaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}/extend', [ManagerTaskController::class, 'extend'])->name('tasks.extend');

    Route::get('/employees', [ManagerEmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/{employee}', [ManagerEmployeeController::class, 'show'])->name('employees.show');

    Route::post('/revisions', [ManagerRevisionController::class, 'store'])->name('revisions.store');
    Route::post('/revisions/{revision}/assign', [ManagerRevisionController::class, 'assign'])->name('revisions.assign');
    Route::patch('/revisions/{revision}/reject', [ManagerRevisionController::class, 'reject'])->name('revisions.reject');

    Route::get('/settings/email', [EmailPreferencesController::class, 'edit'])->name('settings.email');
    Route::put('/settings/email', [EmailPreferencesController::class, 'update'])->name('settings.email.update');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('settings.password.update');
});

/*------------------------------------------
--------------------------------------------
All employee Routes List
--------------------------------------------
--------------------------------------------*/
Route::middleware(['auth', 'user-access:employee'])->prefix('employee')->name('employee.')->group(function () {

    Route::get('/dashboard', [HomeController::class, 'employeeDashboard'])->name('dashboard');

    Route::get('/tasks', [EmployeeTaskController::class, 'index'])->name('tasks.index');
    Route::patch('/tasks/{task}/start', [EmployeeTaskController::class, 'start'])->name('tasks.start');
    Route::post('/tasks/{task}/complete', [EmployeeTaskController::class, 'complete'])->name('tasks.complete');

    Route::get('/orders', [EmployeeOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [EmployeeOrderController::class, 'show'])->name('orders.show');

    Route::get('/settings/email', [EmailPreferencesController::class, 'edit'])->name('settings.email');
    Route::put('/settings/email', [EmailPreferencesController::class, 'update'])->name('settings.email.update');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('settings.password.update');
    Route::get('/performance', PerformanceController::class)->name('performance');
});
