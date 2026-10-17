<?php

/*
|--------------------------------------------------------------------------
| Email notification preferences
|--------------------------------------------------------------------------
|
| Which switches each role sees on Settings → Email, in order. Labels and
| hints are transcribed verbatim from the screens they replaced.
|
| Every notification declares which key gates it (see OrderNotification and
| its subclasses). A key that is `on` sends mail in addition to the in-app
| notification; database notifications are always delivered regardless — the
| bell is not something you can switch off.
|
| `pending` marks a switch whose feature does not exist yet. It is still shown
| and still saved, but nothing sends against it and the UI says so, which is
| better than a control that silently does nothing.
|
*/

return [

    'admin' => [
        'order_completed' => [
            'label' => 'Order Completed Alert',
            'hint' => 'Tells you when an order clears production and is ready to go out.',
            'default' => false,
        ],
        'new_order_alert' => [
            'label' => 'New Order Alert',
            'hint' => 'Notifies the admin team whenever a customer places an order.',
            'default' => true,
        ],
        'quote_alert' => [
            'label' => 'Quote Activity Alert',
            'hint' => 'Notifies the admin team when a quote is requested or accepted.',
            'default' => true,
        ],
        'revision_update' => [
            'label' => 'Revision Alert',
            'hint' => 'Notifies the admin team when a revision is raised.',
            'default' => true,
        ],
        'task_update' => [
            'label' => 'Task Completion Alert',
            'hint' => 'Notifies the admin team when an employee finishes a task.',
            'default' => false,
        ],
        'order_approval' => [
            'label' => 'Final Approval Alert',
            'hint' => 'Tells you when an employee submits finished work that needs your approval.',
            'default' => true,
        ],
        'invoice_breakdown' => [
            'label' => 'Invoice with Breakdown Email',
            'hint' => 'Attaches the itemised breakdown to every invoice.',
            'default' => true,
            'pending' => true,
        ],
        'invoice_paid' => [
            'label' => 'Invoice Payment Confirmation Email',
            'hint' => 'Confirms the payment once an invoice is settled.',
            'default' => true,
            'pending' => true,
        ],
        'order_cancelled' => [
            'label' => 'Order Cancelled Alert',
            'hint' => 'Notifies you when an order is cancelled.',
            'default' => true,
        ],
        'new_comment_alert' => [
            'label' => 'New Comment Alert',
            'hint' => 'Notifies the admin team of new comments on an order.',
            'default' => false,
        ],
    ],

    /* Managers run the floor, so they get the same operational switches
       minus anything to do with money. */
    'manager' => [
        'new_order_alert' => [
            'label' => 'New Order Alert',
            'hint' => 'Notifies you whenever a customer places an order.',
            'default' => true,
        ],
        'quote_alert' => [
            'label' => 'Quote Activity Alert',
            'hint' => 'Notifies you when a quote is requested or accepted.',
            'default' => true,
        ],
        'revision_update' => [
            'label' => 'Revision Alert',
            'hint' => 'Notifies you when a revision is raised.',
            'default' => true,
        ],
        'task_update' => [
            'label' => 'Task Completion Alert',
            'hint' => 'Notifies you when an employee finishes a task.',
            'default' => false,
        ],
        'order_approval' => [
            'label' => 'Final Approval Alert',
            'hint' => 'Tells you when an employee submits finished work that needs your approval.',
            'default' => true,
        ],
        'order_completed' => [
            'label' => 'Order Completed Alert',
            'hint' => 'Tells you when an order clears production and is ready to go out.',
            'default' => false,
        ],
        'order_cancelled' => [
            'label' => 'Order Cancelled Alert',
            'hint' => 'Notifies you when an order is cancelled.',
            'default' => true,
        ],
        'new_comment_alert' => [
            'label' => 'New Comment Alert',
            'hint' => 'Notifies you of new comments on an order.',
            'default' => false,
        ],
    ],

    'employee' => [
        'task_assigned' => [
            'label' => 'Task Assigned Email',
            'hint' => 'Emails you when a task is handed to you.',
            'default' => true,
        ],
        'revision_update' => [
            'label' => 'Revision Assigned Email',
            'hint' => 'Emails you when a revision is assigned to you.',
            'default' => true,
        ],
        'comment_reply' => [
            'label' => 'Comment Reply Email',
            'hint' => 'Lets you know when someone replies on one of your orders.',
            'default' => true,
        ],
        'order_cancelled' => [
            'label' => 'Order Cancelled Email',
            'hint' => 'Tells you when an order you were working on is called off.',
            'default' => true,
        ],
    ],

    'user' => [
        'order_placed' => [
            'label' => 'Order Place Confirmation Email',
            'hint' => 'Sent to you as soon as your order is placed.',
            'default' => false,
        ],
        'order_priced' => [
            'label' => 'Order Priced Email',
            'hint' => 'Tells you as soon as one of your orders has been priced.',
            'default' => true,
        ],
        'order_completed' => [
            'label' => 'Order Completed Email',
            'hint' => 'Tells you when your order is finished and the files are ready to download.',
            'default' => true,
        ],
        'quote_alert' => [
            'label' => 'Quote Priced Email',
            'hint' => 'Tells you when a quote you requested has been priced.',
            'default' => true,
        ],
        'revision_update' => [
            'label' => 'Revision Status Email',
            'hint' => 'Tells you when a revision request changes status.',
            'default' => true,
        ],
        'comment_reply' => [
            'label' => 'Comment Reply Email',
            'hint' => 'Lets you know when someone replies on one of your orders.',
            'default' => true,
        ],
        'order_cancelled' => [
            'label' => 'Order Cancelled Email',
            'hint' => 'Confirms in writing when one of your orders is cancelled.',
            'default' => true,
        ],
        'invoice_breakdown' => [
            'label' => 'Invoice with Breakdown Email',
            'hint' => 'Attaches the itemised breakdown to every invoice.',
            'default' => true,
            'pending' => true,
        ],
        'invoice_paid' => [
            'label' => 'Invoice Payment Confirmation Email',
            'hint' => 'Confirms the payment once an invoice is settled.',
            'default' => true,
            'pending' => true,
        ],
    ],

];
