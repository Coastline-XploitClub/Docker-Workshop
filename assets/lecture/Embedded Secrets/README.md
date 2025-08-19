# Docker Embedded Secrets Vulnerability Demonstration

## Overview

This demonstration shows the difference between vulnerable and secure secret management in Docker containers.

## ⚠️ CRITICAL: Understanding Secret Types

### **Build-time Secrets (BuildKit `--mount=type=secret`)**

- **Available**: ONLY during `RUN --mount=type=secret` commands in Dockerfile
- **NOT Available**: To the running application at runtime
- **Purpose**: For build operations like installing from private repos
- **Security**: Not embedded in image layers

### **Runtime Secrets (Environment Variables)**

- **Available**: To the running application via environment variables
- **Injected**: At container startup with `-e` flags
- **Security**: Not embedded in image, provided externally

## 🚨 Common Misconception

**❌ WRONG**: "BuildKit secrets are available to the running app"
**✅ CORRECT**: "BuildKit secrets are only for build operations"

## Files Structure

```
.
├── app.py                 # Same detection app for both versions
├── Dockerfile.vulnerable  # Shows embedded secrets (bad)
├── Dockerfile.secure      # Multi-stage with BuildKit secrets (good)
├── secrets/              # Build-time secret files
│   ├── api_key.txt
│   ├── db_password.txt
│   ├── aws_secret.txt
│   └── jwt_secret.txt
├── build-secure.sh       # Build with BuildKit secrets
└── run-secure.sh         # Run with runtime secrets
```

## Quick Start

### 1. Vulnerable Version (Shows Problem)

```bash
docker build -f Dockerfile.vulnerable -t secrets-vulnerable .
docker run -p 8000:8000 secrets-vulnerable
# Visit http://localhost:8000 - shows embedded secrets!
```

### 2. Secure Version (Shows Solution)

```bash
./build-secure.sh    # Uses BuildKit secrets (not embedded)
./run-secure.sh      # Injects runtime secrets properly
# Visit http://localhost:8000 - shows secure configuration!
```

## What This Demonstrates

### Vulnerable Version:

- ❌ Secrets embedded in image layers via RUN/ENV commands
- ❌ Extractable with `docker history` and `docker save`
- ❌ Persist even after "deletion" with `rm`

### Secure Version:

- ✅ BuildKit secrets available during build but not embedded
- ✅ Multi-stage build ensures clean final image
- ✅ Runtime secrets injected via environment variables
- ✅ Same app.py detects and reports the difference

## Verification Commands

```bash
# Compare image histories
docker history --no-trunc secrets-vulnerable   # Shows embedded secrets
docker history --no-trunc secrets-secure      # Clean!

# Extract and search for secrets
docker save secrets-vulnerable | tar -xO | strings | grep -i secret
docker save secrets-secure | tar -xO | strings | grep -i secret  # No results
```

## CCDC Training Value

- **Vulnerability Recognition**: How to identify embedded secrets
- **Secure Practices**: Proper secret management with BuildKit and runtime injection
- **Audit Tools**: Commands to detect secret exposure in container images
- **Real-world Application**: Techniques used in actual competitions
