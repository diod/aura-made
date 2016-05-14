#!/bin/bash

cd /home/diod/aura-made;

while true; do
  php runme.php | tee -a runme.log
done;