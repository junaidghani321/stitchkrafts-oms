<?php

namespace App\Http\Controllers\Staff;

use App\Enums\OrderType;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Concerns\StoresQuotes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quotes\PriceQuoteRequest;
use App\Http\Requests\Quotes\RejectQuoteRequest;
use App\Http\Requests\Quotes\Staff\StoreDigitizingQuoteRequest;
use App\Http\Requests\Quotes\Staff\StorePatchQuoteRequest;
use App\Http\Requests\Quotes\Staff\StoreVectorQuoteRequest;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteService;
use App\Support\OrderRowPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** admin/quotes and manager/quotes are byte-identical apart from breadcrumbs. */
abstract class StaffQuoteController extends Controller
{
    use StoresQuotes;

    public function __construct(
        protected readonly OrderRowPresenter $presenter,
        protected readonly QuoteService $quotes,
    ) {}

    abstract protected function role(): string;

    protected function view(string $name): string
    {
        return $this->role().'.quotes.'.$name;
    }

    public function index(): View
    {
        // Cheap sweep on read: there is no guaranteed scheduler, and a quote
        // that is past its validity date but still shows as live is worse than
        // one extra UPDATE per listing.
        $this->quotes->expireStale();

        $quotes = Quote::query()->with('customer:id,name,type')->forIndex()->get();

        return view($this->view('index'), [
            'quotes' => $this->presenter->quotesForStaff($quotes),
            'statusLabels' => QuoteStatus::options(),
            'statusStyles' => QuoteStatus::styles(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function digitizing(): View
    {
        return $this->form(OrderType::Digitizing);
    }

    public function vector(): View
    {
        return $this->form(OrderType::Vector);
    }

    public function patch(): View
    {
        return $this->form(OrderType::Patch);
    }

    protected function form(OrderType $type): View
    {
        return view($this->view($type->value), [
            'type' => $type,
            'customers' => User::customers()->active()->orderBy('name')->get(['id', 'name', 'company']),
        ]);
    }

    public function storeDigitizing(StoreDigitizingQuoteRequest $request): RedirectResponse
    {
        return $this->done($this->persistQuote($request, OrderType::Digitizing, (int) $request->validated('customer_id')));
    }

    public function storeVector(StoreVectorQuoteRequest $request): RedirectResponse
    {
        return $this->done($this->persistQuote($request, OrderType::Vector, (int) $request->validated('customer_id')));
    }

    public function storePatch(StorePatchQuoteRequest $request): RedirectResponse
    {
        return $this->done($this->persistQuote($request, OrderType::Patch, (int) $request->validated('customer_id')));
    }

    private function done(Quote $quote): RedirectResponse
    {
        return redirect()
            ->route($this->role().'.quotes.index')
            ->with('status', "Quote {$quote->quote_number} has been created.");
    }

    /*
    |--------------------------------------------------------------------------
    | Show, price, decide
    |--------------------------------------------------------------------------
    */

    public function show(Quote $quote): View
    {
        $this->authorize('view', $quote);

        return view($this->view('show'), [
            'quote' => $this->loadQuoteDetail($quote),
            'role' => $this->role(),
            'canOwn' => request()->user()->can('price', $quote),
        ]);
    }

    /** pending -> quoted. */
    public function price(PriceQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->authorize('price', $quote);

        $this->quotes->price(
            $quote,
            (float) $request->validated('price'),
            $request->validated('valid_until'),
            $request->user(),
        );

        return back()->with('status', "Quote {$quote->quote_number} has been priced.");
    }

    /** Accepting on the customer's behalf — this is how phone orders land. */
    public function approve(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorize('decide', $quote);

        $order = $this->quotes->approve($quote, $request->user());

        return redirect()
            ->route($this->role().'.orders.show', $order)
            ->with('status', "Quote {$quote->quote_number} accepted — order {$order->order_number} created.");
    }

    public function reject(RejectQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->authorize('decide', $quote);

        $this->quotes->reject($quote, $request->validated('rejection_reason'), $request->user());

        return back()->with('status', "Quote {$quote->quote_number} was declined.");
    }
}
