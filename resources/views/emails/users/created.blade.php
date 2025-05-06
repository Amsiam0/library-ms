<!DOCTYPE html>
<html>

<head>
    <title>Welcome to Our Application!</title>
</head>

<body>
    <h1>Welcome, {{ $user->name }}!</h1>
    <p>Thank you for registering with <a href="{{ config('app.url') }}"> our application.</a></p>
    <p>Your email is: {{ $user->email }}</p>
    <p>Your password is: {{ $password }}</p>
    <p>Please change your password after logging in.</p>
</body>

</html>
