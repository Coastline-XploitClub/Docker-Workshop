#!/bin/bash
# Build script for secure Docker secrets demonstration
# This script shows how to build a Docker image using BuildKit secrets

set -e

echo "🛡️ Building Secure Docker Image with BuildKit Secrets"
echo "=================================================="

# Check if secret files exist
echo "📁 Checking for secret files..."
for secret_file in api_key.txt db_password.txt aws_secret.txt jwt_secret.txt; do
    if [ ! -f "./secrets/$secret_file" ]; then
        echo "❌ Missing secret file: $secret_file"
        exit 1
    fi
    echo "✅ Found: $secret_file"
done

echo ""
echo "🔧 Building with Docker BuildKit and mounted secrets..."
echo "Note: Secrets are mounted during build but NOT embedded in final image!"

# Enable BuildKit and build with secrets
DOCKER_BUILDKIT=1 docker build \
  --secret id=api_key,src=./secrets/api_key.txt \
  --secret id=db_password,src=./secrets/db_password.txt \
  --secret id=aws_secret,src=./secrets/aws_secret.txt \
  --secret id=jwt_secret,src=./secrets/jwt_secret.txt \
  -f Dockerfile.secure \
  -t secrets-secure \
  .

echo ""
echo "✅ Secure image built successfully!"
echo ""
echo "🔍 Verification commands:"
echo "  docker history --no-trunc secrets-secure"
echo "  docker save secrets-secure | tar -xO | strings | grep -i secret"
echo ""
echo "🚀 To run the secure demo:"
echo "  ./run-secure.sh"
echo "  # OR manually:"
echo "  docker run -e API_KEY='runtime-secret' -e ANOTHER_SECRET='runtime-value' -p 8000:8000 secrets-secure"