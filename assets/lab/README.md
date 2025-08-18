# Week 5 Lab Assets - Docker Security

This directory contains supporting files for the Docker Security lab exercises.

## Files Overview

### Vulnerable Configurations
- `Dockerfile.vulnerable` - Intentionally insecure Dockerfile with multiple vulnerabilities
- `docker-compose.vulnerable.yml` - Dangerous compose configuration with privilege escalation risks
- `package.json` - Node.js dependencies with known vulnerabilities
- `app.js` - Sample application with security issues

### Secure Configurations
- `Dockerfile.secure` - Hardened Dockerfile following security best practices
- `docker-compose.secure.yml` - Secure compose configuration with proper controls
- `healthcheck.js` - Health check script for container monitoring

### Security Tools Configuration
- `trivy.yaml` - Trivy scanner configuration for comprehensive security analysis
- `falco-rules.yaml` - Custom Falco rules for runtime security monitoring

## Lab Exercises

### Exercise 1: Vulnerability Assessment
1. Build vulnerable image: `docker build -f Dockerfile.vulnerable -t vulnerable-app .`
2. Scan with Docker Scout: `docker scout cves vulnerable-app`
3. Scan with Trivy: `trivy image vulnerable-app`

### Exercise 2: Container Hardening
1. Compare vulnerable vs secure Dockerfiles
2. Build secure image: `docker build -f Dockerfile.secure -t secure-app .`
3. Compare vulnerability counts

### Exercise 3: Runtime Security
1. Deploy vulnerable compose: `docker-compose -f docker-compose.vulnerable.yml up`
2. Exploit privilege escalation
3. Deploy secure compose: `docker-compose -f docker-compose.secure.yml up`

### Exercise 4: Security Monitoring
1. Install Falco with custom rules
2. Trigger security events
3. Monitor alerts and responses

## Security Best Practices Demonstrated

- ✅ Non-root user implementation
- ✅ Multi-stage builds for reduced attack surface
- ✅ Secrets management with Docker secrets
- ✅ Resource limits and security constraints
- ✅ Health checks and monitoring
- ✅ Runtime protection with Falco

## CCDC Competition Readiness

These exercises prepare students for real-world container security scenarios commonly encountered in CCDC competitions, including:
- Rapid vulnerability assessment
- Quick remediation under time pressure
- Security documentation and reporting
- Incident response procedures