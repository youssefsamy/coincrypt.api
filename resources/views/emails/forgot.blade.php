<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<style>
    body {
        font-size: 16px;
    }

</style>
<body>
    <p>Hello, </p>
    <br>
    <br>
    A request to reset the password on your Coincrypt account {{$user->email}} was just made. To set a new password on this account, please click the following link:
    <a href="{{$user->reset_url}}">{{$user->reset_url}}</a>
    For security reasons, this link will expire in 10 minutes.
    <br>
    <br>
    Sincerely,
    <br>
    The Coincrypt Team
</body>
</html>