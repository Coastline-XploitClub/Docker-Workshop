#!/bin/bash
# Run script for secure Docker secrets demonstration
# This script shows how to run a container with runtime secret injection

set -e

echo "🚀 Running Secure Docker Container with Runtime Secrets"
echo "======================================================"

# Check if the secure image exists
if ! docker images | grep -q "secrets-secure"; then
    echo "❌ Secure image not found. Please build it first:"
    echo "   ./build-secure.sh"
    exit 1
fi

echo "✅ Found secure image: secrets-secure"
echo ""
echo "💉 Injecting secrets at runtime (proper method)..."
echo "Note: These secrets are provided via environment variables, not embedded in image!"

# Define runtime secrets (these would normally come from external secret stores)
API_KEY="sk-runtime-secret-key-2024"
ANOTHER_SECRET="runtime-hardcoded-secret-value"
DATABASE_URL="postgresql://runtime_user:runtime_pass@db:5432/app"

echo ""
echo "🌐 Starting container with runtime secrets on port 8000..."
echo "Access the security report at: http://localhost:8000"
echo ""
echo "💡 The app.py will detect that:"
echo "  ✅ Secrets are properly injected at runtime"
echo "  ✅ No secrets are embedded in the image layers"
echo "  ✅ Container follows security best practices"
echo ""
echo "Press Ctrl+C to stop the container"
echo ""

# Run container with runtime secret injection
docker run \
    -e API_KEY="${API_KEY}" \
    -e ANOTHER_SECRET="${ANOTHER_SECRET}" \
    -e DATABASE_URL="${DATABASE_URL}" \
    -p 8000:8000 \
    --rm \
    secrets-secure