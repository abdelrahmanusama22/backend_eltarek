<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verification Code</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #D32F2F;
            padding: 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .otp-box {
            display: inline-block;
            background-color: #fce4e4;
            color: #D32F2F;
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 5px;
            padding: 15px 30px;
            border-radius: 6px;
            margin: 20px 0;
            border: 2px dashed #D32F2F;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #888888;
            border-top: 1px solid #eeeeee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>El-Tarek Automotive</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Welcome to El-Tarek Automotive! Please use the verification code below to complete your authentication process. This code will expire shortly.</p>
            
            <div class="otp-box">
                {{ $otp }}
            </div>
            
            <p>If you did not request this code, please ignore this email.</p>
        </div>
        <div class="footer">
            &copy; 2026 El-Tarek Automotive &amp; Trading Co. All rights reserved.
        </div>
    </div>
</body>
</html>
