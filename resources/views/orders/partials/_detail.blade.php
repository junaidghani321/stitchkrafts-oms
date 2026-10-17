{{--
    Shared order detail body.

    Expects:
      $order     Order    eager-loaded with customer, artwork, deliverables,
                          tasks.employee, activities.user, and messages as
                          this viewer may read them (see
                          OrderMessage::scopeVisibleTo)
      $role      string   'user' | 'admin' | 'manager' | 'employee'
      $canPrice  bool     show the pricing panel
--}}
@php
    $role     = $role     ?? 'user';
    $canPrice = $canPrice ?? false;
    $isStaff  = in_array($role, ['admin', 'manager'], true);

    /* Revisions are kind = revision messages, so filter the thread we already
       loaded rather than querying for them again. */
    $revisions = $order->messages->where('kind', App\Enums\MessageKind::Revision);

    /* Everything this order has produced, by either route it can arrive by:
       the deliverables attached to the order, plus the revision output that
       hangs off the reply it was handed in with.

       Read out of the loaded thread rather than through the order's own
       revisionDeliverables relation, because the thread is already filtered
       to what this viewer may read — an employee must not meet the output of
       a revision that was never assigned to them. */
    $delivered = $order->deliverables
        ->concat($order->messages->flatMap->deliverables)
        ->sortBy('created_at')
        ->values();

    /* Only the fields that apply to this order's type, in form order. */
    $specs = collect(match ($order->type) {
        App\Enums\OrderType::Digitizing => [
            'Size'            => $order->height && $order->width ? $order->height.' x '.$order->width.' '.($order->unit ?? '') : null,
            'Number of Colors'=> $order->colors,
            'Fabric'          => $order->fabric,
            'Placement'       => config('orders.placements')[$order->placement] ?? null,
            'Required Format' => config('orders.formats.digitizing')[$order->required_format] ?? null,
            'Time Frame'      => $order->time_frame?->label(),
            '3D Puff'         => $order->puff_3d === null ? null : ($order->puff_3d ? 'Yes' : 'No'),
            'Applique'        => $order->applique === null ? null : ($order->applique ? 'Yes' : 'No'),
            'Trims'           => $order->trims === null ? null : ($order->trims ? 'Yes' : 'No'),
            'Patch Cost'      => $order->patch_cost === null ? null : ($order->patch_cost ? 'Yes' : 'No'),
        ],
        App\Enums\OrderType::Vector => [
            'Required Format'  => config('orders.formats.vector')[$order->required_format] ?? null,
            'Color Type'       => config('orders.color_types')[$order->color_type] ?? null,
            'Printing Process' => config('orders.printing_processes')[$order->printing_process] ?? null,
            'Time Frame'       => $order->time_frame?->label(),
        ],
        App\Enums\OrderType::Patch => [
            'Size'               => $order->height && $order->width ? $order->height.' x '.$order->width.' inches' : null,
            'Quantity'           => $order->quantity,
            'Colors'             => $order->colors,
            'Patches Type'       => config('orders.patch_types')[$order->patch_type] ?? null,
            'Patches Background' => config('orders.patch_backgrounds')[$order->patch_background] ?? null,
            'Backing Type'       => config('orders.backing_types')[$order->backing_type] ?? null,
            'Patches Border'     => config('orders.patch_borders')[$order->patch_border] ?? null,
            'Expected Delivery'  => $order->expected_delivery?->label(),
        ],
    })->filter(fn ($value) => $value !== null && $value !== '');
@endphp

