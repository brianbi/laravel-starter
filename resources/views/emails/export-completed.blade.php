<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>导出任务通知</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4a90d9;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .success {
            background-color: #4caf50;
        }
        .failed {
            background-color: #f44336;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .info-table td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 30%;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4a90d9;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #357abd;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header {{ $task->isSuccess() ? 'success' : 'failed' }}">
        <h1>{{ $task->isSuccess() ? '导出完成' : '导出失败' }}</h1>
    </div>
    
    <div class="content">
        <p>您好，</p>
        
        @if($task->isSuccess())
            <p>您的导出任务已完成，以下是任务详情：</p>
        @else
            <p>很抱歉，您的导出任务失败了，以下是任务详情：</p>
        @endif
        
        <table class="info-table">
            <tr>
                <td>资源类型</td>
                <td>{{ $task->resource }}</td>
            </tr>
            <tr>
                <td>数据总量</td>
                <td>{{ number_format($task->total_count) }} 条</td>
            </tr>
            <tr>
                <td>导出格式</td>
                <td>{{ strtoupper($task->format) }}</td>
            </tr>
            <tr>
                <td>任务状态</td>
                <td>{{ $task->status_text }}</td>
            </tr>
            @if($task->isSuccess())
            <tr>
                <td>文件大小</td>
                <td>{{ number_format($task->file_size / 1024, 2) }} KB</td>
            </tr>
            <tr>
                <td>过期时间</td>
                <td>{{ $task->expires_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @endif
            @if($task->isFailed())
            <tr>
                <td>错误信息</td>
                <td>{{ $task->error_message }}</td>
            </tr>
            @endif
        </table>
        
        @if($task->isSuccess())
            <p>请在过期时间前下载文件，过期后文件将被自动删除。</p>
            <a href="{{ route('admin.export.download', ['uuid' => $task->uuid]) }}" class="btn">下载文件</a>
        @else
            <p>如果问题持续存在，请联系系统管理员。</p>
        @endif
    </div>
    
    <div class="footer">
        <p>此邮件由系统自动发送，请勿回复。</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>
