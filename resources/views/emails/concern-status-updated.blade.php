<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Concern Status Update</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .email-header {
            background-color: #3b82f6;
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        
        .email-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        
        .email-body {
            padding: 32px 24px;
        }
        
        .greeting {
            font-size: 16px;
            margin-bottom: 16px;
            color: #111;
        }
        
        .status-update {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
            border-left: 4px solid #3b82f6;
        }
        
        .status-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-ongoing { background-color: #dbeafe; color: #1e40af; }
        .status-escalated { background-color: #fee2e2; color: #991b1b; }
        .status-resolved { background-color: #d1fae5; color: #065f46; }
        
        .arrow { color: #94a3b8; font-size: 18px; }
        
        .detail-item {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
        }
        
        .detail-item:last-child { border-bottom: none; }
        
        .detail-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .detail-value {
            font-size: 14px;
            color: #111;
            font-weight: 500;
        }
        
        .remarks {
            background-color: #fffbeb;
            border-left: 3px solid #f59e0b;
            padding: 12px 16px;
            margin-top: 16px;
            border-radius: 4px;
        }
        
        .remarks-label {
            font-size: 12px;
            color: #92400e;
            font-weight: 600;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        
        .remarks-text {
            font-size: 14px;
            color: #78350f;
        }
        
        .footer-text {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            margin-top: 24px;
        }
        
        .footer-brand {
            font-weight: 600;
            color: #3b82f6;
            margin-top: 8px;
        }
        
        @media only screen and (max-width: 600px) {
            .email-body { padding: 24px 16px; }
            .status-row { flex-direction: column; }
            .arrow { transform: rotate(90deg); }
            .detail-item { flex-direction: column; gap: 4px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>UrbanWatch</h1>
        </div>
        
        <div class="email-body">
            <p class="greeting">Hello, {{ $citizenName }}!</p>
            
            <p style="font-size: 15px; color: #555; margin-bottom: 8px;">
                Your concern status has been updated.
            </p>
            
            <div class="status-update">
                <div class="status-row">
                    <span class="status-badge status-{{ strtolower($previousStatus) }}">
                        {{ ucfirst($previousStatus) }}
                    </span>
                    <span class="arrow">→</span>
                    <span class="status-badge status-{{ strtolower($newStatus) }}">
                        {{ ucfirst($newStatus) }}
                    </span>
                </div>
                
                <div>
                    <div class="detail-item">
                        <span class="detail-label">Concern ID</span>
                        <span class="detail-value">#{{ $concern->id }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Category</span>
                        <span class="detail-value">{{ $concern->category ?? 'General' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Updated By</span>
                        <span class="detail-value">{{ $updatedByName }}</span>
                    </div>
                </div>
                
                @if($remarks)
                <div class="remarks">
                    <div class="remarks-label">Remarks</div>
                    <div class="remarks-text">{{ $remarks }}</div>
                </div>
                @endif
            </div>
            
            <div class="footer-text">
                <p>If you have questions, please contact your Purok Leader.</p>
                <p class="footer-brand">UrbanWatch</p>
            </div>
        </div>
    </div>
</body>
</html>
