{{--
    Assign Task.

    Two fixes over the original markup:

      1. employee_id options were $loop->iteration (1..5), a placeholder that
         had nothing to do with real user ids. They are real ids now.

      2. This modal used to double as the "Extend time" dialog, which posted
         employee_id/task_type/order_id for what is really a duration change,
         and carried no task id at all. Extending now has its own modal —
         see _extend_task_modal.blade.php.

      3. Task Type was a free choice, which let a vector order be assigned as
         a patch task. It is read off the order now — see the readout below
         and TaskAssignmentService::taskTypeFor().

    Expects:
      $action     string
      $employees  Collection<User>
      $orders     ?Collection  order picker, from OrderRowPresenter::selectOptions()
                               (needs `type_label`); omit when the order is
                               implied by the page (an order detail screen),
                               where the trigger carries data-type-label
      $durations  ?array       defaults to config('orders.task_durations')
--}}
@php
    $orders    = $orders    ?? null;
    $durations = $durations ?? config('orders.task_durations');
    // Set on the employee profile page, where the person is already implied.
    $lockedEmployee = $lockedEmployee ?? null;
@endphp

<div class="modal fade" id="assignTaskModal" tabindex="-1" aria-labelledby="assignTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="assignTaskModalLabel">Assign Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ $action }}" method="POST">
                @csrf

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="task_employee">Employee <span class="oms-req">*</span></label>
                            @if ($lockedEmployee !== null)
                                {{-- Implied by the page; not a choice here. --}}
                                <input type="hidden" name="employee_id" value="{{ $lockedEmployee['id'] }}">
                                <div class="form-control-plaintext fw-semibold">{{ $lockedEmployee['name'] }}</div>
                            @else
                                <select class="form-select" id="task_employee" name="employee_id" required>
                                    <option value="" selected disabled>Select employee</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="task_order">Order <span class="oms-req">*</span></label>
                            @if ($orders !== null)
                                <select class="form-select" id="task_order" name="order_id" required>
                                    <option value="" selected disabled>Select order</option>
                                    @foreach ($orders as $option)
                                        {{-- data-type-label feeds the Task Type readout below. --}}
                                        <option value="{{ $option['id'] }}" data-type-label="{{ $option['type_label'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            @else
                                {{-- Order implied by the page; filled from the trigger button. --}}
                                <input type="hidden" name="order_id" data-task-order-input>
                                <div class="form-control-plaintext fw-semibold" data-task-order>—</div>
                            @endif
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Task Type</label>
                            {{-- Not a choice any more: a digitizing order is
                                 digitizing work. The server reads the type off
                                 the order, so this only reports what will be
                                 assigned once the order above is known. --}}
                            <div class="form-control-plaintext fw-semibold" data-task-type>—</div>
                            <div class="form-text">Taken from the order's type.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="task_duration">Allowed Time <span class="oms-req">*</span></label>
                            <select class="form-select" id="task_duration" name="duration_hours" required>
                                @foreach ($durations as $hours)
                                    <option value="{{ $hours }}" @selected($hours === 3)>{{ $hours }} hour{{ $hours === 1 ? '' : 's' }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">The countdown starts as soon as the task is assigned.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="task_priority">Priority</label>
                            <select class="form-select" id="task_priority" name="priority">
                                @foreach (App\Enums\Priority::options() as $value => $label)
                                    <option value="{{ $value }}" @selected($value === 'normal')>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="task_instructions">Instructions</label>
                            <textarea class="form-control" id="task_instructions" name="instructions" rows="4"
                                      placeholder="Anything the employee needs to know before starting"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="task_notify" name="notify" value="1" checked>
                                <label class="form-check-label" for="task_notify">Notify the employee as soon as the task is assigned</label>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Task</button>
                </div>
            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('assignTaskModal');
        if (!modal) return;

        var typeReadout = modal.querySelector('[data-task-type]');
        var orderSelect = modal.querySelector('#task_order');

        function showType(label) {
            if (typeReadout) typeReadout.textContent = label || '—';
        }

        // Order picker: the task type follows whichever order is chosen.
        if (orderSelect) {
            orderSelect.addEventListener('change', function () {
                var option = orderSelect.options[orderSelect.selectedIndex];
                showType(option && option.getAttribute('data-type-label'));
            });
        }

        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;

            var input = modal.querySelector('[data-task-order-input]');
            if (input) input.value = trigger.getAttribute('data-order-id') || '';

            var label = modal.querySelector('[data-task-order]');
            if (label) {
                label.textContent = (trigger.getAttribute('data-order') || '—') +
                    ' — ' + (trigger.getAttribute('data-design') || '');
            }

            // No picker means the order is implied by the page, so the type
            // rides along on the trigger button instead.
            if (!orderSelect) showType(trigger.getAttribute('data-type-label'));
        });
    })();
</script>
@endpush
