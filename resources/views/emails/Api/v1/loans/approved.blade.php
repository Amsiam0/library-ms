@component('mail::message')
    # Loan Request Approved

    Dear {{ $bookLoan->user->name }},

    Your loan request for "{{ $bookLoan->book->title }}" has been approved.

    **Due Date:** {{ $bookLoan->due_date->format('Y-m-d') }}

    Please collect your book from the library.

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
