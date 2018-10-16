<!DOCTYPE html>
<html>
<head>
    <title>CoinCrypt</title>
</head>
<style>
    body {
        font-size: 16px;
    }
</style>
<body>
    Hello,
    <br>
    <br>
    Welcome to Coincrypt!
    <br>
    Please click this link or paste the address into your browser to confirm your registration:
    <br>
    <a href="{{$user->confirmation_url}}">{{$user->confirmation_url}}</a>
    <br>
    <br>
    Sincerely,
    <br>
    The Coincrypt Team
</body>
</html>