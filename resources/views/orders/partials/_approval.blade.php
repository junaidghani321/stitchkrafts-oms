{{--
    Final approval — admin and manager only.

    An employee finishing the last task on an order parks it at
    PendingApproval and tells nobody but the floor. Here the approver checks
    what was handed in, swaps any file for their own version, and signs it off.
    Approving is what sends the customer "your files are ready".

    Renders nothing unless the order is actually waiting on approval — that is
    the whole of OrderPolicy@approve, so there is no second rule to keep in
    step here.

    Expects: $order (with deliverables + revisionDeliverables + tasks.employee), $role
--}}
@can('approve', $order)
    @php
        /* Who handed it in: the most recently completed task on the order. */
        $submission = $order->tasks
            ->filter(fn ($task) => $task->completed_at !== null)
            ->sortByDesc('completed_at')
            ->first();

        /* Both routes work arrives by, and only what is still unsigned: a
           second round leaves the previous round's files on the order, and
           those are the customer's already. See
           Order::deliverablesAwaitingApproval(). */
        $submitted = $order->deliverablesAwaitingApproval();
    @endphp

    <div class="card mb-3">
        <div class="card-header">
            <h2 class="h6 mb-0">Final Approval</h2>
            <span class="oms-badge oms-badge--progress">{{ $order->status->label() }}</span>
        </div>

        <div class="card-body p-3 p-md-4">

            <div class="oms-review mb-3">
                <dl class="oms-detail mb-0">
                    <dt>Submitted by</dt>
                    <dd>{{ $submission?->employee?->name ?? '—' }}</dd>

                    <dt>Submitted</dt>
                    <dd>{{ $submission?->completed_at?->format('d-m-Y H:i') ?? '—' }}</dd>

                    @if ($submission?->completion_note)
                        <dt>Note</dt>
                        <dd>{{ $submission->completion_note }}</dd>
                    @endif
                </dl>
            </div>

            {{-- What the customer will get, unless it is replaced below. --}}
            <h3 class="h6 mb-2">Submitted Files</h3>

            @if ($submitted->isEmpty())
                <p class="text-muted">
                    Nothing was attached to the finished task. Approving will complete the
                    order with no new files for the customer to download.
                </p>
            @else
                <ul class="oms-filelist mb-3">
                    @foreach ($submitted as $file)
                        <li class="flex-wrap">
                            <i class="fa-regular fa-file"></i>
                            <a href="{{ route('files.download', $file) }}">{{ $file->original_name }}</a>
                            <span class="oms-meter__label">{{ $file->human_size }}</span>
                            @if ($file->category === App\Models\OrderFile::CATEGORY_REVISION_DELIVERABLE)
                                <span class="oms-badge oms-badge--pending">Submitted in thread</span>
                            @endif

                            {{-- Its own form: a file input cannot sit inside the
                                 approve form below, and each replacement names
                                 the one file it supersedes. --}}
                            <form action="{{ route($role.'.orders.files.replace', [$order, $file]) }}"
                                  method="POST" enctype="multipart/form-data"
                                  class="d-flex align-items-center gap-2 ms-auto m-0">
                                @csrf
                                <label class="visually-hidden" for="replace_{{ $file->id }}">
                                    Replacement for {{ $file->original_name }}
                                </label>
                                <input type="file" class="form-control form-control-sm" style="max-width: 15rem"
                                       id="replace_{{ $file->id }}" name="file" required>
                                <button type="submit" class="btn btn-brand btn-sm">Replace</button>
                            </form>
                        </li>
                    @endforeach
                </ul>

                @error('file')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror
            @endif

            <form action="{{ route($role.'.orders.approve', $order) }}" method="POST" class="m-0">
                @csrf

                <label class="form-label" for="approval_note">Approval Note</label>
                <textarea class="form-control @error('note') is-invalid @enderror"
                          id="approval_note" name="note" rows="2" maxlength="500"
                          placeholder="Anything worth recording about this sign-off">{{ old('note') }}</textarea>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Optional. It is kept on the order history.</div>

                <div class="oms-form-actions mt-3">
                    <button type="submit" class="btn btn-primary">Approve &amp; Complete Order</button>
                </div>

                <p class="text-muted small mb-0 mt-2">
                    Approving completes the order and emails the customer that their files are
                    ready. Not good enough? Move the order back to <strong>In Progress</strong> or
                    <strong>In Revision</strong> with the status buttons below and assign the
                    rework.
                </p>
            </form>

        </div>
    </div>
@endcan
