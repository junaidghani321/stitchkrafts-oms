{{--
    Delete an order.

    The action URL is set per-row from the trigger's data-action, so there is
    no hidden id to get out of step with the row that opened the dialog.

    This is a soft delete: the order, its artwork, tasks and revisions all stay
    in the database and can be restored. The copy below says so, because
    "Delete" with no qualifier reads as permanent.
--}}
<div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="deleteOrderModalLabel">Delete Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p>
                    Are you sure you want to delete
                    <strong data-delete-order>this order</strong><span data-delete-design></span>?
                </p>

                <div class="oms-review">
                    <dl class="oms-detail mb-0">
                        <dt>Order</dt>
                        <dd data-delete-order-no>—</dd>

                        <dt>Customer</dt>
                        <dd data-delete-customer>—</dd>
                    </dl>
                </div>

                <p class="text-muted small mt-3 mb-0">
                    The order is removed from the listings but is not erased — its
                    artwork, tasks, revisions and history are kept and it can be
                    restored if this was a mistake.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Cancel</button>

                <form action="#" method="POST" class="m-0" id="deleteOrderForm">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Order</button>
                </form>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('deleteOrderModal');
        if (!modal) return;

        var form = modal.querySelector('#deleteOrderForm');

        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;

            var order    = trigger.getAttribute('data-order') || 'this order';
            var design   = trigger.getAttribute('data-design') || '';
            var customer = trigger.getAttribute('data-customer') || '—';

            // Route binding carries the order id.
            form.action = trigger.getAttribute('data-action') || '#';

            modal.querySelector('[data-delete-order]').textContent    = order;
            modal.querySelector('[data-delete-design]').textContent   = design ? ' — ' + design : '';
            modal.querySelector('[data-delete-order-no]').textContent = order;
            modal.querySelector('[data-delete-customer]').textContent = customer;
        });
    })();
</script>
@endpush
