<?php

return [
    'subjects' => [
        'request_received_customer' => 'We received your request — :code',
        'request_new_owner' => 'New booking request — :yacht (:code)',
        'request_new_admin' => '[New request] :yacht — :code',
        'approved_customer' => 'Your booking is confirmed — :code',
        'approved_owner' => 'Booking confirmed — :code',
        'rejected_customer' => 'Your request could not be fulfilled — :code',
        'cancelled_customer' => 'Your booking has been cancelled — :code',
        'cancelled_owner' => 'A booking has been cancelled — :code',
        'pending_reminder_owner' => 'You have a request awaiting reply — :code',
        'escalated_admin' => '[Action needed] Unanswered request — :code',
        'trip_reminder_customer' => '3 days until departure — :code',
    ],

    'common' => [
        'hello' => 'Hello :name,',
        'code' => 'Booking code',
        'yacht' => 'Yacht',
        'dates' => 'Dates',
        'guests' => 'Guests',
        'estimate' => 'Estimated total',
        'customer' => 'Customer',
        'phone' => 'Phone',
        'email' => 'Email',
        'note' => 'Note',
        'estimate_notice' => 'This is an estimate; the final price is confirmed upon approval.',
        'no_payment' => 'No payment is taken through our site. Payment is arranged directly with the owner after approval.',
        'view_reservation' => 'View booking',
        'respond' => 'Respond to request',
        'regards' => 'Fair winds,',
        'footer_auto' => 'This email was sent automatically by :site.',
    ],

    'request_received_customer' => [
        'intro' => 'Your booking request has reached us and has been passed to the yacht owner.',
        'body' => 'We will write again as soon as the owner reviews and approves it. No payment is required at this stage, and the dates are not blocked yet.',
    ],
    'request_new_owner' => [
        'intro' => 'There is a new booking request for your yacht.',
        'body' => 'You can approve or decline it from the link below without logging into the panel. The dates are blocked only once you approve.',
    ],
    'request_new_admin' => [
        'intro' => 'A new booking request has arrived.',
        'body' => 'If the owner does not respond within 4 hours a reminder is sent; after 12 hours the request is flagged in the admin panel.',
    ],
    'approved_customer' => [
        'intro' => 'Your booking has been approved.',
        'body' => 'The dates are now reserved for you. The owner will contact you about payment and handover details.',
    ],
    'approved_owner' => [
        'intro' => 'The booking is confirmed and the dates are blocked on your calendar.',
        'body' => 'Customer contact details are below.',
    ],
    'rejected_customer' => [
        'intro' => 'Unfortunately your request could not be fulfilled.',
        'body' => 'You are welcome to send a new request for different dates or another yacht.',
    ],
    'cancelled_customer' => [
        'intro' => 'Your booking has been cancelled.',
        'body' => 'You can send a new request for the same yacht if you wish.',
    ],
    'cancelled_owner' => [
        'intro' => 'A booking has been cancelled.',
        'body' => 'Those dates are open for booking again on your calendar.',
    ],
    'pending_reminder_owner' => [
        'intro' => 'You have a request awaiting your reply.',
        'body' => 'Guests favour listings that respond quickly. Use the link below to approve or decline.',
    ],
    'escalated_admin' => [
        'intro' => 'The owner has not responded to this request for 12 hours.',
        'body' => 'You can approve or decline it yourself from the admin panel.',
    ],
    'trip_reminder_customer' => [
        'intro' => 'Only 3 days until your departure.',
        'body' => 'Remember to finish your preparations and confirm the meeting details with the yacht owner.',
    ],
];
