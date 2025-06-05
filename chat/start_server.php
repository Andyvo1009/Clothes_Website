<?php
// start_server.php - Auto-start WebSocket server

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function isServerRunning($host = '127.0.0.1', $port = 8080)
{
    $connection = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($connection) {
        fclose($connection);
        return true;
    }
    return false;
}

function startServer()
{
    $chatDir = __DIR__;
    $scriptPath = $chatDir . '/bin/chat-server.php';
    $batPath = $chatDir . '/start-chat-server.bat';

    if (!file_exists($scriptPath)) {
        return [
            'success' => false,
            'message' => 'Chat server script not found'
        ];
    }

    // For Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Try using the batch file first
        if (file_exists($batPath)) {
            $command = "\"$batPath\"";
            $output = shell_exec($command);

            // Wait a moment for server to start
            sleep(3);

            if (isServerRunning()) {
                return [
                    'success' => true,
                    'message' => 'Chat server started successfully via batch file'
                ];
            }
        }

        // Fallback to direct PHP command
        $command = "start /B php \"$scriptPath\" > nul 2>&1";
        $output = shell_exec($command);

        // Wait a moment for server to start
        sleep(3);

        if (isServerRunning()) {
            return [
                'success' => true,
                'message' => 'Chat server started successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to start chat server. Please ensure PHP is in your PATH and port 8080 is available.'
            ];
        }
    } else {
        // For Linux/Mac
        $command = "nohup php \"$scriptPath\" > /dev/null 2>&1 & echo $!";
        $pid = shell_exec($command);

        if ($pid) {
            // Wait a moment for server to start
            sleep(3);

            if (isServerRunning()) {
                return [
                    'success' => true,
                    'message' => 'Chat server started successfully',
                    'pid' => trim($pid)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to start chat server'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Failed to execute start command'
            ];
        }
    }
}

try {
    if (isServerRunning()) {
        echo json_encode([
            'success' => true,
            'message' => 'Chat server is already running',
            'status' => 'running'
        ]);
    } else {
        $result = startServer();
        echo json_encode($result);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
