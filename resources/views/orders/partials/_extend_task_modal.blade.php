{{--
    Extend a task deadline.

    Its own dialog on purpose. The original markup reused #assignTaskModal for
    this, which posted employee_id / task_type / order_id for what is really a
    duration change — and carried no task id at all, so the backend had nothing
    to key on. The action URL is set per-row from the trigger's data-action.
--}}
<div class="modal fade" id="extendTaskModal" tabindex="-1" aria-labelledby="extendTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="extendTaskModalLabel">Extend Time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="#" method="POST" id="extendTaskForm">
                @csrf
                @method('PATCH')

                <div class="modal-body">
                    <div class="oms-review mb-3">
                        <div class="oms-meter__label">Task</div>
                        <div class="fw-semibold" data-extend-task>—</div>
                        <div class="oms-meter__label mt-2">Currently allowed</div>
                        <div class="fw-semibold"><span data-extend-allowed>—</span> hours</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="extend_hours">Additional Hours <span class="oms-req">*</span></label>
                        <select class="form-select" id="extend_hours" name="additional_hours" required>
                            @foreach (config('orders.task_durations') as $hours)
                                <option value="{{ $hours }}" @selected($hours === 1)>+{{ $hours }} hour{{ $hours === 1 ? '' : 's' }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            The deadline moves out; the original allowance stays on the record.
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="extend_reason">Reason</label>
                        <textarea class="form-control" id="extend_reason" name="reason" rows="3"
                                  placeholder="Why does this task need more time?"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Extend Deadline</button>
                </div>
            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('extendTaskModal');
        if (!modal) return;

        var form = modal.querySelector('#extendTaskForm');

        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;

            // Route binding carries the task id — no hidden field to mismatch.
            form.action = trigger.getAttribute('data-action') || '#';

            modal.querySelector('[data-extend-task]').textContent =
                (trigger.getAttribute('data-order') || '—') + ' — ' + (trigger.getAttribute('data-design') || '');
            modal.querySelector('[data-extend-allowed]').textContent =
                trigger.getAttribute('data-allowed') || '—';
        });
    })();
</script>
@endpush
