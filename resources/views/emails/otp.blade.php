<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 32px 24px;
            text-align: center;
        }
        
        .logo {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .content {
            padding: 40px 32px;
        }
        
        .greeting {
            font-size: 18px;
            color: #111827;
            margin-bottom: 16px;
        }
        
        .message {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .otp-container {
            background: #f9fafb;
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin: 32px 0;
        }
        
        .otp-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            color: #3b82f6;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        
        .expiry {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 12px;
        }
        
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            border-radius: 4px;
            margin: 24px 0;
        }
        
        .warning-text {
            font-size: 14px;
            color: #92400e;
            margin: 0;
        }
        
        .footer {
            background: #f9fafb;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-text {
            font-size: 13px;
            color: #6b7280;
            margin: 4px 0;
        }
        
        .brand {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
        }
        
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .content {
                padding: 32px 24px;
            }
            
            .otp-code {
                font-size: 32px;
                letter-spacing: 6px;
            }
            
            .footer {
                padding: 20px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">URBANWATCH</div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="greeting">Hello {{ $userName }},</div>
            
            <p class="message">
                We received a request to verify your account. Use the code below to complete your verification.
            </p>
            
            <!-- OTP Code -->
            <div class="otp-container">
                <div class="otp-label">Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="expiry">This code expires in 10 minutes</div>
            </div>
            
            <p class="message">
                Enter this code in the verification screen to continue.
            </p>
            
            <!-- Security Warning -->
            <div class="warning">
                <p class="warning-text">
                    <strong>⚠️ Security Notice:</strong> Never share this code with anyone. 
                    UrbanWatch will never ask for your verification code.
                </p>
            </div>
            
            <p class="message" style="margin-top: 24px; font-size: 14px;">
                If you didn't request this code, please ignore this email or contact our support team if you have concerns.
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                <strong>UrbanWatch</strong> – Keeping our community safe
            </p>
            <p class="footer-text">
                © {{ date('Y') }} UrbanWatch. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
