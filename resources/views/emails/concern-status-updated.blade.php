<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Concern Status Update</title>
    <style>
        /* RESET & BASICS */
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6; /* Very light cool gray */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #374151; /* Dark gray, softer than black */
            -webkit-font-smoothing: antialiased;
        }

        /* LAYOUT */
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f3f4f6;
            padding-bottom: 40px;
        }

        .main-container {
            background-color: #ffffff;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); /* Very subtle shadow */
            border: 1px solid #e5e7eb;
        }

        /* BRANDING */
        .header {
            padding: 30px 40px 10px 40px;
            text-align: left;
        }
        
        .brand-text {
            color: #2563eb; /* Professional Royal Blue */
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        /* CONTENT */
        .content {
            padding: 20px 40px 40px 40px;
        }

        h1 {
            color: #111827;
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 16px 0;
            line-height: 1.3;
        }

        p {
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 24px 0;
            color: #4b5563;
        }

        /* STATUS CARD */
        .status-card {
            background-color: #f9fafb; /* Extremely light gray */
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .status-transition {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 14px;
        }

        .status-prev {
            color: #6b7280;
            text-decoration: line-through;
        }

        .arrow {
            color: #9ca3af;
            font-size: 12px;
        }

        .status-new {
            color: #2563eb; /* Brand color for the active status */
            background-color: #eff6ff;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            border: 1px solid #dbeafe;
        }

        /* DATA GRID */
        .data-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        
        .data-row:last-child {
            border-bottom: none;
        }

        .data-label {
            color: #6b7280;
            font-weight: 500;
        }

        .data-value {
            color: #111827;
            font-weight: 600;
            text-align: right;
        }

        /* REMARKS SECTION */
        .remarks-container {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #d1d5db;
        }

        .remarks-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .remarks-text {
            font-size: 14px;
            color: #374151;
            font-style: italic;
        }

        /* FOOTER */
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .footer a {
            color: #9ca3af;
            text-decoration: underline;
        }

        /* MOBILE RESPONSIVENESS */
        @media only screen and (max-width: 600px) {
            .header, .content { padding: 20px; }
            .status-card { padding: 16px; }
            .data-row { flex-direction: column; align-items: flex-start; gap: 2px; }
            .data-value { text-align: left; }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <br>
        <div class="main-container">
            
            <div class="header">
                <span class="brand-text">UrbanWatch</span>
            </div>

            <div class="content">
                <h1>Status Update</h1>
                <p>Hello {{ $citizenName }}, the status of your reported concern has changed.</p>

                <div class="status-card">
                    
                    <div class="status-transition">
                        <span class="status-prev">{{ ucfirst($previousStatus) }}</span>
                        <span class="arrow">▶</span>
                        <span class="status-new">{{ ucfirst($newStatus) }}</span>
                    </div>

                    <div class="data-grid">
                        <div class="data-row">
                            <span class="data-label">Concern ID</span>
                            <span class="data-value">#{{ $concern->id }}</span>
                        </div>
                        <div class="data-row">
                            <span class="data-label">Category</span>
                            <span class="data-value">{{ ucfirst($concern->category ?? 'General') }}</span>
                        </div>
                        <div class="data-row">
                            <span class="data-label">Updated By</span>
                            <span class="data-value">{{ $updatedByName }}</span>
                        </div>
                    </div>

                    @if($remarks)
                    <div class="remarks-container">
                        <div class="remarks-label">Official Remarks</div>
                        <div class="remarks-text">"{{ $remarks }}"</div>
                    </div>
                    @endif

                </div>

                <p style="font-size: 14px; color: #6b7280; margin: 0;">
                    You can track further progress by logging into your account or contacting your Purok Leader.
                </p>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} UrbanWatch System. <br>
            Automated notification. Please do not reply.
        </div>
    </div>
</body>
</html>