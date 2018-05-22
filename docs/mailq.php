<?php

// $(mailq 2>/dev/null | tail -n 1 | awk '{print $5}') ; if [ -z "$NUM" ]; then echo "0"; else echo $NUM; fi
$command = "bash -c \"\\\$(mailq 2>/dev/null | tail -n 1 | awk '{print \\\$5}') ; if [ -z \\\"\$NUM\\\" ]; then echo \\\"0\\\"; else echo \\\$NUM; fi\"";
echo shell_exec($command);
