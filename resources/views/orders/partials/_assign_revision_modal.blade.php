{{--
    Assign a revision to an employee.

    One modal serves every row: the trigger carries data-action (the per-revision
    route) plus the readout fields, and the handler below copies them in. Same
    pattern as #completeTaskModal on employee/tasks/index.blade.php.

    Deliberately a narrower form than _assign_task_modal: there is no order
    picker (the revision implies the order) and no task type (the server forces
    task_type = revision). The fields are exactly AssignRevisionRequest's rules.

    Expects:
      $employees  Collection<User>  active employees
      $durations  ?array            defaults to config('orders.task_durations')
--}}
@php
    $durations = $durations ?? config('orders.task_durations');
@endphp

<div class="modal fade" id="assignRevisionModal" tabindex="-1" aria-labelledby="assignRevisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="assignRevisionModalLabel">Assign Revision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- action is set from the trigger's data-action. --}}
            <form action="#" method="POST" id="assignRevisionForm">
                @csrf

                <div class="modal-body">

                    <div class="oms-review mb-3">
                        <div class="oms-meter__label">Revision</div>
                        <div class="fw-semibold mb-2" data-revision-no>—</div>
                        <div class="oms-meter__label">Order</div>
                        <div class="fw-semibold mb-2" data-revision-order>—</div>
                        <div class="oms-meter__label">What was asked for</div>
                        <div class="small" data-revision-comments>—</div>
                    </div>

                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="revision_employee">Employee <span class="oms-req">*</span></label>
                            <select class="form-select" id="revision_employee" name="employee_id" required>
                                <option value="" selected disabled>Select employee</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="revision_duration">Allowed Time <span class="oms-req">*</span></label>
                            <select class="form-select" id="revision_duration" name="duration_hours" required>
                                @foreach ($durations as $hours)
                                    <option value="{{ $hours }}" @selected($hours === 3)>{{ $hours }} hour{{ $hours === 1 ? '' : 's' }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">The countdown starts as soon as the revision is assigned.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="revision_priority">Priority</label>
                            <select class="form-select" id="revision_priority" name="priority">
                                @foreach (App\Enums\Priority::options() as $value => $label)
                                    <option value="{{ $value }}" data-revision-priority-option>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Defaults to the priority the revision was raised at.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="revision_instructions">Instructions</label>
                            <textarea class="form-control" id="revision_instructions" name="instructions" rows="4"
                                      placeholder="Anything the employee needs to know before starting"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="revision_notify" name="notify" value="1" checked>
                                <label class="form-check-label" for="revision_notify">Notify the employee as soon as the revision is assigned</label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Revision</button>
                </div>
            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('assignRevisionModal');
        if (!modal) return;

        var form = modal.querySelector('#assignRevisionForm');

        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;

            modal.querySelector('[data-revision-no]').textContent       = trigger.getAttribute('data-revision') || '—';
            modal.querySelector('[data-revision-order]').textContent     = trigger.getAttribute('data-order') || '—';
            modal.querySelector('[data-revision-comments]').textContent  = trigger.getAttribute('data-comments') || '—';

            // Start from the priority the customer raised it at, rather than
            // silently dropping an urgent revision to normal.
            var priority = trigger.getAttribute('data-priority');
            if (priority) {
                modal.querySelector('#revision_priority').value = priority;
            }

            // Route binding carries the revision id, so there is no hidden
            // field that could disagree with the URL.
            form.action = trigger.getAttribute('data-action') || '#';
        });
    })();
</script>
@endpush
