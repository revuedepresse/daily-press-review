#!/bin/bash

PROJECT_DIR=/var/deploy/devobs/current
TASK_NAME='run-job'
TASK_OWNER=`whoami`
ERROR_LOG=/var/log/job.$TASK_NAME.error.log
OUT_LOG=/var/log/job.$TASK_NAME.out.log

# Ensure required log files exist and
# necessary permissions have been applied to them for the current task owner
test -e $ERROR_LOG || sudo /bin/bash -c 'touch '$ERROR_LOG' && chown '$TASK_OWNER' '$ERROR_LOG
test -e $OUT_LOG || sudo /bin/bash -c 'touch '$OUT_LOG' && chown '$TASK_OWNER' '$OUT_LOG

source $PROJECT_DIR/bin/export-config-parameters.sh
$PROJECT_DIR/app/console wtw:job:run -e prod 2>> $ERROR_LOG >> $OUT_LOG
