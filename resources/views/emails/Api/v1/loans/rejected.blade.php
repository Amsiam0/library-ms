// resources/views/mail/api/v1/loans/rejected.blade.php
<x-mail::message>
    # Loan Request Rejected

    Dear {{ $bookLoan->user->name }},

    Your loan request for "{{ $bookLoan->book->title }}" has been rejected.

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
