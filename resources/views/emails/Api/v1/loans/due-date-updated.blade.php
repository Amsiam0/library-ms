<x-mail::message>
    # Due Date Updated

    Dear {{ $bookLoan->user->name }},

    The due date for your book loan "{{ $bookLoan->book->title }}" has been updated.

    **New Due Date:** {{ $bookLoan->due_date->format('Y-m-d') }}

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
