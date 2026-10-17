{{--
    Cancel an order.

    Shared by the customer screen and the admin/manager action bar — the only
    difference is the action URL, which the caller passes in.

    Expects: $action (string), $order
--}}
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form action="{{ $action }}" method="POST" class="m-0">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p>
                        Are you sure you want to cancel
                        <strong>{{ $order->order_number }}</strong> — {{ $order->design_name }}?
                    </p>

                    <div class="oms-review">
                        <dl class="oms-detail mb-0">
                            <dt>Order</dt>
                            <dd>{{ $order->order_number }}</dd>

                            <dt>Design</dt>
                            <dd>{{ $order->design_name }}</dd>

                            <dt>Current Status</dt>
                            <dd>{{ $order->status->label() }}</dd>
                        </dl>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="cancel_reason">Reason</label>
                        <textarea class="form-control @error('reason') is-invalid @enderror"
                                  id="cancel_reason" name="reason" rows="3" maxlength="500"
                                  placeholder="Why is this order being cancelled?">{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Optional, but it is what the order history will show later.</div>
                    </div>

                    <p class="text-muted small mt-3 mb-0">
                        Cancelling is final — a cancelled order cannot be reopened, and any
                        work still assigned on it will be stood down. The order stays in
                        your list, marked as cancelled.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Keep Order</button>
                    <button type="submit" class="btn btn-danger">Cancel Order</button>
                </div>
            </form>

        </div>
    </div>
</div>
