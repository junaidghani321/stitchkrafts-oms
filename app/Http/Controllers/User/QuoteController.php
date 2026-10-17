<?php

namespace App\Http\Controllers\User;

use App\Enums\OrderType;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Concerns\StoresQuotes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quotes\RejectQuoteRequest;
use App\Http\Requests\Quotes\User\StoreDigitizingQuoteRequest;
use App\Http\Requests\Quotes\User\StorePatchQuoteRequest;
use App\Http\Requests\Quotes\User\StoreVectorQuoteRequest;
use App\Models\Quote;
use App\Services\QuoteService;
use App\Support\OrderRowPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    use StoresQuotes;

    public function __construct(
        private readonly OrderRowPresenter $presenter,
        private readonly QuoteService $quotes,
    ) {}

    public function index(Request $request): View
    {
        $this->quotes->expireStale();

        $quotes = Quote::query()->forCustomer($request->user())->forIndex()->get();

        return view('user.quotes.index', [
            'quotes' => $this->presenter->quotesForCustomer($quotes),
            'statusLabels' => QuoteStatus::options(),
            'statusStyles' => QuoteStatus::styles(),
        ]);
    }

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

    private function form(OrderType $type): View
    {
        return view('user.quotes.'.$type->value, ['type' => $type]);
    }

    public function storeDigitizing(StoreDigitizingQuoteRequest $request): RedirectResponse
    {
        return $this->done($this->persistQuote($request, OrderType::Digitizing));
    }

    public function storeVector(StoreVectorQuoteRequest $request): RedirectResponse
    {
        return $this->done($this->persistQuote($request, OrderType::Vector));
    }

    public function storePatch(StorePatchQuoteRequest $request): RedirectResponse
    {
        return $this->done($this->persistQuote($request, OrderType::Patch));
    }

    private function done(Quote $quote): RedirectResponse
    {
        return redirect()
            ->route('user.quotes.index')
            ->with('status', "Quote {$quote->quote_number} has been requested — we will price it shortly.");
    }

    public function show(Quote $quote): View
    {
        $this->authorize('view', $quote);

        return view('user.quotes.show', [
            'quote' => $this->loadQuoteDetail($quote),
            'role' => 'user',
            'canOwn' => false,
        ]);
    }

    /**
     * The customer accepts the price — this is the moment a quote becomes real
     * work. The policy requires status = quoted, so an unpriced quote cannot
     * be accepted.
     */
    public function approve(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorize('decide', $quote);

        $order = $this->quotes->approve($quote, $request->user());

        return redirect()
            ->route('user.orders.show', $order)
            ->with('status', "Quote accepted — your order {$order->order_number} has been placed.");
    }

    public function reject(RejectQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->authorize('decide', $quote);

        $this->quotes->reject($quote, $request->validated('rejection_reason'), $request->user());

        return redirect()
            ->route('user.quotes.index')
            ->with('status', "Quote {$quote->quote_number} was declined.");
    }
}
