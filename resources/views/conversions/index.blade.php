<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epoch Time Converter</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding-top: 50px;
            padding-bottom: 50px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        h1 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .current-timestamp {
            font-size: 28px;
            font-weight: 500;
            color: #2563eb;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            text-align: center;
            padding: 16px;
            background-color: #eff6ff;
            border-radius: 6px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 6px;
        }

        input[type="datetime-local"] {
            width: 100%;
            padding: 12px 14px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
            color: #333;
        }

        input[type="datetime-local"]:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background-color: #2563eb;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button[type="submit"]:hover {
            background-color: #1d4ed8;
        }

        .errors {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .errors ul {
            margin: 0;
            padding-left: 20px;
        }

        .separator {
            border-top: 2px dashed #ddd;
            margin: 30px 0;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .history-item .original {
            color: #333;
        }

        .history-item .arrow {
            color: #999;
            margin: 0 12px;
        }

        .history-item .epoch {
            color: #2563eb;
            font-weight: 500;
        }

        .empty-history {
            text-align: center;
            color: #999;
            padding: 20px;
            font-size: 14px;
        }

        @media (max-width: 640px) {
            .container {
                margin: 16px;
                padding: 24px;
            }

            .history-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .history-item .arrow {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Epoch Time Converter</h1>

        <!-- Section 1: Current Epoch Timestamp -->
        <div class="section">
            <div class="section-title">Current Epoch Timestamp</div>
            <div class="current-timestamp">{{ $currentEpochTimestamp }}</div>
        </div>

        <!-- Section 2: Timestamp Conversion Form -->
        <div class="section">
            <div class="section-title">Enter date and time</div>

            @if ($errors->any())
                <div class="errors">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('conversions.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="input_datetime">Date & Time</label>
                    <input type="datetime-local" id="input_datetime" name="input_datetime" required>
                </div>
                <button type="submit">Convert</button>
            </form>
        </div>

        <!-- Section 3: Conversion History -->
        <div class="separator"></div>

        <div class="section">
            <div class="section-title">Conversion History</div>

            @if ($conversions->isEmpty())
                <div class="empty-history">No conversions yet.</div>
            @else
                @foreach ($conversions as $conversion)
                    <div class="history-item">
                        <span class="original">{{ $conversion->input_datetime->format('Y-m-d H:i:s') }}</span>
                        <span class="arrow">→</span>
                        <span class="epoch">{{ $conversion->epoch_timestamp }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</body>
</html>