#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Docker Embedded Secrets Vulnerability Demonstration
Shows how secrets remain in image layers even after deletion.
"""

import os
import json
import time


def check_secrets():
    """Check for various types of embedded secrets"""
    findings = []

    # Check for API key in environment
    api_key = os.environ.get("API_KEY", "")
    if api_key:
        findings.append(
            {
                "type": "API Key",
                "source": "Environment Variable",
                "value": api_key[:10] + "..." if len(api_key) > 10 else api_key,
                "risk": "HIGH - Exposed in image layers",
            }
        )

    # Check for another hardcoded secret
    another_secret = os.environ.get("ANOTHER_SECRET", "")
    if another_secret:
        findings.append(
            {
                "type": "Hardcoded Secret",
                "source": "ENV directive",
                "value": (
                    another_secret[:10] + "..."
                    if len(another_secret) > 10
                    else another_secret
                ),
                "risk": "HIGH - Visible in docker inspect",
            }
        )

    # Check if .env file exists (it shouldn't after rm)
    env_file_exists = os.path.exists("/app/.env")

    return findings, env_file_exists


def generate_html_report(findings, env_file_exists):
    """Generate HTML report showing the security implications"""

    findings_html = ""
    if findings:
        for finding in findings:
            findings_html += f"""
            <div style="background:#dc354520; border-left:4px solid #dc3545; padding:15px; margin:10px 0;">
                <h4 style="color:#dc3545; margin:0;">🚨 {finding['type']} Detected</h4>
                <p><strong>Source:</strong> {finding['source']}</p>
                <p><strong>Partial Value:</strong> <code>{finding['value']}</code></p>
                <p><strong>Risk:</strong> {finding['risk']}</p>
            </div>"""
    else:
        findings_html = """
        <div style="background:#19875420; border-left:4px solid #198754; padding:15px; margin:10px 0;">
            <h4 style="color:#198754; margin:0;">✅ No Environment Secrets Detected</h4>
            <p>No secrets found in environment variables (this is good!)</p>
        </div>"""

    html_content = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker Embedded Secrets Vulnerability Demo</title>
    <style>
        body {{
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #1a1a1a;
            color: #e0e0e0;
        }}
        .container {{
            max-width: 1200px;
            margin: 0 auto;
        }}
        .header {{
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #dc3545, #a71e2a);
            border-radius: 10px;
        }}
        .section {{
            background-color: #2d2d2d;
            margin: 20px 0;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #404040;
        }}
        .section h2 {{
            margin-top: 0;
            color: #fff;
            border-bottom: 2px solid #404040;
            padding-bottom: 10px;
        }}
        .code-block {{
            background-color: #404040;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            border-left: 4px solid #0d6efd;
        }}
        .warning {{
            background-color: #fd7e14;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #a0520d;
        }}
        .alert {{
            background-color: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #721c24;
        }}
        .info {{
            background-color: #0d6efd;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #084298;
        }}
        ul {{
            line-height: 1.8;
        }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Docker Embedded Secrets Vulnerability</h1>
            <p>Demonstration: Why Secrets Persist in Image Layers</p>
        </div>

        <div class="section">
            <h2>🔍 Secret Detection Results</h2>
            {findings_html}
            
            <h3>File System Check:</h3>
            <p><strong>.env file exists:</strong> 
                <span style="color:{'#dc3545' if env_file_exists else '#198754'};">
                    {'YES - Still present!' if env_file_exists else 'NO - Successfully deleted'}
                </span>
            </p>
            
            {'<div class="alert"><strong>Key Point:</strong> Even though the .env file was deleted, secrets may still be embedded in previous Docker layers!</div>' if not env_file_exists else ''}
        </div>

        <div class="section">
            <h2>🚨 The Vulnerability Explained</h2>
            <div class="alert">
                <h3>What Happened:</h3>
                <ol>
                    <li><strong>Secret Written:</strong> <code>RUN echo "API_KEY=..." &gt; .env</code></li>
                    <li><strong>Secret Deleted:</strong> <code>RUN rm .env</code></li>
                    <li><strong>Secret Persists:</strong> The secret remains in the Docker layer where it was created!</li>
                </ol>
            </div>
            
            <h3>Why This Happens:</h3>
            <ul>
                <li><strong>Immutable Layers:</strong> Docker layers are read-only once created</li>
                <li><strong>Union Filesystem:</strong> Deletion creates a "whiteout" file, doesn't remove original</li>
                <li><strong>Layer History:</strong> All commands and their effects are preserved</li>
            </ul>
        </div>

        <div class="section">
            <h2>🔧 How to Extract These Secrets</h2>
            <div class="warning">
                <strong>Attackers can extract secrets using:</strong>
            </div>
            
            <h3>1. Docker History Command:</h3>
            <div class="code-block">
docker history --no-trunc [image-name]
# Shows all RUN commands including secrets!
            </div>
            
            <h3>2. Image Layer Extraction:</h3>
            <div class="code-block">
docker save [image] | tar -xO | strings | grep -i "secret\\|password\\|key"
# Extracts and searches all layer contents
            </div>
            
            <h3>3. Environment Variable Inspection:</h3>
            <div class="code-block">
docker inspect [image-name] | grep -A10 "Env"
# Shows all hardcoded environment variables
            </div>
            
            <h3>4. Interactive Layer Analysis:</h3>
            <div class="code-block">
dive [image-name]
# Visual tool to explore each layer's contents
            </div>
        </div>

        <div class="section">
            <h2>🛡️ CCDC Defense Strategies</h2>
            <div class="info">
                <h3>Immediate Actions:</h3>
                <ul>
                    <li><strong>Audit Images:</strong> Scan all container images for embedded secrets</li>
                    <li><strong>Use dive tool:</strong> Visual inspection of layer contents</li>
                    <li><strong>Grep for patterns:</strong> Search for API keys, passwords, tokens</li>
                    <li><strong>Check environment:</strong> Inspect hardcoded ENV variables</li>
                </ul>
                
                <h3>Prevention Measures:</h3>
                <ul>
                    <li><strong>Runtime Secrets:</strong> Use Docker secrets, Kubernetes secrets</li>
                    <li><strong>External Sources:</strong> HashiCorp Vault, AWS Secrets Manager</li>
                    <li><strong>Build Arguments:</strong> Only for non-sensitive build-time data</li>
                    <li><strong>Multi-stage Builds:</strong> Secrets only in build stages, not final image</li>
                </ul>
            </div>
        </div>

        <div style="text-align: center; margin: 20px 0; color: #999; font-style: italic;">
            <p>Report generated on: {time.strftime('%Y-%m-%d %H:%M:%S UTC', time.gmtime())}</p>
            <p>Docker Security Demonstration - CCDC Training Workshop</p>
        </div>
    </div>
</body>
</html>"""

    return html_content


def main():
    """Main demonstration function"""
    print("🔐 Docker Embedded Secrets Vulnerability Demonstration")
    print("=" * 60)

    # Check for secrets
    findings, env_file_exists = check_secrets()

    # Print summary to console
    print(f"Environment secrets found: {len(findings)}")
    print(f".env file exists: {env_file_exists}")

    if findings:
        print("\n🚨 SECRETS DETECTED:")
        for finding in findings:
            print(f"  - {finding['type']}: {finding['value']} ({finding['source']})")

    print(f"\n📝 Generating HTML report...")

    # Generate and save HTML report
    html_report = generate_html_report(findings, env_file_exists)

    # Write to index.html for web serving
    with open("/app/index.html", "w", encoding="utf-8") as f:
        f.write(html_report)

    print("✅ Report saved to /app/index.html")

    # Print instructions for manual verification
    print("\n🔧 Manual Verification Commands:")
    print("docker history --no-trunc [image-name]")
    print("docker save [image] | tar -xO | strings | grep -E 'API_KEY|SECRET|PASSWORD'")
    print("docker inspect [image-name] | grep -A5 Env")


if __name__ == "__main__":
    main()
