<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code - JSS Marketplace</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 560px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 4px 0 0 0;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .content {
            padding: 40px 32px;
        }
        .title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .text {
            font-size: 14px;
            color: #475569;
            margin-bottom: 24px;
        }
        .otp-box {
            background-color: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 28px;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 900;
            letter-spacing: 8px;
            color: #e11d48;
            margin: 0;
        }
        .warning {
            background-color: #fff1f2;
            border-left: 4px solid #f43f5e;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 12px;
            color: #9f1239;
            margin-bottom: 24px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>JSS Marketplace</h1>
            <p>Security Verification Service</p>
        </div>

        <div class="content">
            <div class="title">Your One-Time Verification Code</div>
            <div class="text">
                @if(($type ?? 'email_verification') === 'email_verification')
                    Thank you for signing up with JSS Marketplace. Please use the 6-digit code below to verify your email address and activate your account.
                @else
                    We received a request to reset your JSS Marketplace password. Please use the 6-digit code below to complete your password reset request.
                @endif
            </div>

            <div class="otp-box">
                <div class="otp-code">{{ $otpCode }}</div>
            </div>

            <div class="warning">
                <strong>Important Security Note:</strong> This code is valid for <strong>10 minutes</strong> and can only be used once. Never share this code with anyone. JSS Marketplace staff will never ask for your verification code.
            </div>

            <div class="text" style="font-size: 13px; color: #64748b; margin-bottom: 0;">
                If you did not initiate this request, please ignore this email or contact platform support immediately.
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} JSS Marketplace Solutions. All rights reserved.
        </div>
    </div>
</body>
</html>
