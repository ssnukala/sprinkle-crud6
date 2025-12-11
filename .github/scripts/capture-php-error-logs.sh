#!/bin/bash

# Script to capture and display PHP error logs from UserFrosting application
# This script looks for PHP error logs and displays them

echo "========================================="
echo "Capturing PHP Error Logs"
echo "========================================="
echo ""

# Array of possible PHP error log locations
declare -a log_locations=(
    "/tmp/php_errors.log"
    "/var/log/php_errors.log"
    "/var/log/php/error.log"
    "app/logs/userfrosting.log"
    "app/logs/errors.log"
    "app/storage/logs/userfrosting.log"
    "app/storage/logs/errors.log"
)

# Check for UserFrosting debug logs
uf_log_locations=(
    "app/logs/*.log"
    "app/storage/logs/*.log"
)

found_errors=false

# Check standard PHP error log locations
echo "Checking standard PHP error log locations..."
for log_path in "${log_locations[@]}"; do
    if [ -f "$log_path" ]; then
        echo ""
        echo "📄 Found log file: $log_path"
        file_size=$(stat -f%z "$log_path" 2>/dev/null || stat -c%s "$log_path" 2>/dev/null || echo "0")
        if [ "$file_size" -gt 0 ]; then
            echo "   Size: $((file_size / 1024)) KB"
            echo ""
            echo "─────────────────────────────────────────"
            echo "Last 50 lines of $log_path:"
            echo "─────────────────────────────────────────"
            # tail -n 50 "$log_path"
            echo "─────────────────────────────────────────"
            found_errors=true
        else
            echo "   (empty file)"
        fi
    fi
done

# Check UserFrosting log directories
echo ""
echo "Checking UserFrosting log directories..."
for pattern in "${uf_log_locations[@]}"; do
    for log_file in $pattern; do
        if [ -f "$log_file" ]; then
            echo ""
            echo "📄 Found UserFrosting log: $log_file"
            file_size=$(stat -f%z "$log_file" 2>/dev/null || stat -c%s "$log_file" 2>/dev/null || echo "0")
            if [ "$file_size" -gt 0 ]; then
                echo "   Size: $((file_size / 1024)) KB"
                echo ""
                echo "─────────────────────────────────────────"
                echo "Last 50 lines of $log_file:"
                echo "─────────────────────────────────────────"
                # tail -n 50 "$log_file"
                echo "─────────────────────────────────────────"
                found_errors=true
            else
                echo "   (empty file)"
            fi
        fi
    done
done

# Check for recent PHP errors in system log
if command -v journalctl &> /dev/null; then
    echo ""
    echo "Checking system journal for PHP errors (last 100 entries)..."
    echo "─────────────────────────────────────────"
    journalctl -u php* -n 100 --no-pager 2>/dev/null || echo "No systemd journal entries found for PHP"
    echo "─────────────────────────────────────────"
fi

echo ""
if [ "$found_errors" = false ]; then
    echo "✅ No PHP error log files found or all logs are empty"
else
    echo "⚠️  PHP error logs found and displayed above"
fi

echo ""
echo "========================================="
echo "End of PHP Error Log Capture"
echo "========================================="
