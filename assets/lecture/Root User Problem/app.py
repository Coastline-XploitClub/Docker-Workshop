#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Security Report Generator for Root User Problem Demonstration
Generates an HTML report showing the security implications of running containers as root.
"""

import os
import pwd
import grp
import socket
import stat
import time
from pathlib import Path


def get_user_info():
    """Get current user information"""
    uid = os.getuid()
    gid = os.getgid()

    try:
        user = pwd.getpwuid(uid)
        username = user.pw_name
        home = user.pw_dir
        shell = user.pw_shell
    except KeyError:
        username = f"Unknown (UID: {uid})"
        home = "Unknown"
        shell = "Unknown"

    try:
        group = grp.getgrgid(gid)
        groupname = group.gr_name
    except KeyError:
        groupname = f"Unknown (GID: {gid})"

    return {
        "uid": uid,
        "gid": gid,
        "username": username,
        "groupname": groupname,
        "home": home,
        "shell": shell,
        "is_root": uid == 0,
    }


def check_file_permissions(paths):
    """Check read/write permissions for critical system paths"""
    results = []

    for path in paths:
        try:
            path_obj = Path(path)
            if path_obj.exists():
                stat_info = path_obj.stat()
                perms = stat.filemode(stat_info.st_mode)
                readable = os.access(path, os.R_OK)
                writable = os.access(path, os.W_OK)

                results.append(
                    {
                        "path": path,
                        "exists": True,
                        "permissions": perms,
                        "readable": readable,
                        "writable": writable,
                    }
                )
            else:
                results.append(
                    {
                        "path": path,
                        "exists": False,
                        "permissions": "N/A",
                        "readable": False,
                        "writable": False,
                    }
                )
        except Exception as e:
            results.append(
                {
                    "path": path,
                    "exists": "Error",
                    "permissions": f"Error: {e}",
                    "readable": False,
                    "writable": False,
                }
            )

    return results


def get_network_info():
    """Get network configuration info"""
    try:
        hostname = socket.gethostname()
        fqdn = socket.getfqdn()
        return {"hostname": hostname, "fqdn": fqdn}
    except Exception as e:
        return {"hostname": f"Error: {e}", "fqdn": "Unknown"}


def check_process_capabilities():
    """Check process capabilities (simplified)"""
    caps = []

    # Check if we can bind to privileged ports
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        sock.bind(("localhost", 80))
        sock.close()
        caps.append("Can bind to privileged port 80")
    except Exception:
        caps.append("Cannot bind to privileged port 80")

    # Check environment variables
    if "PATH" in os.environ:
        caps.append(f"PATH access: {os.environ['PATH'][:100]}...")

    return caps


def generate_html_report():
    """Generate the complete HTML security report"""
    user_info = get_user_info()

    # Critical system paths to check
    critical_paths = [
        "/etc/passwd",
        "/etc/shadow",
        "/etc/hosts",
        "/etc/sudoers",
        "/root",
        "/usr/bin",
        "/var/log",
        "/tmp",
        "/etc/nginx/nginx.conf",
    ]

    file_perms = check_file_permissions(critical_paths)
    network_info = get_network_info()
    capabilities = check_process_capabilities()

    # Generate HTML
    html_content = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Container Security Report - Root User Problem</title>
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
        .alert {{
            background-color: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #721c24;
        }}
        .warning {{
            background-color: #fd7e14;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #a0520d;
        }}
        .info {{
            background-color: #0d6efd;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #084298;
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
        table {{
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }}
        th, td {{
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #404040;
        }}
        th {{
            background-color: #404040;
            font-weight: bold;
        }}
        .dangerous {{
            background-color: #dc354520;
            color: #ff6b7a;
        }}
        .safe {{
            background-color: #19875420;
            color: #75dd88;
        }}
        .timestamp {{
            text-align: center;
            margin: 20px 0;
            color: #999;
            font-style: italic;
        }}
        .root-indicator {{
            font-size: 2em;
            color: #dc3545;
            text-align: center;
            margin: 20px 0;
        }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Container Security Assessment Report 🚨</h1>
            <p>Demonstrating the Root User Problem in Docker Containers</p>
        </div>

        {"<div class='alert'><strong>⚠️ CRITICAL SECURITY RISK DETECTED!</strong><br>This container is running as ROOT user (UID 0). This is a serious security vulnerability.</div>" if user_info['is_root'] else "<div class='info'><strong>SECURE CONFIGURATION</strong><br>This container is running as a non-privileged user.</div>"}

        <div class="section">
            <h2>👤 Current User Information</h2>
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                    <th>Security Impact</th>
                </tr>
                <tr class="{'dangerous' if user_info['is_root'] else 'safe'}">
                    <td><strong>User ID (UID)</strong></td>
                    <td>{user_info['uid']}</td>
                    <td>{"ROOT ACCESS - Can access any file/process" if user_info['is_root'] else "Limited user privileges"}</td>
                </tr>
                <tr>
                    <td><strong>Username</strong></td>
                    <td>{user_info['username']}</td>
                    <td>Process owner identity</td>
                </tr>
                <tr>
                    <td><strong>Group ID (GID)</strong></td>
                    <td>{user_info['gid']}</td>
                    <td>Group-based permissions</td>
                </tr>
                <tr>
                    <td><strong>Group Name</strong></td>
                    <td>{user_info['groupname']}</td>
                    <td>Group membership privileges</td>
                </tr>
                <tr>
                    <td><strong>Home Directory</strong></td>
                    <td>{user_info['home']}</td>
                    <td>User's default directory</td>
                </tr>
                <tr>
                    <td><strong>Shell</strong></td>
                    <td>{user_info['shell']}</td>
                    <td>Command execution environment</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>📁 File System Access Analysis</h2>
            <p>Testing access to critical system files and directories:</p>
            <table>
                <tr>
                    <th>Path</th>
                    <th>Exists</th>
                    <th>Permissions</th>
                    <th>Readable</th>
                    <th>Writable</th>
                    <th>Risk Level</th>
                </tr>"""

    for perm in file_perms:
        risk_class = (
            "dangerous"
            if perm["writable"]
            and perm["path"] in ["/etc/passwd", "/etc/shadow", "/etc/sudoers", "/root"]
            else ""
        )
        risk_level = (
            "HIGH RISK"
            if perm["writable"]
            and perm["path"] in ["/etc/passwd", "/etc/shadow", "/etc/sudoers", "/root"]
            else "Normal"
        )

        html_content += f"""
                <tr class="{risk_class}">
                    <td><code>{perm['path']}</code></td>
                    <td>{'✅' if perm['exists'] else '❌'}</td>
                    <td><code>{perm['permissions']}</code></td>
                    <td>{'✅' if perm['readable'] else '❌'}</td>
                    <td>{'⚠️' if perm['writable'] else '❌'}</td>
                    <td>{risk_level}</td>
                </tr>"""

    html_content += f"""
            </table>
        </div>

        <div class="section">
            <h2>🌐 Network Configuration</h2>
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td><strong>Hostname</strong></td>
                    <td>{network_info['hostname']}</td>
                </tr>
                <tr>
                    <td><strong>FQDN</strong></td>
                    <td>{network_info['fqdn']}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>⚡ Process Capabilities</h2>
            <ul>"""

    for cap in capabilities:
        html_content += f"<li>{cap}</li>"

    html_content += f"""
            </ul>
        </div>

        <div class="section">
            <h2>🛡️ Security Recommendations</h2>
            {"<div class='alert'>" if user_info['is_root'] else "<div class='info'>"}
                <h3>{"IMMEDIATE ACTION REQUIRED" if user_info['is_root'] else "SECURITY STATUS: GOOD"}</h3>
                {"<p><strong>This container is running as root!</strong> This creates serious security vulnerabilities:</p>" if user_info['is_root'] else "<p>This container is running with appropriate user privileges.</p>"}
                <ul>
                    {"<li>🚨 Can read/write any file on the system</li><li>🚨 Can install packages and modify system configuration</li><li>🚨 Can access other containers if they share volumes</li><li>🚨 Container escape leads to full host compromise</li>" if user_info['is_root'] else "<li>✅ Limited file system access</li><li>✅ Cannot modify system configuration</li><li>✅ Reduced attack surface</li><li>✅ Container escape impact minimized</li>"}
                </ul>
                {"<h4>Fix: Add USER directive to Dockerfile:</h4><pre>RUN addgroup --system appgroup && adduser --system --ingroup appgroup appuser" + chr(10) + "USER appuser</pre>" if user_info['is_root'] else "<h4>Current configuration follows security best practices.</h4>"}
            </div>
        </div>

        <div class="section">
            <h2>📚 CCDC Training Notes</h2>
            <div class="info">
                <h4>Why This Matters in CCDC:</h4>
                <ul>
                    <li><strong>Attack Surface:</strong> Root containers are prime targets for privilege escalation</li>
                    <li><strong>Lateral Movement:</strong> Compromised root containers can access shared volumes and network resources</li>
                    <li><strong>Incident Response:</strong> Root access makes containment and forensics more difficult</li>
                    <li><strong>Compliance:</strong> Running as root violates security best practices and compliance frameworks</li>
                </ul>
            </div>
        </div>

        <div class="timestamp">
            <p>Report generated on: {time.strftime('%Y-%m-%d %H:%M:%S UTC', time.gmtime())}</p>
            <p>Container Security Assessment Tool - CCDC Training Workshop</p>
        </div>
    </div>
</body>
</html>"""

    return html_content


def main():
    """Generate and save the security report"""
    print("Generating container security report...")

    html_report = generate_html_report()

    # Ensure the nginx html directory exists
    nginx_html_dir = Path("/usr/share/nginx/html")
    nginx_html_dir.mkdir(parents=True, exist_ok=True)

    # Write the report with UTF-8 encoding
    index_file = nginx_html_dir / "index.html"
    with open(index_file, "w", encoding="utf-8") as f:
        f.write(html_report)

    print(f"Security report generated: {index_file}")
    print("Report details:")

    user_info = get_user_info()
    if user_info["is_root"]:
        print("  ⚠️  WARNING: Running as ROOT user (UID 0)")
        print("  This demonstrates a serious security vulnerability!")
    else:
        print("  ✅ Running as non-privileged user")
        print("  This follows security best practices")


if __name__ == "__main__":
    main()
