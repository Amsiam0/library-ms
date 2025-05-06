<!DOCTYPE html>
<html>

<head>
    <title>Due Date Increase Request {{ ucfirst($status) }}</title>
</head>

<body>
    <h1>Due Date Increase Request {{ ucfirst($status) }}</h1>
    <p>Dear User,</p>
    <p>Your request to increase the due date for book loan #{{ $dueDateIncrease->book_loan_id }} has been
        {{ $status }}.</p>

    @if ($status == 'approved')
        <p>Your new due date is: {{ date('d M Y', strtotime($dueDateIncrease->new_due_date)) }}</p>
    @endif

    <p>Thank you!</p>
</body>

</html>
