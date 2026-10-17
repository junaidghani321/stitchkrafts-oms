{{--
    Request Revision — two-step wizard (fill in, review, submit).

    Fixes two bugs in the original markup:

      1. The order-index version posted `order_no` with a display string
         ("ADDO-442") while the revisions-index version posted `order_id` with
         a REVISION number ("REV-0031"). Both now post `order_id` = the real
         primary key, with the display number carried separately in
         data-order-no purely for the review step.

      2. Trigger buttons must supply data-order-id. The handler below reads it.

    Expects:
      $action           string       route to post to
      $revisableOrders  ?Collection  when present, renders a picker instead of
                                     taking the order from the trigger button
--}}
@php
    $revisableOrders = $revisableOrders ?? null;
@endphp

<div class="modal fade" id="revisionModal" tabindex="-1" aria-labelledby="revisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="revisionModalLabel">Request Revision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ $action }}" method="POST" enctype="multipart/form-data" id="revisionForm">
                @csrf

                <div class="modal-body">

                    {{-- Step 1 --}}
                    <div data-revision-step="form">

                        @if ($revisableOrders !== null)
                            <div class="mb-3">
                                <label class="form-label" for="revision_order">Order <span class="oms-req">*</span></label>
                                <select class="form-select" id="revision_order" name="order_id" required>
                                    <option value="" selected disabled>Select a completed order</option>
                                    @foreach ($revisableOrders as $option)
                                        {{-- Real primary key, not the display number. --}}
                                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="order_id" data-revision-order-input>

                            <div class="oms-review mb-3">
                                <div class="row g-2">
                                    <div class="col-12 col-md-6">
                                        <div class="oms-meter__label">Order</div>
                                        <div class="fw-semibold" data-revision-order>—</div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="oms-meter__label">Design</div>
                                        <div class="fw-semibold" data-revision-design>—</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="revision_reason">Reason <span class="oms-req">*</span></label>
                                <select class="form-select" id="revision_reason" name="reason" required>
                                    <option value="" selected disabled>Select</option>
                                    @foreach (App\Enums\RevisionReason::options() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="revision_priority">Priority</label>
                                <select class="form-select" id="revision_priority" name="priority">
                                    @foreach (App\Enums\Priority::options() as $value => $label)
                                        <option value="{{ $value }}" @selected($value === 'normal')>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label" for="revision_due">Required By</label>
                                <input type="date" class="form-control" id="revision_due" name="due_date">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="revision_comments">Comments / Instructions <span class="oms-req">*</span></label>
                                <textarea class="form-control" id="revision_comments" name="comments" rows="4" required
                                          placeholder="Describe exactly what needs to change in this order"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Revision Files</label>
                                <label class="oms-dropzone" for="revision_files">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span class="oms-dropzone__title">Click to upload revision files</span>
                                    <span class="oms-dropzone__hint">Artwork, reference images or marked-up screenshots — multiple files allowed</span>
                                </label>
                                <input type="file" class="d-none" id="revision_files" name="files[]" multiple>
                                <ul class="oms-filelist" data-revision-files hidden></ul>
                            </div>

                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div data-revision-step="review" hidden>
                        <p class="text-muted small">Please check the request below before submitting it.</p>

                        <div class="oms-review">
                            <dl class="oms-detail mb-0">
                                <dt>Order</dt>
                                <dd data-review="order">—</dd>

                                <dt>Reason</dt>
                                <dd data-review="reason">—</dd>

                                <dt>Priority</dt>
                                <dd data-review="priority">—</dd>

                                <dt>Required By</dt>
                                <dd data-review="due">—</dd>

                                <dt>Comments</dt>
                                <dd data-review="comments">—</dd>

                                <dt>Files</dt>
                                <dd data-review="files">No files attached</dd>
                            </dl>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-brand" data-revision-back hidden>Back</button>
                    <button type="button" class="btn btn-primary" data-revision-next>Review Request</button>
                    <button type="submit" class="btn btn-primary" data-revision-submit hidden>Submit Revision</button>
                </div>
            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('revisionModal');
        if (!modal) return;

        var form     = modal.querySelector('#revisionForm');
        var stepForm = modal.querySelector('[data-revision-step="form"]');
        var review   = modal.querySelector('[data-revision-step="review"]');
        var nextBtn  = modal.querySelector('[data-revision-next]');
        var backBtn  = modal.querySelector('[data-revision-back]');
        var sendBtn  = modal.querySelector('[data-revision-submit]');
        var files    = modal.querySelector('#revision_files');
        var fileList = modal.querySelector('[data-revision-files]');
        var picker   = modal.querySelector('#revision_order');

        function label(select) {
            return select && select.selectedIndex > -1 ? select.options[select.selectedIndex].text : '—';
        }

        function set(name, value) {
            var target = modal.querySelector('[data-review="' + name + '"]');
            if (target) target.textContent = value || '—';
        }

        function showStep(name) {
            var reviewing = name === 'review';

            stepForm.hidden = reviewing;
            review.hidden   = !reviewing;
            nextBtn.hidden  = reviewing;
            backBtn.hidden  = !reviewing;
            sendBtn.hidden  = !reviewing;
        }

        // Carry the triggering row's order into the modal. data-order-id is the
        // primary key; data-order is only the label.
        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;

            var input = modal.querySelector('[data-revision-order-input]');
            if (input) input.value = trigger.getAttribute('data-order-id') || '';

            var orderEl = modal.querySelector('[data-revision-order]');
            if (orderEl) orderEl.textContent = trigger.getAttribute('data-order') || '—';

            var designEl = modal.querySelector('[data-revision-design]');
            if (designEl) designEl.textContent = trigger.getAttribute('data-design') || '—';

            var customerEl = modal.querySelector('[data-revision-customer]');
            if (customerEl) customerEl.textContent = trigger.getAttribute('data-customer') || '—';
        });

        files.addEventListener('change', function () {
            fileList.innerHTML = '';
            fileList.hidden    = files.files.length === 0;

            Array.prototype.forEach.call(files.files, function (file) {
                var item = document.createElement('li');
                var size = (file.size / 1024).toFixed(0);

                item.innerHTML = '<i class="fa-regular fa-file"></i>';
                item.appendChild(document.createTextNode(file.name + ' (' + size + ' KB)'));
                fileList.appendChild(item);
            });
        });

        nextBtn.addEventListener('click', function () {
            if (!form.reportValidity()) return;

            var orderEl  = modal.querySelector('[data-revision-order]');
            var designEl = modal.querySelector('[data-revision-design]');

            set('order', picker
                ? label(picker)
                : (orderEl ? orderEl.textContent : '') + ' — ' + (designEl ? designEl.textContent : ''));

            set('reason',   label(modal.querySelector('#revision_reason')));
            set('priority', label(modal.querySelector('#revision_priority')));
            set('due',      modal.querySelector('#revision_due').value || 'Not specified');
            set('comments', modal.querySelector('#revision_comments').value);
            set('files',    files.files.length ? files.files.length + ' file(s) attached' : 'No files attached');

            showStep('review');
        });

        backBtn.addEventListener('click', function () { showStep('form'); });

        modal.addEventListener('hidden.bs.modal', function () { showStep('form'); });
    })();
</script>
@endpush
