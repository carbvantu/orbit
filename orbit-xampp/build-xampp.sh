#!/bin/bash
# =====================================================
# ORBIT — Script build cho XAMPP
# Chạy script này trên Replit để tạo bản XAMPP
# =====================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
OUT_DIR="$SCRIPT_DIR"

echo ""
echo "======================================"
echo "  ORBIT — Build phiên bản XAMPP"
echo "======================================"
echo ""

# Build React frontend với cấu hình XAMPP
echo "[1/2] Đang build frontend React..."
cd "$ROOT_DIR"
BASE_PATH="/orbit/" \
VITE_API_BASE="/orbit/api" \
NODE_ENV="production" \
pnpm --filter @workspace/orbit exec vite build

echo ""
echo "[2/2] Copy files vào thư mục orbit-xampp..."

# Copy built assets → orbit-xampp/
DIST="$ROOT_DIR/artifacts/orbit/dist/public"
if [ -d "$DIST" ]; then
    cp -r "$DIST/." "$OUT_DIR/"
    echo "✓ Copied frontend build"
else
    echo "✗ Không tìm thấy build output!"
    exit 1
fi

echo ""
echo "======================================"
echo "  BUILD HOÀN THÀNH!"
echo "======================================"
echo ""
echo "Thư mục orbit-xampp/ đã sẵn sàng."
echo "Xem README.md để biết cách cài đặt."
echo ""
