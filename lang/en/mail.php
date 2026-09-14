<?php

return [
    'subjects' => [
        'request_received_customer' => 'We received your booking — :code',
        'request_new_owner' => 'New booking request — :yacht (:code)',
        'request_new_admin' => '[New request] :yacht — :code',
        'approved_customer' => 'Your booking is confirmed — :code',
        'approved_owner' => 'Booking confirmed — :code',
        'rejected_customer' => 'Your booking could not be fulfilled — :code',
        'cancelled_customer' => 'Your booking has been cancelled — :code',
        'cancelled_owner' => 'A booking has been cancelled — :code',
        'cancel_requested_owner' => 'The guest requested a cancellation — :code',
        'cancel_requested_admin' => '[Cancellation request] :yacht — :code',
        'pending_reminder_owner' => 'You have a request awaiting reply — :code',
        'escalated_admin' => '[Action needed] Unanswered request — :code',
        'trip_reminder_customer' => '3 days until departure — :code',
    ],

    'password' => [
        'subject' => ':site — password reset',
        'intro' => 'We received a request to reset your password.',
        'action' => 'Reset password',
        'expire' => 'This link expires in :count minutes.',
        'ignore' => 'If you did not request this, you can ignore this email; nothing will change on your account.',
    ],

    'common' => [
        'hello' => 'Hello :name,',
        'code' => 'Booking code',
        'yacht' => 'Tour',
        'dates' => 'Dates',
        'guests' => 'Guests',
        'estimate' => 'Total',
        'customer' => 'Customer',
        'phone' => 'Phone',
        'email' => 'Email',
        'note' => 'Note',
        'estimate_notice' => 'Prices are fixed and never change.',
        'no_payment' => 'No payment is taken through our site. You pay directly on board on the day of the tour.',
        'view_reservation' => 'View booking',
        'qr_hint' => 'Show this QR code to verify your booking quickly.',
        'respond' => 'Respond to booking',
        'regards' => 'Fair winds,',
        'footer_auto' => 'This email was sent automatically by :site.',
    ],

    'request_received_customer' => [
        'intro' => 'Your booking has reached us.',
        'body' => 'We will confirm it and get back to you shortly. No payment is required at this stage; you will pay on board on the day of the tour.',
    ],
    'request_new_owner' => [
        'intro' => 'There is a new booking request for your tour.',
        'body' => 'You can approve or decline it from the link below without logging into the panel. The dates are blocked only once you approve.',
    ],
    'request_new_admin' => [
        'intro' => 'A new booking request has arrived.',
        'body' => 'If the booking is not answered within 4 hours a reminder is sent; after 12 hours it is flagged in the admin panel.',
    ],
    'approved_customer' => [
        'intro' => 'Your booking has been approved.',
        'body' => 'The dates are now reserved for you. We will contact you about the meeting details. You will pay directly on board on the day of the tour.',
    ],
    'approved_owner' => [
        'intro' => 'The booking is confirmed and the dates are blocked on your calendar.',
        'body' => 'Customer contact details are below.',
    ],
    'rejected_customer' => [
        'intro' => 'Unfortunately your request could not be fulfilled.',
        'body' => 'You are welcome to book again for different dates or another tour.',
    ],
    'cancelled_customer' => [
        'intro' => 'Your booking has been cancelled.',
        'body' => 'You can book the same tour again if you wish.',
    ],
    'cancelled_owner' => [
        'intro' => 'A booking has been cancelled.',
        'body' => 'Those dates are open for booking again on your calendar.',
    ],
    'cancel_requested_owner' => [
        'intro' => 'The guest has requested to cancel this booking.',
        'body' => 'The decision is yours. If you accept, use "Cancel" in the panel; the dates go back on sale.',
    ],
    'cancel_requested_admin' => [
        'intro' => 'A guest has requested a cancellation.',
        'body' => 'You can decide from the admin panel.',
    ],
    'pending_reminder_owner' => [
        'intro' => 'You have a request awaiting your reply.',
        'body' => 'The guest is waiting for a reply. Use the link below to approve or decline.',
    ],
    'escalated_admin' => [
        'intro' => 'This booking has gone unanswered for 12 hours.',
        'body' => 'You can approve or decline it yourself from the admin panel.',
    ],
    'trip_reminder_customer' => [
        'intro' => 'Only 3 days until your departure.',
        'body' => 'Remember to finish your preparations and confirm the meeting details with us. Payment is made on board.',
    ],
];
