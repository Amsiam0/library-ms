<!DOCTYPE html>
<html>

<head>
    <title>Book Loan Due Date Reminder</title>
</head>

<body>
    <h1>Reminder: Your Book Loan is Due Soon!</h1>
    <p>Dear {{ $bookLoan->user->name }},</p>
    <p>This is a friendly reminder that your book loan for <strong>{{ $bookLoan->book->title }}</strong> is due in 3
        days, on {{ $bookLoan->return_date }}.</p>
    <p>Please return the book on or before the due date to avoid any late fees.</p>
    <p>Thank you!</p>
</body>

</html>
