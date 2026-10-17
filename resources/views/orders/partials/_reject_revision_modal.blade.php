{{--
    Turn a revision down.

    One modal serves every row; the trigger carries data-action. Note the
    @method('PATCH') — the reject route is PATCH, not POST.

    The reason is not just a status: RevisionService posts it into the order
    thread as a reply parented to the revision, so the customer reads why in
    the same place they asked.

    Expects: nothing — the trigger supplies everything.
--}}
<div class="modal fade" id="rejectRevisionModal" tabindex="-1" aria-labelledby="rejectRevisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="rejectRevisionModalLabel">Reject Revision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- action is set from the trigger's data-action. --}}
            <form action="#" method="POST" id="rejectRevisionForm">
                @csrf
                @method('PATCH')

                <div class="modal-body">

                    <div class="oms-review mb-3">
                        <div class="oms-meter__label">Revision</div>
                        <div class="fw-semibold mb-2" data-revision-no>—</div>
                        <div class="oms-meter__label">Order</div>
                        <div class="fw-semibold mb-2" data-revision-order>—</div>
                        <div class="oms-meter__label">What was asked for</div>
                        <div class="small" data-revision-comments>—</div>
                    </div>

                    <div>
                        <label class="form-label" for="rejection_reason">Reason <span class="oms-req">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required
                                  maxlength="2000"
                                  placeholder="Why this change will not be made"></textarea>
                        <div class="form-text">
                            Kept on the revision record. The customer is told the revision was not
                            accepted, without this wording.
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reject Revision</button>
                </div>
            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('rejectRevisionModal');
        if (!modal) return;

        var form = modal.querySelector('#rejectRevisionForm');

        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;

            modal.querySelector('[data-revision-no]').textContent      = trigger.getAttribute('data-revision') || '—';
            modal.querySelector('[data-revision-order]').textContent    = trigger.getAttribute('data-order') || '—';
            modal.querySelector('[data-revision-comments]').textContent = trigger.getAttribute('data-comments') || '—';

            form.action = trigger.getAttribute('data-action') || '#';
            modal.querySelector('#rejection_reason').value = '';
        });
    })();
</script>
@endpush
