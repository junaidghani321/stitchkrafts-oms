{{--
    The admin/manager action bar on an order detail page: edit, advance the
    status, assign work, request a revision on the customer's behalf.

    Only transitions that are legal from the current status are offered — the
    controller re-checks, this just keeps impossible buttons off the screen.

    Expects: $order, $role
--}}
@php
    /*
       Cancelled is filtered out: it has its own button and modal so the
       reason and the actor are always captured. updateStatus() rejects it
       server-side too.
    */
    $transitions = array_values(array_filter(
        $order->status->allowedTransitions(),
        fn ($next) => $next !== App\Enums\OrderStatus::Cancelled,
    ));
@endphp

<div class="oms-form-actions">
    <a href="{{ route($role.'.orders.index') }}" class="btn btn-brand">Back to Orders</a>

    @can('update', $order)
        <a href="{{ route($role.'.orders.edit', $order) }}" class="btn btn-primary">Edit Order</a>
    @endcan

    @can('assignTask', $order)
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignTaskModal"
                data-order-id="{{ $order->id }}"
                data-order="{{ $order->order_number }}"
                data-design="{{ $order->design_name }}"
                data-type-label="{{ $order->type->label() }}">
            Assign Task
        </button>
    @endcan

    @can('requestRevision', $order)
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#revisionModal"
                data-order-id="{{ $order->id }}"
                data-order="{{ $order->order_number }}"
                data-design="{{ $order->design_name }}"
                data-customer="{{ $order->customer?->name }}">
            Request Revision
        </button>
    @endcan

    @can('cancel', $order)
        <button type="button" class="btn btn-danger ms-auto" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
            Cancel Order
        </button>
    @endcan

    {{-- Admin only; the route exists under the admin prefix alone. --}}
    @can('delete', $order)
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteOrderModal"
                data-action="{{ route('admin.orders.destroy', $order) }}"
                data-order="{{ $order->order_number }}"
                data-design="{{ $order->design_name }}"
                data-customer="{{ $order->customer?->name }}">
            Delete Order
        </button>
    @endcan
</div>

@can('cancel', $order)
    @include('orders.partials._cancel_order_modal', ['action' => route($role.'.orders.cancel', $order)])
@endcan

@can('delete', $order)
    @include('orders.partials._delete_order_modal')
@endcan

@can('updateStatus', $order)
    @if ($transitions !== [])
        <div class="card mt-3">
            <div class="card-header"><h2 class="h6 mb-0">Move this order on</h2></div>
            <div class="card-body p-3 p-md-4">
                @error('status')<div class="alert alert-danger">{{ $message }}</div>@enderror

                <form action="{{ route($role.'.orders.status', $order) }}" method="POST" class="d-flex flex-wrap gap-2">
                    @csrf
                    @method('PATCH')
                    @foreach ($transitions as $next)
                        <button type="submit" name="status" value="{{ $next->value }}" class="btn btn-brand">
                            {{ $next->label() }}
                        </button>
                    @endforeach
                </form>

                <div class="form-text mt-2">
                    Currently <strong>{{ $order->status->label() }}</strong>.
                </div>
            </div>
        </div>
    @endif
@endcan

@can('assignTask', $order)
    @include('orders.partials._assign_task_modal', [
        'action'    => route($role.'.tasks.store'),
        'employees' => $employees ?? \App\Models\User::employees()->active()->orderBy('name')->get(['id', 'name']),
    ])
@endcan

@can('requestRevision', $order)
    @include('orders.partials._revision_modal', ['action' => route($role.'.revisions.store')])
@endcan

{{-- The assign/reject dialogs for the Revisions card in _detail. Included
     unconditionally: the card's own buttons are already gated on $isStaff and
     the revision being open, and the modals are inert without a trigger. --}}
@include('orders.partials._assign_revision_modal', [
    'employees' => $employees ?? \App\Models\User::employees()->active()->orderBy('name')->get(['id', 'name']),
])
@include('orders.partials._reject_revision_modal')
