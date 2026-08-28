#!/bin/bash
# phpopenvpnadmin installer orchestrator
# Runs all steps in order. Each step is idempotent — safe to re-run.
# On failure: fix the issue, re-run this script. Completed steps are skipped.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STEPS_DIR="${SCRIPT_DIR}/steps"

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo ""
echo -e "${BLUE}======================================${NC}"
echo -e "${BLUE}  phpopenvpnadmin installer${NC}"
echo -e "${BLUE}======================================${NC}"
echo ""

if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Error: This installer must be run as root.${NC}"
    echo "Run: sudo bash install.sh"
    exit 1
fi

steps=("$STEPS_DIR"/[0-9]*.sh)

if [ ${#steps[@]} -eq 0 ]; then
    echo -e "${RED}No step scripts found in ${STEPS_DIR}${NC}"
    exit 1
fi

total=${#steps[@]}
current=0

for step in "${steps[@]}"; do
    current=$((current + 1))
    name=$(basename "$step" .sh)
    echo -e "${BLUE}[${current}/${total}]${NC} ${name}"
    bash "$step" || {
        echo ""
        echo -e "${RED}======================================${NC}"
        echo -e "${RED}  Installation failed at: ${name}${NC}"
        echo -e "${RED}  Fix the issue and re-run install.sh${NC}"
        echo -e "${RED}  Completed steps will be skipped.${NC}"
        echo -e "${RED}======================================${NC}"
        exit 1
    }
done

echo ""
echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}  Installation complete!${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""
