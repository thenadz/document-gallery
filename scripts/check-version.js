#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const colors = {
    reset: '\x1b[0m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m'
};

function log(message, color = 'reset') {
    console.log(color + message + colors.reset);
}

function extractVersion(file, pattern) {
    const content = fs.readFileSync(file, 'utf8');
    const match = content.match(pattern);
    return match ? match[1] : null;
}

// Extract versions from different files
const packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
const packageVersion = packageJson.version;

const readmeVersion = extractVersion(
    'README.txt',
    /^Stable tag:\s*(.+)$/m
);

const phpVersion = extractVersion(
    'document-gallery.php',
    /^\s*\*\s*Version:\s*(.+)$/m
);

const phpConstVersion = extractVersion(
    'document-gallery.php',
    /define\(\s*'DG_VERSION',\s*'(.+?)'\s*\);/
);

// Report versions
log('\n=== Version Check ===', colors.yellow);
log(`package.json:              ${packageVersion}`);
log(`README.txt (Stable tag):   ${readmeVersion}`);
log(`document-gallery.php:      ${phpVersion}`);
log(`DG_VERSION constant:       ${phpConstVersion}`);

// Check for mismatches
const versions = [packageVersion, readmeVersion, phpVersion, phpConstVersion];
const allMatch = versions.every(v => v === packageVersion);

if (allMatch) {
    log('\n✓ All versions match!', colors.green);
    process.exit(0);
} else {
    log('\n✗ Version mismatch detected!', colors.red);
    log('All version numbers must match before building.', colors.red);
    process.exit(1);
}
