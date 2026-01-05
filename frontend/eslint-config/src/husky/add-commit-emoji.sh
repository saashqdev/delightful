#!/usr/bin/env sh

# Check whether .husky directory exists at repo root
if [ ! -d "$(pwd)/.husky" ]; then
  echo "Not found .husky directory, please install husky and run 'npx husky init' in root directory first."
  exit 1
fi

# Read ./template/add-commit-emoji and insert it at the top of $(pwd)/.husky/commit-msg
TEMPLATE_FILE="$(dirname $0)/template/add-commit-emoji"

# Read .husky/commit-msg and save it temporarily
COMMIT_MSG="$(pwd)/.husky/commit-msg"
COMMIT_MSG_TMP="$(pwd)/.husky/commit-msg.tmp"

if [ -f "$COMMIT_MSG" ]; then
    cp $COMMIT_MSG $COMMIT_MSG_TMP
fi

if [ -f "$TEMPLATE_FILE" ]; then

    cat $TEMPLATE_FILE > $(pwd)/.husky/commit-msg

    if [ -f "$COMMIT_MSG_TMP" ]; then
        while read line || [[ -n ${line} ]];
        do
            echo $line >> $(pwd)/.husky/commit-msg
        done < $COMMIT_MSG_TMP

        rm -rf $COMMIT_MSG_TMP
    fi

    # Set execute permission
    chmod +x $(pwd)/.husky/commit-msg

    echo "\n🥳 Add Successfully! \nTry to create a commit to verify.\n"

    exit 1
fi

echo "Not found template file"