<div class="row g-3">

    {{-- Summary --}}
    <div class="col-12 col-xl-8">
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="h6 mb-0">{{ $order->order_number }} — {{ $order->design_name }}</h2>
                <span class="oms-badge oms-badge--{{ $order->status->badge() }}">
                    {{ $role === 'user' ? $order->status->customerLabel() : $order->status->label() }}
                </span>
            </div>
            <div class="card-body p-3 p-md-4">
                <dl class="oms-detail mb-0">
                    <dt>Type</dt>
                    <dd>{{ $order->type->label() }}</dd>

                    {{-- Staff only. A customer knows who they are, and an
                         employee works the brief without needing to know whose
                         it is. --}}
                    @if ($isStaff)
                        <dt>Customer</dt>
                        <dd>{{ $order->customer?->name ?? '—' }}</dd>
                    @endif

                    <dt>Order Date</dt>
                    <dd>{{ $order->created_at->format('d-m-Y H:i') }}</dd>

                    <dt>Due</dt>
                    <dd>{{ $order->due_at?->format('d-m-Y H:i') ?? '—' }}</dd>

                    {{-- Hidden from employees: they work the brief, not the
                         money. The customer sees their own price, and staff
                         see everything. --}}
                    @unless ($role === 'employee')
                        <dt>Price</dt>
                        <dd>
                            <span class="oms-price {{ $order->isPriced() ? '' : 'oms-price--zero' }}">
                                $ {{ number_format($order->display_price, 2) }}
                            </span>
                            @unless ($order->isPriced())
                                <small class="text-muted">not yet priced</small>
                            @endunless
                        </dd>
                    @endunless

                    @if ($isStaff)
                        <dt>Payment</dt>
                        <dd><span class="oms-badge oms-badge--{{ $order->payment_status->badge() }}">{{ $order->payment_status->label() }}</span></dd>
                    @endif

                    <dt>Delivery</dt>
                    <dd><span class="oms-badge oms-badge--{{ $order->delivery_status->badge() }}">{{ $order->delivery_status->label() }}</span></dd>

                    @if ($isStaff && $order->approved_at)
                        <dt>Approved</dt>
                        <dd>
                            {{ $order->approved_at->format('d-m-Y H:i') }}
                            @if ($order->approvedBy)
                                <span class="text-muted">by {{ $order->approvedBy->name }}</span>
                            @endif
                            @if ($order->approval_note)
                                <div class="oms-meter__label">{{ $order->approval_note }}</div>
                            @endif
                        </dd>
                    @endif

                    @if ($order->instructions)
                        <dt>Instructions</dt>
                        <dd>{{ $order->instructions }}</dd>
                    @endif

                    @if ($order->isCancelled())
                        <dt>Cancelled</dt>
                        <dd>
                            {{ $order->cancelled_at?->format('d-m-Y H:i') ?? '—' }}
                            @if ($isStaff && $order->cancelledBy)
                                <span class="text-muted">by {{ $order->cancelledBy->name }}</span>
                            @endif
                        </dd>

                        @if ($order->cancellation_reason)
                            <dt>Reason</dt>
                            <dd>{{ $order->cancellation_reason }}</dd>
                        @endif
                    @endif
                </dl>
            </div>
        </div>

        {{-- Type-specific specification --}}
        <div class="card mb-3">
            <div class="card-header"><h2 class="h6 mb-0">Specification</h2></div>
            <div class="card-body p-3 p-md-4">
                @if ($specs->isEmpty())
                    <p class="text-muted mb-0">No specification was supplied for this order.</p>
                @else
                    <dl class="oms-detail mb-0">
                        @foreach ($specs as $label => $value)
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        @endforeach
                    </dl>
                @endif
            </div>
        </div>

        {{-- Work history — shop floor only, like the activity timeline. --}}
        @unless ($role === 'user')
            <div class="card mb-3">
                <div class="card-header"><h2 class="h6 mb-0">Tasks</h2></div>
                <div class="card-body p-3 p-md-4">
                    @forelse ($order->tasks as $task)
                        @php $sla = $task->sla(); @endphp
                        <div class="oms-review mb-2">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <strong>{{ $task->task_type->label() }}</strong>
                                    — {{ $task->employee?->name ?? '—' }}
                                    <div class="oms-meter__label">
                                        Assigned {{ $task->assigned_at->format('d-m-Y H:i') }} ·
                                        {{ $task->duration_hours }}h allowed ·
                                        deadline {{ $sla->deadline->format('d-m-Y H:i') }}
                                    </div>
                                </div>
                                <span class="oms-badge oms-badge--{{ $sla->status->badge() }}">{{ $sla->status->label() }}</span>
                            </div>
                            <div class="oms-progress mt-2">
                                <div class="oms-progress__bar oms-progress__bar--{{ $sla->status->bar() }}" style="width: {{ $sla->percent }}%"></div>
                            </div>
                            @if ($task->completion_note)
                                <div class="oms-meter__label mt-2">{{ $task->completion_note }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Nobody has been assigned to this order yet.</p>
                    @endforelse
                </div>
            </div>
        @endunless

        {{-- Revisions --}}
        @if ($revisions->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header"><h2 class="h6 mb-0">Revisions</h2></div>
                <div class="card-body p-3 p-md-4">
                    @foreach ($revisions as $revision)
                        <div class="oms-review mb-2">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <div>
                                    <strong>{{ $revision->revision_number }}</strong> — {{ $revision->reason->label() }}
                                    <div class="oms-meter__label">
                                        {{ $revision->created_at->format('d-m-Y H:i') }} ·
                                        requested by {{ $revision->by_label }}
                                        @if ($role !== 'user') · assigned to {{ $revision->assignee_name }} @endif
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="oms-badge oms-badge--{{ $revision->priority->badge() }}">{{ $revision->priority->label() }}</span>
                                    <span class="oms-badge oms-badge--{{ $revision->status->badge() }}">{{ $revision->status->label() }}</span>

                                    {{-- Assigning and rejecting live here: there is no separate
                                         revisions screen. The employee closes it out from My
                                         Tasks. --}}
                                    @if ($isStaff && $revision->isOpen())
                                        <button type="button" class="oms-action ms-1" title="Assign" aria-label="Assign"
                                                data-bs-toggle="modal" data-bs-target="#assignRevisionModal"
                                                data-action="{{ route($role.'.revisions.assign', $revision) }}"
                                                data-revision="{{ $revision->revision_number }}"
                                                data-order="{{ $order->order_number }} — {{ $order->design_name }}"
                                                data-priority="{{ $revision->priority->value }}"
                                                data-comments="{{ $revision->body }}">
                                            <i class="fa-solid fa-user-plus"></i>
                                        </button>

                                        <button type="button" class="oms-action" title="Reject" aria-label="Reject"
                                                data-bs-toggle="modal" data-bs-target="#rejectRevisionModal"
                                                data-action="{{ route($role.'.revisions.reject', $revision) }}"
                                                data-revision="{{ $revision->revision_number }}"
                                                data-order="{{ $order->order_number }} — {{ $order->design_name }}"
                                                data-comments="{{ $revision->body }}">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-2">{{ $revision->body }}</div>

                            {{-- What the customer sent with the request. It used to hang in
                                 the thread; with that gone this is the only place the
                                 employee doing the work can reach it. The revision's own
                                 output is not here — approved or not, that lands in
                                 Delivered Files. --}}
                            @if ($revision->attachments->isNotEmpty())
                                <ul class="oms-filelist mt-2 mb-0">
                                    @foreach ($revision->attachments as $file)
                                        <li>
                                            <i class="fa-regular fa-file"></i>
                                            <a href="{{ route('files.download', $file) }}">{{ $file->original_name }}</a>
                                            <span class="oms-meter__label">{{ $file->human_size }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Side column --}}
    <div class="col-12 col-xl-4">

        @include('orders.partials._files', [
            'title' => 'Artwork',
            'files' => $order->artwork,
            'empty' => 'No artwork was attached.',
        ])

        {{-- The customer sees only what an approver has released — a revised
             file included, which is why this reads $delivered and not just
             the order's own deliverables. Staff and the employee who did the
             work see everything, including what is still waiting on
             sign-off. --}}
        @include('orders.partials._files', [
            'title' => 'Delivered Files',
            'files' => $role === 'user'
                ? $delivered->whereNotNull('approved_at')
                : $delivered,
            'empty' => 'Nothing has been delivered yet.',
        ])

        {{-- Pricing — admin and manager only --}}
        @if ($canPrice)
            <div class="card mb-3">
                <div class="card-header"><h2 class="h6 mb-0">Pricing</h2></div>
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route($role.'.orders.price', $order) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <label class="form-label" for="price">Order Price ($)</label>
                        <div class="d-flex gap-2">
                            <input type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror"
                                   id="price" name="price" value="{{ old('price', $order->price) }}" placeholder="0.00">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                        @error('price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @if ($order->priced_at)
                            <div class="form-text">
                                Priced by {{ $order->pricedBy?->name ?? '—' }} on {{ $order->priced_at->format('d-m-Y H:i') }}
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        @endif

        {{-- Activity timeline — who did what, when. Admin and manager only:
             neither the customer nor the employee working the brief needs the
             order's full paper trail. --}}
        @if ($isStaff)
            <div class="card">
                <div class="card-header"><h2 class="h6 mb-0">Activity</h2></div>
                <div class="card-body p-3 p-md-4">
                    <ul class="oms-timeline mb-0">
                        @forelse ($order->activities as $activity)
                            <li class="{{ $activity->tone() }}">
                                <div class="oms-timeline__title">{{ $activity->description }}</div>
                                <div class="oms-timeline__meta">
                                    {{ $activity->created_at->format('d-m-Y H:i') }}
                                    @if ($activity->user)
                                        · {{ $activity->user->name }}
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li><div class="oms-timeline__meta">No activity recorded yet.</div></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endif

    </div>
</div>
